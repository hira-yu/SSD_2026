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
    public function searchForCustomer(?string $name = null, ?string $category = null, ?string $maker = null): array
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

        if ($category !== null && $category !== '') {
            $conditions[] = 'category = :category';
            $params['category'] = $category;
        }

        if ($maker !== null && $maker !== '') {
            $conditions[] = 'maker = :maker';
            $params['maker'] = $maker;
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

    public function findById(int $productId): ?array
    {
        return $this->findByIds([$productId])[$productId] ?? null;
    }

    public function findByIdForUpdate(int $productId): ?array
    {
        return $this->findByIdsForUpdate([$productId])[$productId] ?? null;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findByIdsForUpdate(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf(
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
                ORDER BY id ASC
            SQL,
            $placeholders
        );

        if ($this->usesRowLevelLocking()) {
            $sql .= ' FOR UPDATE';
        }

        $statement = db_connection()->prepare($sql);
        $statement->execute($ids);
        $rows = $statement->fetchAll() ?: [];
        $products = [];

        foreach ($rows as $row) {
            $products[(int) $row['id']] = $row;
        }

        return $products;
    }

    public function decreaseStockQuantity2(int $productId, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

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

    public function decrementStockQuantity2(int $productId, int $quantity): bool
    {
        return $this->decreaseStockQuantity2($productId, $quantity);
    }

    public function decreaseStockQuantity1(int $productId, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

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

    public function decrementStockQuantity1(int $productId, int $quantity): bool
    {
        return $this->decreaseStockQuantity1($productId, $quantity);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStockPair(int $productId): ?array
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                SELECT
                    id,
                    product_no,
                    name,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                WHERE id = :id
                LIMIT 1
            SQL
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch();

        return is_array($product) ? $product : null;
    }

    public function assertStockQuantity2Available(int $productId, int $quantity): bool
    {
        $product = $this->getStockPair($productId);

        return $product !== null && (int) $product['stock_quantity_2'] >= $quantity;
    }

    public function assertStockQuantitiesEqual(int $productId): bool
    {
        $product = $this->getStockPair($productId);

        return $product !== null
            && (int) $product['stock_quantity_1'] === (int) $product['stock_quantity_2'];
    }

    private function usesRowLevelLocking(): bool
    {
        return (string) config('database.driver', 'sqlite') === 'mysql';
    }
}
