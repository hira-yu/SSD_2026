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
        $makerFacetProducts = $normalizedName !== '' || $normalizedCategory !== ''
            ? $this->decorateProducts($this->products->searchForCustomer($normalizedName, $normalizedCategory, null))
            : $allProducts;

        return [
            'filters' => [
                'name' => $normalizedName,
                'category' => $normalizedCategory,
                'maker' => $normalizedMaker,
            ],
            'products' => $this->decorateProducts($products),
            'categoryOptions' => $this->buildFacetOptions($allProducts, 'category'),
            'makerOptions' => $this->buildFacetOptions($makerFacetProducts, 'maker'),
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
     * @return array<string, mixed>|null
     */
    public function productDetailData(int $productId): ?array
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            return null;
        }

        $decoratedProduct = $this->decorateProduct($product);
        $allProducts = $this->decorateProducts($this->products->listAll());
        $relatedProducts = [];
        $sameMakerProducts = [];

        foreach ($allProducts as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $productId) {
                continue;
            }

            if (
                $relatedProducts === []
                || (
                    (string) ($candidate['category'] ?? '') === (string) ($decoratedProduct['category'] ?? '')
                    && count($relatedProducts) < 8
                )
            ) {
                if ((string) ($candidate['category'] ?? '') === (string) ($decoratedProduct['category'] ?? '')) {
                    $relatedProducts[] = $candidate;
                }
            }

            if (
                (string) ($candidate['maker'] ?? '') === (string) ($decoratedProduct['maker'] ?? '')
                && count($sameMakerProducts) < 8
            ) {
                $sameMakerProducts[] = $candidate;
            }
        }

        if ($relatedProducts === []) {
            $relatedProducts = array_slice(array_values(array_filter(
                $allProducts,
                static fn (array $candidate): bool => (int) ($candidate['id'] ?? 0) !== $productId
            )), 0, 8);
        }

        return [
            'product' => $decoratedProduct,
            'relatedProducts' => array_slice($relatedProducts, 0, 8),
            'sameMakerProducts' => array_slice($sameMakerProducts, 0, 8),
            'categoryOptions' => $this->buildFacetOptions($allProducts, 'category'),
            'makerOptions' => $this->buildFacetOptions($allProducts, 'maker'),
            'deliverySummary' => $decoratedProduct['is_orderable']
                ? '在庫があるため、通常 2-4 日でお届け予定です。'
                : '現在在庫がないため、入荷までお時間をいただく場合があります。',
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
        return array_map(fn (array $product): array => $this->decorateProduct($product), $products);
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function decorateProduct(array $product): array
    {
        $stockQuantity2 = (int) ($product['stock_quantity_2'] ?? 0);
        $product['is_orderable'] = $stockQuantity2 > 0;
        $product['availability_label'] = $stockQuantity2 > 0 ? '注文可能' : '在庫なし';
        $product['availability_class'] = $stockQuantity2 > 0 ? 'status-ok' : 'status-ng';
        $product['image_url'] = product_image_url((string) ($product['image_path'] ?? ''));

        return $product;
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
