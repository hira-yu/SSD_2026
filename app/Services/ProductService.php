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
     * @param array<int, string>|string|null $makers
     * @return array{
     *   filters: array{name: string, category: string, makers: array<int, string>, min_price: string, max_price: string, feature: string},
     *   products: array<int, array<string, mixed>>,
     *   categoryOptions: array<int, array<string, mixed>>,
     *   makerOptions: array<int, array<string, mixed>>
     * }
     */
    public function searchPublicProducts(?string $name, ?string $category = null, array|string|null $makers = null, mixed $minPrice = null, mixed $maxPrice = null, mixed $feature = null): array
    {
        $normalizedName = trim((string) $name);
        $normalizedCategory = trim((string) $category);
        $normalizedMakers = $this->normalizeMakerFilter($makers);
        $normalizedMinPrice = $this->normalizePriceFilter($minPrice);
        $normalizedMaxPrice = $this->normalizePriceFilter($maxPrice);
        $normalizedFeature = $this->normalizeFeatureFilter($feature);
        $products = $this->applyFeatureFilter($this->decorateProducts($this->products->searchForCustomer(
            $normalizedName,
            $normalizedCategory,
            $normalizedMakers,
            $normalizedMinPrice,
            $normalizedMaxPrice
        )), $normalizedFeature);
        $allProducts = $this->decorateProducts($this->products->listAll());
        $makerFacetProducts = $normalizedName !== ''
            || $normalizedCategory !== ''
            || $normalizedMinPrice !== null
            || $normalizedMaxPrice !== null
            || $normalizedFeature !== ''
            ? $this->applyFeatureFilter($this->decorateProducts($this->products->searchForCustomer(
                $normalizedName,
                $normalizedCategory,
                null,
                $normalizedMinPrice,
                $normalizedMaxPrice
            )), $normalizedFeature)
            : $allProducts;

        return [
            'filters' => [
                'name' => $normalizedName,
                'category' => $normalizedCategory,
                'makers' => $normalizedMakers,
                'min_price' => $normalizedMinPrice === null ? '' : (string) $normalizedMinPrice,
                'max_price' => $normalizedMaxPrice === null ? '' : (string) $normalizedMaxPrice,
                'feature' => $normalizedFeature,
            ],
            'products' => $products,
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
            'deliverySchedule' => $this->buildDeliverySchedule($decoratedProduct),
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
        $availability = product_availability($product);
        $regularPrice = (int) ($product['price'] ?? 0);
        $effectivePrice = product_effective_price($product);
        $product['regular_price'] = $regularPrice;
        $product['display_price'] = $effectivePrice;
        $product['price'] = $effectivePrice;
        $product['is_on_sale'] = $effectivePrice < $regularPrice;
        $product['sale_badge_label'] = $product['is_on_sale'] ? 'SALE' : '';
        $product['is_orderable'] = $availability['is_orderable'];
        $product['availability_label'] = $availability['label'];
        $product['availability_class'] = $availability['class'];
        $product['sales_period_label'] = $availability['period_label'];
        $product['image_url'] = product_image_url((string) ($product['image_path'] ?? ''));

        return $product;
    }

    private function normalizeFeatureFilter(mixed $feature): string
    {
        $normalized = trim((string) $feature);

        return in_array($normalized, ['seasonal', 'bulk', 'pc', 'limited-sale', 'new'], true)
            ? $normalized
            : '';
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function applyFeatureFilter(array $products, string $feature): array
    {
        if ($feature === '') {
            return $products;
        }

        if ($feature === 'new') {
            usort(
                $products,
                static fn (array $left, array $right): int => (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0)
            );

            return array_slice($products, 0, 24);
        }

        $filtered = array_filter($products, static function (array $product) use ($feature): bool {
            $category = (string) ($product['category'] ?? '');

            return match ($feature) {
                'seasonal' => $category === '家電',
                'bulk' => in_array($category, ['食べ物', '飲み物', '事務用品'], true),
                'pc' => in_array($category, ['電子機器', '電子部品'], true),
                'limited-sale' => !empty($product['is_on_sale'])
                    || (int) ($product['stock_quantity_1'] ?? 0) <= 10,
                default => true,
            };
        });

        return array_values($filtered);
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function buildDeliverySchedule(array $product): array
    {
        return product_delivery_schedule($product);
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

    /**
     * @param array<int, string>|string|null $makers
     * @return array<int, string>
     */
    private function normalizeMakerFilter(array|string|null $makers): array
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

    private function normalizePriceFilter(mixed $price): ?int
    {
        $price = trim((string) $price);

        if ($price === '') {
            return null;
        }

        $price = preg_replace('/[^\d]/', '', $price) ?? '';

        if ($price === '') {
            return null;
        }

        return max(0, (int) $price);
    }
}
