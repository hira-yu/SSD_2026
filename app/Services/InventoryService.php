<?php

declare(strict_types=1);

class InventoryService
{
    private ProductRepository $products;
    private OrderRepository $orders;

    public function __construct()
    {
        $this->products = new ProductRepository();
        $this->orders = new OrderRepository();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function reserveForOrder(array $items): void
    {
        $normalizedItems = $this->normalizeItems($items);
        $products = $this->assertOrderReservable($items);

        foreach ($normalizedItems as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                throw new RuntimeException('選択した商品が見つかりません。');
            }

            if (!$this->products->decreaseStockQuantity2($productId, $quantity)) {
                $this->throwReserveFailure($productId, $quantity, $product);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function assertOrderReservable(array $items): array
    {
        $normalizedItems = $this->normalizeItems($items);
        $products = $this->products->findByIdsForUpdate(array_keys($normalizedItems));

        foreach ($normalizedItems as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                throw new RuntimeException('選択した商品が見つかりません。');
            }

            if (!$this->products->assertStockQuantity2Available($productId, $quantity)) {
                throw new RuntimeException(sprintf(
                    '%s の在庫数量2が不足しているため、注文を登録できません。',
                    (string) $product['name']
                ));
            }
        }

        return $products;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function finalizeShipment(array $items): void
    {
        $normalizedItems = $this->normalizeItems($items);
        $products = $this->products->findByIdsForUpdate(array_keys($normalizedItems));

        foreach ($normalizedItems as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                throw new RuntimeException('対象商品の在庫確認に失敗しました。');
            }

            if (!$this->products->decreaseStockQuantity1($productId, $quantity)) {
                $this->throwShipmentFailure($productId, $quantity, $product);
            }
        }

        foreach (array_keys($normalizedItems) as $productId) {
            $this->assertProductStockMatchesUnshippedReservations($productId);
        }
    }

    public function assertProductStockMatchesUnshippedReservations(int $productId): void
    {
        $stockPair = $this->products->getStockPair($productId);

        if ($stockPair === null) {
            throw new RuntimeException('対象商品の在庫確認に失敗しました。');
        }

        $stockQuantity1 = (int) $stockPair['stock_quantity_1'];
        $stockQuantity2 = (int) $stockPair['stock_quantity_2'];
        $reservedQuantities = $this->orders->getUnshippedItemQuantitiesByProduct();
        $expectedReservedQuantity = $reservedQuantities[$productId] ?? 0;
        $actualDifference = $stockQuantity1 - $stockQuantity2;

        if (
            $stockQuantity1 < 0
            || $stockQuantity2 < 0
            || $stockQuantity1 < $stockQuantity2
            || $actualDifference !== $expectedReservedQuantity
        ) {
            error_log('productId=' . $productId);
            error_log('stock1=' . $stockQuantity1);
            error_log('stock2=' . $stockQuantity2);
            error_log('actualDifference=' . $actualDifference);
            error_log('expectedReservedQuantity=' . $expectedReservedQuantity);
            throw new RuntimeException(sprintf(
                '%s の在庫引当数量との整合性が確認できないため、発送更新を中止しました。',
                (string) ($stockPair['name'] ?? '対象商品')
            ));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        $normalizedItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId < 1 || $quantity < 1) {
                throw new RuntimeException('在庫更新対象の商品情報が不正です。');
            }

            $normalizedItems[$productId] = ($normalizedItems[$productId] ?? 0) + $quantity;
        }

        if ($normalizedItems === []) {
            throw new RuntimeException('在庫更新対象の商品がありません。');
        }

        ksort($normalizedItems);

        return $normalizedItems;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function throwReserveFailure(int $productId, int $quantity, array $product): never
    {
        $stockPair = $this->products->getStockPair($productId);
        $productName = (string) (($stockPair['name'] ?? null) ?: ($product['name'] ?? $product['product_name'] ?? '対象商品'));

        if ($stockPair === null) {
            throw new RuntimeException('対象商品の在庫確認に失敗しました。');
        }

        if ((int) $stockPair['stock_quantity_2'] < $quantity) {
            throw new RuntimeException(sprintf(
                '%s の在庫数量2が不足しているため、注文を登録できません。',
                $productName
            ));
        }

        throw new RuntimeException(sprintf(
            '%s の在庫更新が競合したため、注文を登録できませんでした。時間をおいて再度お試しください。',
            $productName
        ));
    }

    /**
     * @param array<string, mixed> $product
     */
    private function throwShipmentFailure(int $productId, int $quantity, array $product): never
    {
        $stockPair = $this->products->getStockPair($productId);
        $productName = (string) (($stockPair['name'] ?? null) ?: ($product['name'] ?? $product['product_name'] ?? '対象商品'));

        if ($stockPair === null) {
            throw new RuntimeException('対象商品の在庫確認に失敗しました。');
        }

        if ((int) $stockPair['stock_quantity_1'] < $quantity) {
            throw new RuntimeException(sprintf(
                '%s の在庫数量1が不足しているため、発送更新を中止しました。',
                $productName
            ));
        }

        throw new RuntimeException(sprintf(
            '%s の在庫更新が競合したため、発送更新を中止しました。時間をおいて再度お試しください。',
            $productName
        ));
    }
}
