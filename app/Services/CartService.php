<?php

declare(strict_types=1);

class CartService
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository();
    }

    public function addItem(int $productId, int $quantity): string
    {
        if ($quantity < 1) {
            throw new RuntimeException('数量は1以上の整数で入力してください。');
        }

        $product = $this->products->findById($productId);

        if ($product === null) {
            throw new RuntimeException('選択した商品が見つかりませんでした。');
        }

        if ((int) $product['stock_quantity_2'] < 1) {
            throw new RuntimeException(sprintf('%s は在庫切れのため追加できません。', (string) $product['name']));
        }

        $cart = $this->cartQuantities();
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
        $this->storeCart($cart);

        if ($cart[$productId] > (int) $product['stock_quantity_2']) {
            return sprintf(
                '%s をカートに追加しました。在庫数量2を超えているため、注文確定前に数量をご確認ください。',
                (string) $product['name']
            );
        }

        return sprintf('%s をカートに追加しました。', (string) $product['name']);
    }

    public function updateItem(int $productId, int $quantity): string
    {
        $cart = $this->cartQuantities();

        if (!array_key_exists($productId, $cart)) {
            throw new RuntimeException('更新対象の商品がカートにありません。');
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
            $this->storeCart($cart);

            return '商品をカートから削除しました。';
        }

        $product = $this->products->findById($productId);

        if ($product === null) {
            unset($cart[$productId]);
            $this->storeCart($cart);
            throw new RuntimeException('商品情報を確認できなかったため、カートから削除しました。');
        }

        $cart[$productId] = $quantity;
        $this->storeCart($cart);

        if ($quantity > (int) $product['stock_quantity_2']) {
            return sprintf(
                '%s の数量を更新しました。在庫数量2を超えているため、注文確定前に数量をご確認ください。',
                (string) $product['name']
            );
        }

        return sprintf('%s の数量を更新しました。', (string) $product['name']);
    }

    public function removeItem(int $productId): string
    {
        $cart = $this->cartQuantities();

        if (!array_key_exists($productId, $cart)) {
            return '商品はすでにカートから削除されています。';
        }

        unset($cart[$productId]);
        $this->storeCart($cart);

        return '商品をカートから削除しました。';
    }

    public function clear(): void
    {
        unset($_SESSION[$this->cartSessionKey()]);
    }

    /**
     * @return array<int, int>
     */
    public function cartQuantities(): array
    {
        $rawCart = $_SESSION[$this->cartSessionKey()] ?? [];

        if (!is_array($rawCart)) {
            return [];
        }

        $cart = [];

        foreach ($rawCart as $productId => $quantity) {
            if (!ctype_digit((string) $productId) || !is_int($quantity)) {
                continue;
            }

            if ($quantity < 1) {
                continue;
            }

            $cart[(int) $productId] = $quantity;
        }

        ksort($cart);

        return $cart;
    }

    public function itemCount(): int
    {
        return array_sum($this->cartQuantities());
    }

    public function isEmpty(): bool
    {
        return $this->cartQuantities() === [];
    }

    public function cartHash(): string
    {
        return hash('sha256', json_encode($this->cartQuantities(), JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function cartViewData(): array
    {
        $cart = $this->cartQuantities();

        if ($cart === []) {
            return [
                'items' => [],
                'subtotal' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'item_count' => 0,
                'warnings' => [],
                'missing_product_count' => 0,
            ];
        }

        $products = $this->products->findByIds(array_keys($cart));
        $items = [];
        $warnings = [];
        $missingProductCount = 0;
        $subtotal = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                $warnings[] = sprintf('商品ID %d は存在しないため、カート内容をご確認ください。', $productId);
                $missingProductCount++;
                continue;
            }

            $lineTotal = (int) $product['price'] * $quantity;
            $subtotal += $lineTotal;
            $stockQuantity2 = (int) $product['stock_quantity_2'];
            $warning = null;

            if ($quantity > $stockQuantity2) {
                $warning = sprintf('在庫数量2は %d 個です。注文確定前に数量を調整してください。', $stockQuantity2);
                $warnings[] = (string) $product['name'] . ': ' . $warning;
            }

            $items[] = [
                'product_id' => (int) $product['id'],
                'product_no' => (string) $product['product_no'],
                'product_name' => (string) $product['name'],
                'unit_price' => (int) $product['price'],
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'stock_quantity_2' => $stockQuantity2,
                'warning' => $warning,
            ];
        }

        $shippingFee = $items === [] ? 0 : $this->shippingFee();

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total_amount' => $subtotal + $shippingFee,
            'item_count' => array_sum($cart),
            'warnings' => array_values(array_unique($warnings)),
            'missing_product_count' => $missingProductCount,
        ];
    }

    /**
     * @param array<int, int> $cart
     */
    private function storeCart(array $cart): void
    {
        $normalized = [];

        foreach ($cart as $productId => $quantity) {
            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $normalized[(string) $productId] = $quantity;
        }

        if ($normalized === []) {
            unset($_SESSION[$this->cartSessionKey()]);
            return;
        }

        ksort($normalized);
        $_SESSION[$this->cartSessionKey()] = $normalized;
    }

    private function shippingFee(): int
    {
        return (int) config('app.online_order.shipping_fee', 660);
    }

    private function cartSessionKey(): string
    {
        return (string) config('app.online_order.cart_session_key', 'online_cart');
    }
}
