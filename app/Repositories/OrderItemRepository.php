<?php

declare(strict_types=1);

class OrderItemRepository
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function createMany(int $orderId, array $items): void
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_no,
                    product_name,
                    unit_price,
                    quantity,
                    line_total
                ) VALUES (
                    :order_id,
                    :product_id,
                    :product_no,
                    :product_name,
                    :unit_price,
                    :quantity,
                    :line_total
                )
            SQL
        );

        foreach ($items as $item) {
            $statement->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_no' => $item['product_no'],
                'product_name' => $item['product_name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderId(int $orderId): array
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                SELECT
                    product_id,
                    product_no,
                    product_name,
                    unit_price,
                    quantity,
                    line_total
                FROM order_items
                WHERE order_id = :order_id
                ORDER BY id ASC
            SQL
        );
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll() ?: [];
    }
}
