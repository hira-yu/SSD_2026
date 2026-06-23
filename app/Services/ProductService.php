<?php

declare(strict_types=1);

class ProductService
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository();
    }

    /**
     * @return array{
     *   filters: array{name: string},
     *   products: array<int, array<string, mixed>>
     * }
     */
    public function searchPublicProducts(?string $name): array
    {
        $normalizedName = trim((string) $name);
        $products = $this->products->searchForCustomer($normalizedName);

        return [
            'filters' => [
                'name' => $normalizedName,
            ],
            'products' => $this->decorateProducts($products),
        ];
    }

    /**
     * @return array{
     *   filters: array{product_no: string, name: string},
     *   products: array<int, array<string, mixed>>
     * }
     */
    public function searchReceptionistProducts(?string $productNo, ?string $name): array
    {
        $normalizedProductNo = trim((string) $productNo);
        $normalizedName = trim((string) $name);
        $products = $this->products->searchForReceptionist($normalizedProductNo, $normalizedName);

        return [
            'filters' => [
                'product_no' => $normalizedProductNo,
                'name' => $normalizedName,
            ],
            'products' => $this->decorateProducts($products),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSelectableProducts(): array
    {
        return $this->decorateProducts($this->products->listAll());
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function decorateProducts(array $products): array
    {
        return array_map(function (array $product): array {
            $stockQuantity2 = (int) ($product['stock_quantity_2'] ?? 0);

            $product['is_orderable'] = $stockQuantity2 > 0;
            $product['availability_label'] = $stockQuantity2 > 0 ? '注文可能' : '在庫なし';
            $product['availability_class'] = $stockQuantity2 > 0 ? 'status-ok' : 'status-ng';

            return $product;
        }, $products);
    }
}
