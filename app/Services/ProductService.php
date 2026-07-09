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
     *   filters: array{name: string, category: string, makers: array<int, string>, min_price: string, max_price: string},
     *   products: array<int, array<string, mixed>>,
     *   categoryOptions: array<int, array<string, mixed>>,
     *   makerOptions: array<int, array<string, mixed>>
     * }
     */
    public function searchPublicProducts(?string $name, ?string $category = null, array|string|null $makers = null, mixed $minPrice = null, mixed $maxPrice = null): array
    {
        $normalizedName = trim((string) $name);
        $normalizedCategory = trim((string) $category);
        $normalizedMakers = $this->normalizeMakerFilter($makers);
        $normalizedMinPrice = $this->normalizePriceFilter($minPrice);
        $normalizedMaxPrice = $this->normalizePriceFilter($maxPrice);
        $products = $this->products->searchForCustomer(
            $normalizedName,
            $normalizedCategory,
            $normalizedMakers,
            $normalizedMinPrice,
            $normalizedMaxPrice
        );
        $allProducts = $this->decorateProducts($this->products->listAll());
        $makerFacetProducts = $normalizedName !== ''
            || $normalizedCategory !== ''
            || $normalizedMinPrice !== null
            || $normalizedMaxPrice !== null
            ? $this->decorateProducts($this->products->searchForCustomer(
                $normalizedName,
                $normalizedCategory,
                null,
                $normalizedMinPrice,
                $normalizedMaxPrice
            ))
            : $allProducts;

        return [
            'filters' => [
                'name' => $normalizedName,
                'category' => $normalizedCategory,
                'makers' => $normalizedMakers,
                'min_price' => $normalizedMinPrice === null ? '' : (string) $normalizedMinPrice,
                'max_price' => $normalizedMaxPrice === null ? '' : (string) $normalizedMaxPrice,
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

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function buildDeliverySchedule(array $product): array
    {
        if (empty($product['is_orderable'])) {
            return [
                'supports_same_day' => false,
                'summary_type' => 'unavailable',
                'summary_text' => '現在在庫がないため、入荷までお時間をいただく場合があります。',
                'deadline_hours' => 0,
                'deadline_minutes' => 0,
                'arrival_date_label' => '',
            ];
        }

        $timezone = new DateTimeZone('Asia/Tokyo');
        $now = new DateTimeImmutable('now', $timezone);
        $cutoffToday = $now->setTime(15, 0);
        $supportsSameDay = $now <= $cutoffToday;
        $deadlineAt = $supportsSameDay ? $cutoffToday : $cutoffToday->modify('+1 day');
        $arrivalAt = $supportsSameDay ? $now : $now->modify('+1 day');
        $remainingSeconds = max(0, $deadlineAt->getTimestamp() - $now->getTimestamp());
        $deadlineHours = intdiv($remainingSeconds, 3600);
        $deadlineMinutes = intdiv($remainingSeconds % 3600, 60);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[(int) $arrivalAt->format('w')] ?? '';

        return [
            'supports_same_day' => $supportsSameDay,
            'summary_type' => 'orderable',
            'summary_text' => '',
            'deadline_hours' => $deadlineHours,
            'deadline_minutes' => $deadlineMinutes,
            'arrival_date_label' => $arrivalAt->format('Y年m月d日') . $weekday . '曜日',
        ];
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
