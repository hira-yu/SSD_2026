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
}
