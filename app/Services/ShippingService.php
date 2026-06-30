<?php

declare(strict_types=1);

class ShippingService
{
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
        $this->orders = new OrderRepository();
        $this->orderItems = new OrderItemRepository();
        $this->inventory = new InventoryService();
    }

    /**
     * @return array{shippable: array<int, array<string, mixed>>, waiting: array<int, array<string, mixed>>}
     */
    public function listOrders(): array
    {
        $orders = array_map(fn (array $order): array => $this->decorateOrder($order), $this->orders->listUnshippedOrders());

        return [
            'shippable' => array_values(array_filter($orders, fn (array $order): bool => ($order['shipping_eligibility']['status'] ?? '') === 'shippable')),
            'waiting' => array_values(array_filter($orders, fn (array $order): bool => ($order['shipping_eligibility']['status'] ?? '') !== 'shippable')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderDetail(string $orderNo): ?array
    {
        $order = $this->orders->findByOrderNo($orderNo);

        if ($order === null) {
            return null;
        }

        $decorated = $this->decorateOrder($order);
        $items = $this->orderItems->findByOrderId((int) $order['id']);

        return [
            'order' => $decorated,
            'items' => $items,
            'deliverySlipTitle' => '納品書情報',
            'showInvoice' => (string) $order['payment_method'] === 'cod',
            'invoiceTitle' => '請求書情報',
        ];
    }

    /**
     * @return array{ok: bool, message: string, order_no?: string}
     */
    public function markOrderAsShipped(string $orderNo): array
    {
        $connection = db_connection();

        try {
            $connection->beginTransaction();
            $order = $this->orders->findByOrderNoForUpdate($orderNo);

            if ($order === null) {
                $connection->rollBack();

                return [
                    'ok' => false,
                    'message' => '対象の注文が見つかりませんでした。',
                ];
            }

            $eligibility = $this->getShippingEligibility($order);

            if ((string) $order['shipping_status'] === 'shipped') {
                $connection->commit();

                return [
                    'ok' => false,
                    'message' => 'この注文はすでに発送済です。',
                    'order_no' => (string) $order['order_no'],
                ];
            }

            if (($eligibility['status'] ?? '') !== 'shippable') {
                $connection->rollBack();

                return [
                    'ok' => false,
                    'message' => (string) ($eligibility['message'] ?? 'この注文は発送対象ではありません。'),
                    'order_no' => (string) $order['order_no'],
                ];
            }

            $items = $this->orderItems->findByOrderId((int) $order['id']);

            if ($items === []) {
                throw new RuntimeException('発送対象の注文明細が見つかりませんでした。');
            }

            if (!$this->orders->markAsShipped((int) $order['id'])) {
                throw new RuntimeException('発送状態の更新に失敗しました。');
            }

            $this->inventory->finalizeShipment($items);

            $connection->commit();

            return [
                'ok' => true,
                'message' => '発送状態を発送済へ更新しました。',
                'order_no' => (string) $order['order_no'],
            ];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            app_log('Shipping update failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'order_no' => $orderNo,
            ]);

            return [
                'ok' => false,
                'message' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : '発送状態の更新に失敗しました。時間をおいて再度お試しください。',
                'order_no' => $orderNo,
            ];
        }
    }

    /**
     * @param array<string, mixed> $order
     * @return array{status: string, label: string, message: string}
     */
    public function getShippingEligibility(array $order): array
    {
        $paymentMethod = (string) ($order['payment_method'] ?? '');
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        $shippingStatus = (string) ($order['shipping_status'] ?? '');

        if ($shippingStatus === 'shipped') {
            return [
                'status' => 'shipped',
                'label' => '発送済',
                'message' => 'この注文はすでに発送済です。',
            ];
        }

        return match ($paymentMethod) {
            'bank', 'convenience', 'credit' => $paymentStatus === 'paid'
                ? ['status' => 'shippable', 'label' => '発送可能', 'message' => '発送できます。']
                : ['status' => 'waiting_payment', 'label' => '支払い待ち', 'message' => '入金確認後に発送可能になります。'],
            'cod' => ['status' => 'shippable', 'label' => '発送可能', 'message' => '代金引換のため発送できます。'],
            default => ['status' => 'not_supported', 'label' => '発送対象外', 'message' => 'この注文は発送対象外です。'],
        };
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
        $order['shipping_eligibility'] = $this->getShippingEligibility($order);

        return $order;
    }
}
