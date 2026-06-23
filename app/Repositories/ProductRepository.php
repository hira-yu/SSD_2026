<?php

declare(strict_types=1);

class ProductRepository
{
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
}
