<?php

declare(strict_types=1);

class CheckoutService
{
    private CartService $cart;
    private PaymentService $payment;
    private OrderRepository $orders;
    private OrderItemRepository $orderItems;
    private InventoryService $inventory;

    public function __construct()
    {
        $this->cart = new CartService();
        $this->payment = new PaymentService();
        $this->orders = new OrderRepository();
        $this->orderItems = new OrderItemRepository();
        $this->inventory = new InventoryService();
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
                '%s は在庫数量2を超えているため、数量を見直してください。',
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
                '%s は在庫数量2を超えているため、注文を確定できません。',
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
                'customer_name' => $form['customer_name'],
                'customer_address' => $form['customer_address'],
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

        return [
            'order' => $order,
            'items' => $this->orderItems->findByOrderId((int) $order['id']),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeCheckoutInput(array $input): array
    {
        $card = $this->payment->normalizeCardInput($input);

        return [
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_address' => trim((string) ($input['customer_address'] ?? '')),
            'customer_contact' => trim((string) ($input['customer_contact'] ?? '')),
            'card_number' => (string) ($card['card_number'] ?? ''),
            'cardholder_name' => (string) ($card['cardholder_name'] ?? ''),
            'card_expiry' => (string) ($card['card_expiry'] ?? ''),
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

        if ($form['customer_name'] === '') {
            $errors[] = '購入者氏名を入力してください。';
        }

        if ($form['customer_address'] === '') {
            $errors[] = '住所を入力してください。';
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
            'customer_name' => $form['customer_name'],
            'customer_address' => $form['customer_address'],
            'customer_contact' => $form['customer_contact'],
            'cardholder_name' => $form['cardholder_name'],
            'card_expiry' => $form['card_expiry'],
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
}
