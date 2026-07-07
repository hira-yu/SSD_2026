<?php

declare(strict_types=1);

class FavoriteService
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository();
    }

    /**
     * @return array<int, int>
     */
    public function favoriteProductIds(): array
    {
        $raw = $_SESSION[$this->sessionKey()] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        $ids = [];

        foreach ($raw as $value) {
            if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                continue;
            }

            $id = (int) $value;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    public function itemCount(): int
    {
        return count($this->favoriteProductIds());
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->favoriteProductIds(), true);
    }

    public function add(int $productId): string
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            throw new RuntimeException('選択した商品が見つかりませんでした。');
        }

        $ids = $this->favoriteProductIds();

        if (!in_array($productId, $ids, true)) {
            $ids[] = $productId;
            sort($ids);
            $_SESSION[$this->sessionKey()] = $ids;
        }

        return sprintf('%s をお気に入りに追加しました。', (string) $product['name']);
    }

    public function remove(int $productId): string
    {
        $product = $this->products->findById($productId);
        $ids = array_values(array_filter(
            $this->favoriteProductIds(),
            static fn (int $id): bool => $id !== $productId
        ));
        $_SESSION[$this->sessionKey()] = $ids;

        return $product === null
            ? 'お気に入りから商品を削除しました。'
            : sprintf('%s をお気に入りから削除しました。', (string) $product['name']);
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $ids = $this->favoriteProductIds();
        $rows = $this->products->findByIds($ids);
        $items = [];

        foreach ($ids as $id) {
            $product = $rows[$id] ?? null;

            if ($product === null) {
                continue;
            }

            $stockQuantity2 = (int) ($product['stock_quantity_2'] ?? 0);
            $product['is_orderable'] = $stockQuantity2 > 0;
            $product['availability_label'] = $stockQuantity2 > 0 ? '注文可能' : '在庫なし';
            $product['availability_class'] = $stockQuantity2 > 0 ? 'status-ok' : 'status-ng';
            $product['image_url'] = product_image_url((string) ($product['image_path'] ?? ''));
            $items[] = $product;
        }

        return [
            'items' => $items,
            'favoriteProductIds' => $ids,
        ];
    }

    private function sessionKey(): string
    {
        return (string) config('app.online_order.favorite_session_key', 'favorite_products');
    }
}
