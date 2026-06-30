<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

$connection = db_connection();
$driver = (string) config('database.driver', 'sqlite');

if ($driver === 'sqlite') {
    $columns = $connection->query('PRAGMA table_info(products)')->fetchAll() ?: [];
    $hasImagePath = false;

    foreach ($columns as $column) {
        if ((string) ($column['name'] ?? '') === 'image_path') {
            $hasImagePath = true;
            break;
        }
    }

    if (!$hasImagePath) {
        $connection->exec('ALTER TABLE products ADD COLUMN image_path TEXT');
        echo "products.image_path を追加しました (sqlite)\n";
    } else {
        echo "products.image_path は既に存在します (sqlite)\n";
    }
} elseif ($driver === 'mysql') {
    $statement = $connection->query("SHOW COLUMNS FROM products LIKE 'image_path'");
    $hasImagePath = ($statement->fetch() ?: false) !== false;

    if (!$hasImagePath) {
        $connection->exec('ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL AFTER maker');
        echo "products.image_path を追加しました (mysql)\n";
    } else {
        echo "products.image_path は既に存在します (mysql)\n";
    }
} else {
    fwrite(STDERR, "未対応のDB_DRIVERです: {$driver}\n");
    exit(1);
}
