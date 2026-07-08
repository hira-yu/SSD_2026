<?php

declare(strict_types=1);

class ProductRepository
{
    private ?bool $hasImagePathColumn = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $statement = db_connection()->query(
            sprintf(
                <<<'SQL'
                SELECT
                    id,
                    product_no,
                    name,
                    price,
                    category,
                    maker,
                    %s,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                ORDER BY product_no ASC
            SQL,
                $this->imagePathSelectExpression()
            )
        );

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int, string>|string|null $makers
     * @return array<int, array<string, mixed>>
     */
    public function searchForCustomer(?string $name = null, ?string $category = null, array|string|null $makers = null, ?int $minPrice = null, ?int $maxPrice = null): array
    {
        $sql = sprintf(
            <<<'SQL'
            SELECT
                id,
                product_no,
                name,
                price,
                category,
                maker,
                %s,
                stock_quantity_1,
                stock_quantity_2
            FROM products
        SQL,
            $this->imagePathSelectExpression()
        );

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

        $makerValues = $this->normalizeMakers($makers);

        if ($makerValues !== []) {
            $makerPlaceholders = [];

            foreach ($makerValues as $index => $maker) {
                $placeholder = 'maker_' . $index;
                $makerPlaceholders[] = ':' . $placeholder;
                $params[$placeholder] = $maker;
            }

            $conditions[] = 'maker IN (' . implode(', ', $makerPlaceholders) . ')';
        }

        if ($minPrice !== null) {
            $conditions[] = 'price >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice !== null) {
            $conditions[] = 'price <= :max_price';
            $params['max_price'] = $maxPrice;
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
     * @param array<int, string>|string|null $makers
     * @return array<int, string>
     */
    private function normalizeMakers(array|string|null $makers): array
    {
        if ($makers === null || $makers === '') {
            return [];
        }

        $values = is_array($makers) ? $makers : [$makers];
        $normalized = [];

        foreach ($values as $maker) {
            $maker = trim((string) $maker);

            if ($maker === '') {
                continue;
            }

            $normalized[] = $maker;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchForReceptionist(?string $productNo = null, ?string $name = null): array
    {
        $sql = sprintf(
            <<<'SQL'
            SELECT
                id,
                product_no,
                name,
                price,
                category,
                maker,
                %s,
                stock_quantity_1,
                stock_quantity_2
            FROM products
        SQL,
            $this->imagePathSelectExpression()
        );

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
                    %s,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                WHERE id IN (%s)
                ORDER BY product_no ASC
            SQL,
            $this->imagePathSelectExpression(),
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
                    %s,
                    stock_quantity_1,
                    stock_quantity_2
                FROM products
                WHERE id IN (%s)
                ORDER BY id ASC
            SQL,
            $this->imagePathSelectExpression(),
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

    private function imagePathSelectExpression(): string
    {
        return $this->hasImagePathColumn() ? 'image_path' : "'' AS image_path";
    }

    private function hasImagePathColumn(): bool
    {
        if ($this->hasImagePathColumn !== null) {
            return $this->hasImagePathColumn;
        }

        $driver = (string) config('database.driver', 'sqlite');
        $connection = db_connection();

        if ($driver === 'sqlite') {
            $columns = $connection->query('PRAGMA table_info(products)')->fetchAll() ?: [];

            foreach ($columns as $column) {
                if ((string) ($column['name'] ?? '') === 'image_path') {
                    return $this->hasImagePathColumn = true;
                }
            }

            return $this->hasImagePathColumn = false;
        }

        if ($driver === 'mysql') {
            $statement = $connection->query("SHOW COLUMNS FROM products LIKE 'image_path'");
            return $this->hasImagePathColumn = (($statement->fetch() ?: false) !== false);
        }

        return $this->hasImagePathColumn = false;
    }
}
