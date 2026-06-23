<?php

declare(strict_types=1);

class AccountingService
{
    private OrderRepository $orders;
    private OrderItemRepository $orderItems;

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
    }

    /**
     * @return array{
     *   filters: array{order_no: string, order_date: string, customer_name: string, payment_status: string},
     *   orders: array<int, array<string, mixed>>,
     *   paymentStatusOptions: array<string, string>
     * }
     */
    public function searchOrders(array $input): array
    {
        $filters = [
            'order_no' => trim((string) ($input['order_no'] ?? '')),
            'order_date' => trim((string) ($input['order_date'] ?? '')),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'payment_status' => trim((string) ($input['payment_status'] ?? '')),
        ];

        if (!in_array($filters['payment_status'], ['', 'unpaid', 'paid'], true)) {
            $filters['payment_status'] = '';
        }

        $orders = array_map(
            fn (array $order): array => $this->decorateOrder($order),
            $this->orders->search($filters)
        );

        return [
            'filters' => $filters,
            'orders' => $orders,
            'paymentStatusOptions' => [
                '' => '指定なし',
                'unpaid' => '未払い',
                'paid' => '支払済',
            ],
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

        return [
            'order' => $this->decorateOrder($order),
            'items' => $this->orderItems->findByOrderId((int) $order['id']),
        ];
    }

    /**
     * @return array{ok: bool, message: string, order_no?: string}
     */
    public function markPaymentAsPaid(string $orderNo): array
    {
        $connection = db_connection();

        try {
            $connection->beginTransaction();
            $order = $this->orders->findByOrderNo($orderNo);

            if ($order === null) {
                $connection->rollBack();

                return [
                    'ok' => false,
                    'message' => '対象の注文が見つかりませんでした。',
                ];
            }

            if ((string) $order['payment_status'] === 'paid') {
                $connection->commit();

                return [
                    'ok' => false,
                    'message' => 'この注文はすでに支払済です。',
                    'order_no' => (string) $order['order_no'],
                ];
            }

            if (!$this->isPaymentUpdateTarget((string) $order['payment_method'])) {
                $connection->rollBack();

                return [
                    'ok' => false,
                    'message' => 'この支払い方法は今回の会計更新対象外です。',
                    'order_no' => (string) $order['order_no'],
                ];
            }

            if (!$this->orders->markAsPaid((int) $order['id'])) {
                throw new RuntimeException('支払い状態の更新に失敗しました。');
            }

            $connection->commit();

            return [
                'ok' => true,
                'message' => '支払い状態を支払済へ更新しました。',
                'order_no' => (string) $order['order_no'],
            ];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            app_log('Accounting payment update failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'order_no' => $orderNo,
            ]);

            return [
                'ok' => false,
                'message' => '支払い状態の更新に失敗しました。時間をおいて再度お試しください。',
                'order_no' => $orderNo,
            ];
        }
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
        $order['can_update_payment'] = $paymentStatus === 'unpaid' && $this->isPaymentUpdateTarget($paymentMethod);

        return $order;
    }

    private function isPaymentUpdateTarget(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['bank', 'convenience', 'cod'], true);
    }
}
