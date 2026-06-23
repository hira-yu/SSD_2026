<?php

declare(strict_types=1);

class ProductRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $statement = db_connection()->query(
            <<<'SQL'
                SELECT
                    id,
                    product_no,
                    name,
                    price,
                    category,
                    maker,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                ORDER BY product_no ASC
            SQL
        );

        return $statement->fetchAll() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchForCustomer(?string $name = null): array
    {
        $sql = <<<'SQL'
            SELECT
                id,
                product_no,
                name,
                price,
                category,
                maker,
                stock_quantity_1,
                stock_quantity_2
            FROM products
        SQL;

        $conditions = [];
        $params = [];

        if ($name !== null && $name !== '') {
            $conditions[] = 'name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY product_no ASC';

        $statement = db_connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchForReceptionist(?string $productNo = null, ?string $name = null): array
    {
        $sql = <<<'SQL'
            SELECT
                id,
                product_no,
                name,
                price,
                category,
                maker,
                stock_quantity_1,
                stock_quantity_2
            FROM products
        SQL;

        $conditions = [];
        $params = [];

        if ($productNo !== null && $productNo !== '') {
            $conditions[] = 'product_no LIKE :product_no';
            $params['product_no'] = '%' . $productNo . '%';
        }

        if ($name !== null && $name !== '') {
            $conditions[] = 'name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY product_no ASC';

        $statement = db_connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement = db_connection()->prepare(sprintf(
            <<<'SQL'
                SELECT
                    id,
                    product_no,
                    name,
                    price,
                    category,
                    maker,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                WHERE id IN (%s)
                ORDER BY product_no ASC
            SQL,
            $placeholders
        ));
        $statement->execute($ids);
        $rows = $statement->fetchAll() ?: [];
        $products = [];

        foreach ($rows as $row) {
            $products[(int) $row['id']] = $row;
        }

        return $products;
    }

    public function decrementStockQuantity2(int $productId, int $quantity): bool
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE products
                SET stock_quantity_2 = stock_quantity_2 - :quantity,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND stock_quantity_2 >= :quantity
            SQL
        );
        $statement->execute([
            'id' => $productId,
            'quantity' => $quantity,
        ]);

        return $statement->rowCount() === 1;
    }

    public function decrementStockQuantity1(int $productId, int $quantity): bool
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE products
                SET stock_quantity_1 = stock_quantity_1 - :quantity,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND stock_quantity_1 >= :quantity
            SQL
        );
        $statement->execute([
            'id' => $productId,
            'quantity' => $quantity,
        ]);

        return $statement->rowCount() === 1;
    }
}
