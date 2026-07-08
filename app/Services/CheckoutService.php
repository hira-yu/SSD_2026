<?php

declare(strict_types=1);

class CheckoutService
{
    private CartService $cart;
    private PaymentService $payment;
    private OrderRepository $orders;
    private OrderItemRepository $orderItems;
    private InventoryService $inventory;
    private ProductRepository $products;

    public function __construct()
    {
        $this->cart = new CartService();
        $this->payment = new PaymentService();
        $this->orders = new OrderRepository();
        $this->orderItems = new OrderItemRepository();
        $this->inventory = new InventoryService();
        $this->products = new ProductRepository();
    }

    /**
     * @param array<string, mixed>|null $input
     * @param array<int, string> $errors
     * @return array<string, mixed>
     */
    public function checkoutFormData(?array $input = null, array $errors = []): array
    {
        $form = $this->normalizeCheckoutInput($input ?? $this->checkoutDraft());
        $cartData = $this->cart->cartViewData();
        $this->clearCheckoutConfirmation();
        $this->storeCheckoutDraft($form);

        return [
            'form' => $form,
            'errors' => $errors,
            'cart' => $cartData,
            'demoNotice' => (string) config('app.online_order.demo_notice', ''),
            'demoCardExample' => (array) config('app.online_order.demo_card_example', []),
            'prefectureOptions' => $this->prefectureOptions(),
            'expiryMonthOptions' => $this->expiryMonthOptions(),
            'expiryYearOptions' => $this->expiryYearOptions(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function buildConfirmation(array $input): array
    {
        $form = $this->normalizeCheckoutInput($input);
        $errors = $this->validateCheckoutForm($form);
        $cartData = $this->cart->cartViewData();

        if ($cartData['items'] === []) {
            $errors[] = 'カートが空のため、注文情報を確認できません。';
        }

        if ((int) ($cartData['missing_product_count'] ?? 0) > 0) {
            $errors[] = '存在しない商品がカートに含まれているため、カート内容を見直してください。';
        }

        foreach ($cartData['items'] as $item) {
            if (($item['warning'] ?? null) === null) {
                continue;
            }

            $errors[] = sprintf(
                '%s は在庫数を超えるため、数量を見直してください。',
                (string) $item['product_name']
            );
        }

        if ($errors !== []) {
            $this->clearCheckoutConfirmation();
            $this->storeCheckoutDraft($form);

            return [
                'ok' => false,
                'form' => $form,
                'errors' => array_values(array_unique($errors)),
                'cart' => $cartData,
            ];
        }

        $this->storeCheckoutDraft($form);
        $this->storeCheckoutConfirmation($form);

        return [
            'ok' => true,
            'form' => $form,
            'cart' => $cartData,
            'cardSummary' => $this->payment->buildCardSummary($form),
            'demoNotice' => (string) config('app.online_order.demo_notice', ''),
            'customerSummary' => $this->buildCustomerSummary($form),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function completeOrder(): array
    {
        $confirmation = $this->checkoutConfirmation();

        if (!is_array($confirmation)) {
            return [
                'ok' => false,
                'errors' => ['確認情報の有効期限が切れたため、注文情報の入力からやり直してください。'],
            ];
        }

        if ($this->cart->isEmpty()) {
            $this->clearCheckoutConfirmation();

            return [
                'ok' => false,
                'errors' => ['カートが空のため、注文を確定できません。'],
            ];
        }

        if (($confirmation['cart_hash'] ?? '') !== $this->cart->cartHash()) {
            $this->clearCheckoutConfirmation();

            return [
                'ok' => false,
                'errors' => ['カート内容が変更されたため、確認画面からやり直してください。'],
            ];
        }

        $form = $this->normalizeCheckoutInput($confirmation);
        $errors = $this->validateCheckoutForm($form);
        $cartData = $this->cart->cartViewData();

        if ($cartData['items'] === []) {
            $errors[] = '注文対象の商品がありません。';
        }

        if ((int) ($cartData['missing_product_count'] ?? 0) > 0) {
            $errors[] = '存在しない商品がカートに含まれているため、注文を確定できません。';
        }

        foreach ($cartData['items'] as $item) {
            if (($item['warning'] ?? null) === null) {
                continue;
            }

            $errors[] = sprintf(
                '%s は在庫数を超えるため、注文を確定できません。',
                (string) $item['product_name']
            );
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => array_values(array_unique($errors)),
            ];
        }

        $items = $this->buildOrderItems($cartData['items']);
        $connection = db_connection();

        try {
            $connection->beginTransaction();
            $this->inventory->assertOrderReservable($items);

            $orderNo = $this->generateOrderNo();
            $orderDate = date('Y-m-d H:i:s');
            $orderId = $this->orders->create([
                'order_no' => $orderNo,
                'order_date' => $orderDate,
                'customer_name' => $this->buildCustomerName($form),
                'customer_address' => $this->buildCustomerAddress($form),
                'customer_contact' => $form['customer_contact'],
                'order_type' => (string) config('app.online_order.order_type', 'online'),
                'payment_method' => (string) config('app.online_order.payment_method', 'credit'),
                'payment_status' => (string) config('app.online_order.payment_status', 'paid'),
                'shipping_status' => (string) config('app.online_order.shipping_status', 'unshipped'),
                'subtotal' => (int) $cartData['subtotal'],
                'fee' => (int) config('app.online_order.payment_fee', 0),
                'shipping_fee' => (int) $cartData['shipping_fee'],
                'total_amount' => (int) $cartData['total_amount'],
            ]);

            $this->orderItems->createMany($orderId, $items);
            $this->inventory->reserveForOrder($items);
            $connection->commit();

            $this->cart->clear();
            $this->clearCheckoutConfirmation();
            $this->clearCheckoutDraft();

            return [
                'ok' => true,
                'order_no' => $orderNo,
            ];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            app_log('Online order registration failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'errors' => [
                    $exception instanceof RuntimeException
                        ? $exception->getMessage()
                        : 'ネット注文の登録に失敗しました。時間をおいて再度お試しください。',
                ],
            ];
        }
    }

    public function findDoneViewData(string $orderNo): ?array
    {
        $order = $this->orders->findByOrderNo($orderNo);

        if ($order === null || (string) ($order['order_type'] ?? '') !== 'online') {
            return null;
        }

        $items = $this->orderItems->findByOrderId((int) $order['id']);
        $products = $this->products->findByIds(array_map(
            static fn (array $item): int => (int) ($item['product_id'] ?? 0),
            $items
        ));

        foreach ($items as $index => $item) {
            $product = $products[(int) ($item['product_id'] ?? 0)] ?? null;
            $items[$index]['image_path'] = (string) ($product['image_path'] ?? '');
            $items[$index]['image_url'] = product_image_url((string) ($product['image_path'] ?? ''));
        }

        return [
            'order' => $order,
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeCheckoutInput(array $input): array
    {
        $card = $this->payment->normalizeCardInput($input);
        $legacyName = preg_split('/\s+/u', trim((string) ($input['customer_name'] ?? '')), 2) ?: [];
        $legacyAddress = trim((string) ($input['customer_address'] ?? ''));
        $legacyPostalCode = '';
        $legacyPrefecture = '';
        $legacyCity = '';
        $legacyAddressLine = $legacyAddress;
        $legacyBuilding = '';

        if (preg_match('/^〒?(\d{3})-?(\d{4})\s*(.+)$/u', $legacyAddress, $matches) === 1) {
            $legacyPostalCode = $matches[1] . $matches[2];
            $legacyAddressLine = trim((string) $matches[3]);
        }

        return [
            'last_name' => trim((string) ($input['last_name'] ?? ($legacyName[0] ?? ''))),
            'first_name' => trim((string) ($input['first_name'] ?? ($legacyName[1] ?? ''))),
            'last_name_kana' => trim((string) ($input['last_name_kana'] ?? '')),
            'first_name_kana' => trim((string) ($input['first_name_kana'] ?? '')),
            'postal_code' => preg_replace('/\D+/', '', (string) ($input['postal_code'] ?? $legacyPostalCode)) ?? '',
            'prefecture' => trim((string) ($input['prefecture'] ?? $legacyPrefecture)),
            'city' => trim((string) ($input['city'] ?? $legacyCity)),
            'address_line' => trim((string) ($input['address_line'] ?? $legacyAddressLine)),
            'building' => trim((string) ($input['building'] ?? $legacyBuilding)),
            'customer_contact' => trim((string) ($input['customer_contact'] ?? '')),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_address' => trim((string) ($input['customer_address'] ?? $legacyAddress)),
            'card_number' => (string) ($card['card_number'] ?? ''),
            'cardholder_name' => (string) ($card['cardholder_name'] ?? ''),
            'card_expiry' => (string) ($card['card_expiry'] ?? ''),
            'card_expiry_month' => (string) ($card['card_expiry_month'] ?? ''),
            'card_expiry_year' => (string) ($card['card_expiry_year'] ?? ''),
            'security_code' => (string) ($card['security_code'] ?? ''),
        ];
    }

    /**
     * @param array<string, string> $form
     * @return array<int, string>
     */
    private function validateCheckoutForm(array $form): array
    {
        $errors = [];

        if ($form['last_name'] === '' || $form['first_name'] === '') {
            $errors[] = '氏名を入力してください。';
        }

        if ($form['last_name_kana'] === '' || $form['first_name_kana'] === '') {
            $errors[] = '氏名カナを入力してください。';
        }

        if (
            ($form['last_name_kana'] !== '' && preg_match('/^[ァ-ヶー－\s　]+$/u', $form['last_name_kana']) !== 1)
            || ($form['first_name_kana'] !== '' && preg_match('/^[ァ-ヶー－\s　]+$/u', $form['first_name_kana']) !== 1)
        ) {
            $errors[] = '氏名カナは全角カタカナで入力してください。';
        }

        if (preg_match('/^\d{7}$/', $form['postal_code']) !== 1) {
            $errors[] = '郵便番号はハイフンなし7桁で入力してください。';
        }

        if ($form['prefecture'] === '') {
            $errors[] = '都道府県を選択してください。';
        }

        if ($form['city'] === '') {
            $errors[] = '市区町村を入力してください。';
        }

        if ($form['address_line'] === '') {
            $errors[] = '町名・番地を入力してください。';
        }

        if ($form['customer_contact'] === '') {
            $errors[] = '連絡先を入力してください。';
        }

        return array_merge($errors, $this->payment->validateCardInput($form));
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     * @return array<int, array<string, mixed>>
     */
    private function buildOrderItems(array $cartItems): array
    {
        return array_map(static function (array $item): array {
            return [
                'product_id' => (int) $item['product_id'],
                'product_no' => (string) $item['product_no'],
                'product_name' => (string) $item['product_name'],
                'unit_price' => (int) $item['unit_price'],
                'quantity' => (int) $item['quantity'],
                'line_total' => (int) $item['line_total'],
            ];
        }, $cartItems);
    }

    private function generateOrderNo(): string
    {
        $datePrefix = date('Ymd');
        $latestOrderNo = $this->orders->findLatestOrderNoByDate($datePrefix);
        $nextSequence = 1;

        if (is_string($latestOrderNo) && preg_match('/^ORD\d{8}(\d{4})$/', $latestOrderNo, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return 'ORD' . $datePrefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    private function checkoutDraft(): array
    {
        $draft = $_SESSION[$this->draftSessionKey()] ?? [];

        return is_array($draft) ? $draft : [];
    }

    /**
     * @param array<string, string> $form
     */
    private function storeCheckoutDraft(array $form): void
    {
        $_SESSION[$this->draftSessionKey()] = [
            'last_name' => $form['last_name'],
            'first_name' => $form['first_name'],
            'last_name_kana' => $form['last_name_kana'],
            'first_name_kana' => $form['first_name_kana'],
            'postal_code' => $form['postal_code'],
            'prefecture' => $form['prefecture'],
            'city' => $form['city'],
            'address_line' => $form['address_line'],
            'building' => $form['building'],
            'customer_contact' => $form['customer_contact'],
            'cardholder_name' => $form['cardholder_name'],
            'card_expiry' => $form['card_expiry'],
            'card_expiry_month' => $form['card_expiry_month'],
            'card_expiry_year' => $form['card_expiry_year'],
        ];
    }

    private function clearCheckoutDraft(): void
    {
        unset($_SESSION[$this->draftSessionKey()]);
    }

    /**
     * @param array<string, string> $form
     */
    private function storeCheckoutConfirmation(array $form): void
    {
        $_SESSION[$this->confirmationSessionKey()] = [
            ...$form,
            'cart_hash' => $this->cart->cartHash(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function checkoutConfirmation(): ?array
    {
        $confirmation = $_SESSION[$this->confirmationSessionKey()] ?? null;

        return is_array($confirmation) ? $confirmation : null;
    }

    private function clearCheckoutConfirmation(): void
    {
        unset($_SESSION[$this->confirmationSessionKey()]);
    }

    private function draftSessionKey(): string
    {
        return (string) config('app.online_order.checkout_draft_session_key', 'online_checkout_draft');
    }

    private function confirmationSessionKey(): string
    {
        return (string) config('app.online_order.checkout_confirmation_session_key', 'online_checkout_confirmation');
    }

    /**
     * @return array<string, string>
     */
    private function prefectureOptions(): array
    {
        return [
            '' => '選択してください',
            '北海道' => '北海道',
            '青森県' => '青森県',
            '岩手県' => '岩手県',
            '宮城県' => '宮城県',
            '秋田県' => '秋田県',
            '山形県' => '山形県',
            '福島県' => '福島県',
            '茨城県' => '茨城県',
            '栃木県' => '栃木県',
            '群馬県' => '群馬県',
            '埼玉県' => '埼玉県',
            '千葉県' => '千葉県',
            '東京都' => '東京都',
            '神奈川県' => '神奈川県',
            '新潟県' => '新潟県',
            '富山県' => '富山県',
            '石川県' => '石川県',
            '福井県' => '福井県',
            '山梨県' => '山梨県',
            '長野県' => '長野県',
            '岐阜県' => '岐阜県',
            '静岡県' => '静岡県',
            '愛知県' => '愛知県',
            '三重県' => '三重県',
            '滋賀県' => '滋賀県',
            '京都府' => '京都府',
            '大阪府' => '大阪府',
            '兵庫県' => '兵庫県',
            '奈良県' => '奈良県',
            '和歌山県' => '和歌山県',
            '鳥取県' => '鳥取県',
            '島根県' => '島根県',
            '岡山県' => '岡山県',
            '広島県' => '広島県',
            '山口県' => '山口県',
            '徳島県' => '徳島県',
            '香川県' => '香川県',
            '愛媛県' => '愛媛県',
            '高知県' => '高知県',
            '福岡県' => '福岡県',
            '佐賀県' => '佐賀県',
            '長崎県' => '長崎県',
            '熊本県' => '熊本県',
            '大分県' => '大分県',
            '宮崎県' => '宮崎県',
            '鹿児島県' => '鹿児島県',
            '沖縄県' => '沖縄県',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function expiryMonthOptions(): array
    {
        $options = [];

        for ($month = 1; $month <= 12; $month++) {
            $value = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $options[] = $value;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function expiryYearOptions(): array
    {
        $currentYear = (int) date('Y');
        $options = [];

        for ($offset = 0; $offset <= 12; $offset++) {
            $options[] = (string) ($currentYear + $offset);
        }

        return $options;
    }

    /**
     * @param array<string, string> $form
     * @return array<string, string>
     */
    private function buildCustomerSummary(array $form): array
    {
        return [
            'name' => $this->buildCustomerName($form),
            'name_kana' => trim($form['last_name_kana'] . ' ' . $form['first_name_kana']),
            'postal_code' => substr($form['postal_code'], 0, 3) . '-' . substr($form['postal_code'], 3),
            'address' => trim($form['prefecture'] . $form['city'] . $form['address_line']),
            'building' => $form['building'],
            'contact' => $form['customer_contact'],
        ];
    }

    /**
     * @param array<string, string> $form
     */
    private function buildCustomerName(array $form): string
    {
        return trim($form['last_name'] . ' ' . $form['first_name']);
    }

    /**
     * @param array<string, string> $form
     */
    private function buildCustomerAddress(array $form): string
    {
        $main = trim($form['prefecture'] . $form['city'] . $form['address_line']);
        $building = trim($form['building']);
        $postal = $form['postal_code'] !== ''
            ? '〒' . substr($form['postal_code'], 0, 3) . '-' . substr($form['postal_code'], 3)
            : '';

        return trim($postal . ' ' . $main . ($building !== '' ? ' ' . $building : ''));
    }
}
