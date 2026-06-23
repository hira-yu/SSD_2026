<?php

declare(strict_types=1);

class OrderRepository
{
    public function findLatestOrderNoByDate(string $datePrefix): ?string
    {
        $statement = db_connection()->prepare(
            'SELECT order_no FROM orders WHERE order_no LIKE :prefix ORDER BY order_no DESC LIMIT 1'
        );
        $statement->execute([
            'prefix' => 'ORD' . $datePrefix . '%',
        ]);

        $orderNo = $statement->fetchColumn();

        return is_string($orderNo) ? $orderNo : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                INSERT INTO orders (
                    order_no,
                    order_date,
                    customer_name,
                    customer_address,
                    customer_contact,
                    order_type,
                    payment_method,
                    payment_status,
                    shipping_status,
                    subtotal,
                    fee,
                    shipping_fee,
                    total_amount
                ) VALUES (
                    :order_no,
                    :order_date,
                    :customer_name,
                    :customer_address,
                    :customer_contact,
                    :order_type,
                    :payment_method,
                    :payment_status,
                    :shipping_status,
                    :subtotal,
                    :fee,
                    :shipping_fee,
                    :total_amount
                )
            SQL
        );

        $statement->execute([
            'order_no' => $data['order_no'],
            'order_date' => $data['order_date'],
            'customer_name' => $data['customer_name'],
            'customer_address' => $data['customer_address'],
            'customer_contact' => $data['customer_contact'],
            'order_type' => $data['order_type'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'shipping_status' => $data['shipping_status'],
            'subtotal' => $data['subtotal'],
            'fee' => $data['fee'],
            'shipping_fee' => $data['shipping_fee'],
            'total_amount' => $data['total_amount'],
        ]);

        return (int) db_connection()->lastInsertId();
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                SELECT
                    id,
                    order_no,
                    order_date,
                    customer_name,
                    customer_address,
                    customer_contact,
                    order_type,
                    payment_method,
                    payment_status,
                    shipping_status,
                    subtotal,
                    fee,
                    shipping_fee,
                    total_amount,
                    created_at,
                    updated_at
                FROM orders
                WHERE order_no = :order_no
                LIMIT 1
            SQL
        );
        $statement->execute(['order_no' => $orderNo]);
        $order = $statement->fetch();

        return is_array($order) ? $order : null;
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $filters): array
    {
        $sql = <<<'SQL'
            SELECT
                id,
                order_no,
                order_date,
                customer_name,
                customer_address,
                customer_contact,
                order_type,
                payment_method,
                payment_status,
                shipping_status,
                subtotal,
                fee,
                shipping_fee,
                total_amount,
                created_at,
                updated_at
            FROM orders
        SQL;

        $conditions = [];
        $params = [];

        if ($filters['order_no'] !== '') {
            $conditions[] = 'order_no LIKE :order_no';
            $params['order_no'] = '%' . $filters['order_no'] . '%';
        }

        if ($filters['order_date'] !== '') {
            $conditions[] = 'order_date LIKE :order_date';
            $params['order_date'] = $filters['order_date'] . '%';
        }

        if ($filters['customer_name'] !== '') {
            $conditions[] = 'customer_name LIKE :customer_name';
            $params['customer_name'] = '%' . $filters['customer_name'] . '%';
        }

        if (in_array($filters['payment_status'], ['unpaid', 'paid'], true)) {
            $conditions[] = 'payment_status = :payment_status';
            $params['payment_status'] = $filters['payment_status'];
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY order_date DESC, id DESC';

        $statement = db_connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function markAsPaid(int $orderId): bool
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE orders
                SET payment_status = :payment_status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND payment_status = :current_status
            SQL
        );
        $statement->execute([
            'id' => $orderId,
            'payment_status' => 'paid',
            'current_status' => 'unpaid',
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUnshippedOrders(): array
    {
        $statement = db_connection()->query(
            <<<'SQL'
                SELECT
                    id,
                    order_no,
                    order_date,
                    customer_name,
                    customer_address,
                    customer_contact,
                    order_type,
                    payment_method,
                    payment_status,
                    shipping_status,
                    subtotal,
                    fee,
                    shipping_fee,
                    total_amount,
                    created_at,
                    updated_at
                FROM orders
                WHERE shipping_status = 'unshipped'
                ORDER BY order_date ASC, id ASC
            SQL
        );

        return $statement->fetchAll() ?: [];
    }

    public function markAsShipped(int $orderId): bool
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE orders
                SET shipping_status = :shipping_status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND shipping_status = :current_status
            SQL
        );
        $statement->execute([
            'id' => $orderId,
            'shipping_status' => 'shipped',
            'current_status' => 'unshipped',
        ]);

        return $statement->rowCount() === 1;
    }
}
