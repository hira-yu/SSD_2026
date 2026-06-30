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

            if (!$this->products->decreaseStockQuantity2($productId, $quantity)) {
                $this->throwReserveFailure($productId, $quantity, $product);
            }
        }
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

        $reservedQuantities = $this->orders->getUnshippedItemQuantitiesByProduct();

        foreach (array_keys($normalizedItems) as $productId) {
            $stockPair = $this->products->getStockPair($productId);

            if ($stockPair === null) {
                throw new RuntimeException('対象商品の在庫確認に失敗しました。');
            }

            $expectedReservedQuantity = $reservedQuantities[$productId] ?? 0;
            $actualDifference = (int) $stockPair['stock_quantity_1'] - (int) $stockPair['stock_quantity_2'];

            if ($actualDifference === $expectedReservedQuantity) {
                continue;
            }

            $productName = (string) ($stockPair['name'] ?? '対象商品');

            if ($expectedReservedQuantity === 0) {
                throw new RuntimeException(sprintf(
                    '%s の在庫数量1と在庫数量2が一致しないため、発送更新を中止しました。',
                    $productName
                ));
            }

            throw new RuntimeException(sprintf(
                '%s の在庫整合性が崩れたため、発送更新を中止しました。',
                $productName
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
