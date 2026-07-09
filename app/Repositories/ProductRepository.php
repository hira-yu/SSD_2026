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
                    description,
                    price,
                    category,
                    maker,
                    %s,
                    sale_price,
                    sale_starts_at,
                    sale_ends_at,
                    available_from,
                    available_until,
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
                description,
                price,
                category,
                maker,
                %s,
                sale_price,
                sale_starts_at,
                sale_ends_at,
                available_from,
                available_until,
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
                description,
                price,
                category,
                maker,
                %s,
                sale_price,
                sale_starts_at,
                sale_ends_at,
                available_from,
                available_until,
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
                    description,
                    price,
                    category,
                    maker,
                    %s,
                    sale_price,
                    sale_starts_at,
                    sale_ends_at,
                    available_from,
                    available_until,
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

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $statement = db_connection()->prepare(
            <<<'SQL'
                INSERT INTO products (
                    product_no,
                    name,
                    description,
                    price,
                    category,
                    maker,
                    image_path,
                    sale_price,
                    sale_starts_at,
                    sale_ends_at,
                    available_from,
                    available_until,
                    stock_quantity_1,
                    stock_quantity_2
                ) VALUES (
                    :product_no,
                    :name,
                    :description,
                    :price,
                    :category,
                    :maker,
                    :image_path,
                    :sale_price,
                    :sale_starts_at,
                    :sale_ends_at,
                    :available_from,
                    :available_until,
                    :stock_quantity_1,
                    :stock_quantity_2
                )
            SQL
        );
        $statement->execute($this->productWriteParams($data));

        return (int) db_connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $productId, array $data): bool
    {
        $params = $this->productWriteParams($data);
        $params['id'] = $productId;
        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE products
                SET product_no = :product_no,
                    name = :name,
                    description = :description,
                    price = :price,
                    category = :category,
                    maker = :maker,
                    image_path = :image_path,
                    sale_price = :sale_price,
                    sale_starts_at = :sale_starts_at,
                    sale_ends_at = :sale_ends_at,
                    available_from = :available_from,
                    available_until = :available_until,
                    stock_quantity_1 = :stock_quantity_1,
                    stock_quantity_2 = :stock_quantity_2,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            SQL
        );
        $statement->execute($params);

        return $statement->rowCount() >= 0;
    }

    public function receiveStock(int $productId, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        $statement = db_connection()->prepare(
            <<<'SQL'
                UPDATE products
                SET stock_quantity_1 = stock_quantity_1 + :quantity,
                    stock_quantity_2 = stock_quantity_2 + :quantity,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            SQL
        );
        $statement->execute([
            'id' => $productId,
            'quantity' => $quantity,
        ]);

        return $statement->rowCount() === 1;
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
                    description,
                    price,
                    category,
                    maker,
                    %s,
                    sale_price,
                    sale_starts_at,
                    sale_ends_at,
                    available_from,
                    available_until,
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function productWriteParams(array $data): array
    {
        return [
            'product_no' => (string) $data['product_no'],
            'name' => (string) $data['name'],
            'description' => (string) $data['description'],
            'price' => (int) $data['price'],
            'category' => (string) $data['category'],
            'maker' => (string) $data['maker'],
            'image_path' => $this->nullableString($data['image_path'] ?? null),
            'sale_price' => $data['sale_price'] === null ? null : (int) $data['sale_price'],
            'sale_starts_at' => $this->nullableString($data['sale_starts_at'] ?? null),
            'sale_ends_at' => $this->nullableString($data['sale_ends_at'] ?? null),
            'available_from' => $this->nullableString($data['available_from'] ?? null),
            'available_until' => $this->nullableString($data['available_until'] ?? null),
            'stock_quantity_1' => (int) $data['stock_quantity_1'],
            'stock_quantity_2' => (int) $data['stock_quantity_2'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
