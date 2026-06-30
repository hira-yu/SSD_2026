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
     *   filters: array{name: string, category: string, maker: string},
     *   products: array<int, array<string, mixed>>,
     *   categoryOptions: array<int, array<string, mixed>>,
     *   makerOptions: array<int, array<string, mixed>>
     * }
     */
    public function searchPublicProducts(?string $name, ?string $category = null, ?string $maker = null): array
    {
        $normalizedName = trim((string) $name);
        $normalizedCategory = trim((string) $category);
        $normalizedMaker = trim((string) $maker);
        $products = $this->products->searchForCustomer($normalizedName, $normalizedCategory, $normalizedMaker);
        $allProducts = $this->decorateProducts($this->products->listAll());

        return [
            'filters' => [
                'name' => $normalizedName,
                'category' => $normalizedCategory,
                'maker' => $normalizedMaker,
            ],
            'products' => $this->decorateProducts($products),
            'categoryOptions' => $this->buildFacetOptions($allProducts, 'category'),
            'makerOptions' => $this->buildFacetOptions($allProducts, 'maker'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function homePageData(): array
    {
        $products = $this->decorateProducts($this->products->listAll());

        return [
            'featuredProducts' => array_slice($products, 0, 4),
            'newArrivalProducts' => array_slice($products, 0, 8),
            'categoryOptions' => $this->buildFacetOptions($products, 'category'),
            'makerOptions' => $this->buildFacetOptions($products, 'maker'),
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

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function buildFacetOptions(array $products, string $field): array
    {
        $counts = [];

        foreach ($products as $product) {
            $value = trim((string) ($product[$field] ?? ''));

            if ($value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        ksort($counts, SORT_NATURAL);
        $options = [];

        foreach ($counts as $value => $count) {
            $options[] = [
                'value' => $value,
                'count' => $count,
            ];
        }

        return $options;
    }
}
