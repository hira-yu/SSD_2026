<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

$products = new ProductRepository();
$orders = new OrderRepository();

try {
    $issues = [];
    $reservedQuantities = $orders->getUnshippedItemQuantitiesByProduct();

    foreach ($products->listAll() as $product) {
        $productId = (int) $product['id'];
        $productNo = (string) $product['product_no'];
        $productName = (string) $product['name'];
        $stockQuantity1 = (int) $product['stock_quantity_1'];
        $stockQuantity2 = (int) $product['stock_quantity_2'];
        $reservedQuantity = $reservedQuantities[$productId] ?? 0;
        $productIssues = [];

        if ($stockQuantity1 < 0) {
            $productIssues[] = '在庫数量1が0未満です。';
        }

        if ($stockQuantity2 < 0) {
            $productIssues[] = '在庫数量2が0未満です。';
        }

        if ($stockQuantity1 < $stockQuantity2) {
            $productIssues[] = '在庫数量1が在庫数量2を下回っています。';
        }

        if (($stockQuantity1 - $stockQuantity2) !== $reservedQuantity) {
            $productIssues[] = sprintf(
                '未発送引当数量との不整合があります。(期待差分: %d / 実際差分: %d)',
                $reservedQuantity,
                $stockQuantity1 - $stockQuantity2
            );
        }

        if ($productIssues === []) {
            continue;
        }

        $issues[] = sprintf(
            '%s %s: %s',
            $productNo,
            $productName,
            implode(' ', $productIssues)
        );
    }

    if ($issues === []) {
        echo '在庫整合性OK' . PHP_EOL;
        exit(0);
    }

    foreach ($issues as $issue) {
        echo $issue . PHP_EOL;
    }

    exit(1);
} catch (Throwable $exception) {
    app_log('Inventory consistency check failed', [
        'type' => $exception::class,
        'message' => $exception->getMessage(),
    ]);

    fwrite(STDERR, '在庫整合性チェックに失敗しました。' . PHP_EOL);
    exit(1);
}
