<?php

declare(strict_types=1);

class ReceptionOrderService
{
    private ProductRepository $products;
    private OrderRepository $orders;
    private OrderItemRepository $orderItems;
    private InventoryService $inventory;

    /**
     * @var array<string, string>
     */
    private array $paymentLabels = [
        'bank' => '銀行振込',
        'convenience' => 'コンビニ決済',
        'cod' => '代金引換',
        'credit' => 'クレジットカード',
    ];

    /**
     * @var array<string, string>
     */
    private array $paymentStatusLabels = [
        'unpaid' => '未払い',
        'paid' => '支払済',
    ];

    /**
     * @var array<string, string>
     */
    private array $shippingStatusLabels = [
        'unshipped' => '未発送',
        'shipped' => '発送済',
    ];

    public function __construct()
    {
        $this->products = new ProductRepository();
        $this->orders = new OrderRepository();
        $this->orderItems = new OrderItemRepository();
        $this->inventory = new InventoryService();
    }

    /**
     * @return array<string, mixed>
     */
    public function newOrderFormData(?array $input = null, array $errors = []): array
    {
        $form = $this->normalizeFormInput($input ?? []);

        return [
            'form' => $form,
            'errors' => $errors,
            'productOptions' => $this->products->listAll(),
            'paymentOptions' => $this->paymentOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildConfirmation(array $input): array
    {
        $form = $this->normalizeFormInput($input);
        $errors = $this->validateBasicFields($form);
        $normalizedItems = $this->normalizeItems($form['items']);

        if ($normalizedItems === []) {
            $errors[] = '商品を1件以上指定してください。';
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'form' => $form,
                'errors' => $errors,
            ];
        }

        $products = $this->products->findByIds(array_keys($normalizedItems));
        $missing = array_diff(array_map('intval', array_keys($normalizedItems)), array_keys($products));

        if ($missing !== []) {
            return [
                'ok' => false,
                'form' => $form,
                'errors' => ['存在しない商品が含まれています。'],
            ];
        }

        $lineItems = [];
        $subtotal = 0;

        foreach ($normalizedItems as $productId => $quantity) {
            $product = $products[$productId];

            if ($quantity > (int) $product['stock_quantity_2']) {
                $errors[] = sprintf(
                    '%s は在庫数量2を超えているため登録できません。',
                    (string) $product['name']
                );
                continue;
            }

            $unitPrice = (int) $product['price'];
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;

            $lineItems[] = [
                'product_id' => (int) $product['id'],
                'product_no' => (string) $product['product_no'],
                'product_name' => (string) $product['name'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'stock_quantity_2' => (int) $product['stock_quantity_2'],
            ];
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'form' => $form,
                'errors' => $errors,
            ];
        }

        $paymentMethod = $form['payment_method'];
        $fee = $this->paymentFee($paymentMethod);
        $shippingFee = $this->shippingFee();
        $totals = [
            'subtotal' => $subtotal,
            'fee' => $fee,
            'shipping_fee' => $shippingFee,
            'total_amount' => $subtotal + $fee + $shippingFee,
        ];

        return [
            'ok' => true,
            'form' => $form,
            'items' => $lineItems,
            'totals' => $totals,
            'paymentLabel' => $this->paymentLabel($paymentMethod),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(array $input): array
    {
        $confirmation = $this->buildConfirmation($input);

        if (($confirmation['ok'] ?? false) !== true) {
            return $confirmation;
        }

        $form = $confirmation['form'];
        $items = $confirmation['items'];
        $totals = $confirmation['totals'];
        $connection = db_connection();

        try {
            $connection->beginTransaction();
            $this->inventory->reserveForOrder($items);

            $orderNo = $this->generateOrderNo();
            $orderDate = date('Y-m-d H:i:s');
            $orderId = $this->orders->create([
                'order_no' => $orderNo,
                'order_date' => $orderDate,
                'customer_name' => $form['customer_name'],
                'customer_address' => $form['customer_address'],
                'customer_contact' => $form['customer_contact'],
                'order_type' => 'phone_fax',
                'payment_method' => $form['payment_method'],
                'payment_status' => 'unpaid',
                'shipping_status' => 'unshipped',
                'subtotal' => $totals['subtotal'],
                'fee' => $totals['fee'],
                'shipping_fee' => $totals['shipping_fee'],
                'total_amount' => $totals['total_amount'],
            ]);

            $this->orderItems->createMany($orderId, $items);

            $connection->commit();

            return [
                'ok' => true,
                'order_no' => $orderNo,
                'payment_method' => $form['payment_method'],
            ];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $message = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : '注文の登録に失敗しました。時間をおいて再度お試しください。';

            app_log('Reception order registration failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'form' => $form,
                'errors' => [$message],
            ];
        }
    }

    public function findCompleteViewData(string $orderNo): ?array
    {
        $order = $this->orders->findByOrderNo($orderNo);

        if ($order === null) {
            return null;
        }

        $items = $this->orderItems->findByOrderId((int) $order['id']);

        return [
            'order' => $order,
            'items' => $items,
            'paymentLabel' => $this->paymentLabel((string) $order['payment_method']),
            'paymentGuide' => $this->paymentGuide((string) $order['payment_method']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchOrders(array $input): array
    {
        $filters = [
            'order_no' => trim((string) ($input['order_no'] ?? '')),
            'order_date' => trim((string) ($input['order_date'] ?? '')),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'payment_method' => trim((string) ($input['payment_method'] ?? '')),
            'payment_status' => trim((string) ($input['payment_status'] ?? '')),
            'shipping_status' => trim((string) ($input['shipping_status'] ?? '')),
        ];

        if (!array_key_exists($filters['payment_method'], $this->paymentMethodOptions())) {
            $filters['payment_method'] = '';
        }

        if (!array_key_exists($filters['payment_status'], $this->paymentStatusOptions())) {
            $filters['payment_status'] = '';
        }

        if (!array_key_exists($filters['shipping_status'], $this->shippingStatusOptions())) {
            $filters['shipping_status'] = '';
        }

        $orders = array_map(
            fn (array $order): array => $this->decorateOrder($order),
            $this->orders->search($filters)
        );

        return [
            'filters' => $filters,
            'orders' => $orders,
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
            'shippingStatusOptions' => $this->shippingStatusOptions(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderDetailViewData(string $orderNo): ?array
    {
        $order = $this->orders->findByOrderNo($orderNo);

        if ($order === null) {
            return null;
        }

        return [
            'order' => $this->decorateOrder($order),
            'items' => $this->orderItems->findByOrderId((int) $order['id']),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeFormInput(array $input): array
    {
        $items = [];
        $productIds = $input['product_ids'] ?? ($input['items']['product_id'] ?? []);
        $quantities = $input['quantities'] ?? ($input['items']['quantity'] ?? []);

        if (is_array($productIds) || is_array($quantities)) {
            $maxCount = max(
                is_array($productIds) ? count($productIds) : 0,
                is_array($quantities) ? count($quantities) : 0
            );

            for ($index = 0; $index < max($maxCount, 1); $index++) {
                $items[] = [
                    'product_id' => trim((string) ((is_array($productIds) ? ($productIds[$index] ?? '') : ''))),
                    'quantity' => trim((string) ((is_array($quantities) ? ($quantities[$index] ?? '') : ''))),
                ];
            }
        }

        if ($items === []) {
            $items = [
                ['product_id' => '', 'quantity' => '1'],
            ];
        }

        return [
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_address' => trim((string) ($input['customer_address'] ?? '')),
            'customer_contact' => trim((string) ($input['customer_contact'] ?? '')),
            'payment_method' => trim((string) ($input['payment_method'] ?? 'bank')),
            'items' => $items,
        ];
    }

    /**
     * @param array<int, array{product_id: string, quantity: string}> $items
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = trim((string) ($item['product_id'] ?? ''));
            $quantity = trim((string) ($item['quantity'] ?? ''));

            if ($productId === '' && $quantity === '') {
                continue;
            }

            if (!ctype_digit($productId) || !ctype_digit($quantity)) {
                $normalized[-1] = -1;
                continue;
            }

            $productKey = (int) $productId;
            $normalized[$productKey] = ($normalized[$productKey] ?? 0) + (int) $quantity;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<int, string>
     */
    private function validateBasicFields(array $form): array
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

        if (!array_key_exists($form['payment_method'], $this->paymentOptions())) {
            $errors[] = '支払い方法が不正です。';
        }

        foreach ($form['items'] as $index => $item) {
            $productId = trim((string) ($item['product_id'] ?? ''));
            $quantity = trim((string) ($item['quantity'] ?? ''));

            if ($productId === '' && $quantity === '') {
                continue;
            }

            if ($productId === '') {
                $errors[] = sprintf('%d行目の商品を選択してください。', $index + 1);
            }

            if ($productId !== '' && !ctype_digit($productId)) {
                $errors[] = sprintf('%d行目の商品指定が不正です。', $index + 1);
            }

            if ($quantity === '') {
                $errors[] = sprintf('%d行目の数量を入力してください。', $index + 1);
                continue;
            }

            if (!ctype_digit($quantity) || (int) $quantity < 1) {
                $errors[] = sprintf('%d行目の数量は1以上の整数で入力してください。', $index + 1);
            }
        }

        return $errors;
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
    public function paymentOptions(): array
    {
        return (array) config('app.reception_order.payment_methods', [
            'bank' => '銀行振込',
            'convenience' => 'コンビニ決済',
            'cod' => '代金引換',
        ]);
    }

    public function paymentLabel(string $paymentMethod): string
    {
        $options = $this->paymentOptions();

        return $options[$paymentMethod] ?? '不明';
    }

    public function shippingFee(): int
    {
        return (int) config('app.reception_order.shipping_fee', 660);
    }

    public function paymentFee(string $paymentMethod): int
    {
        $fees = (array) config('app.reception_order.payment_fees', [
            'bank' => 0,
            'convenience' => 220,
            'cod' => 330,
        ]);

        return (int) ($fees[$paymentMethod] ?? 0);
    }

    public function paymentGuide(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank' => '銀行振込の案内と振込先情報を購入者へ通知してください。',
            'convenience' => 'コンビニ決済のお支払番号を購入者へ通知してください。',
            'cod' => '代金引換として、発送時に配達業者が代金を回収します。',
            default => '支払い方法に応じた案内を購入者へ通知してください。',
        };
    }

    /**
     * @return array<string, string>
     */
    public function paymentMethodOptions(): array
    {
        return ['' => '指定なし'] + $this->paymentLabels;
    }

    /**
     * @return array<string, string>
     */
    public function paymentStatusOptions(): array
    {
        return ['' => '指定なし'] + $this->paymentStatusLabels;
    }

    /**
     * @return array<string, string>
     */
    public function shippingStatusOptions(): array
    {
        return ['' => '指定なし'] + $this->shippingStatusLabels;
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function decorateOrder(array $order): array
    {
        $paymentMethod = (string) ($order['payment_method'] ?? '');
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        $shippingStatus = (string) ($order['shipping_status'] ?? '');

        $order['payment_method_label'] = $this->paymentLabels[$paymentMethod] ?? '不明';
        $order['payment_status_label'] = $this->paymentStatusLabels[$paymentStatus] ?? '不明';
        $order['shipping_status_label'] = $this->shippingStatusLabels[$shippingStatus] ?? '不明';

        return $order;
    }
}
