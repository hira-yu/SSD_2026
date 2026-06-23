<?php

declare(strict_types=1);

class OrderItemRepository
{
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
