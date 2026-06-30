<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

if ((string) config('database.driver', 'sqlite') !== 'sqlite') {
    fwrite(STDERR, 'DB_DRIVER=sqlite のときだけ実行できます。' . PHP_EOL);
    exit(1);
}

$sqlitePath = resolve_path((string) config('database.sqlite.path', 'database/local.sqlite'));
$schemaPath = base_path('database/schema.sqlite.sql');
$seedPath = base_path('database/seed.sqlite.sql');

if (!is_file($schemaPath) || !is_file($seedPath)) {
    fwrite(STDERR, 'schema.sqlite.sql または seed.sqlite.sql が見つかりません。' . PHP_EOL);
    exit(1);
}

if (is_file($sqlitePath)) {
    unlink($sqlitePath);
}

$pdo = new PDO('sqlite:' . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents($schemaPath) ?: '');
$pdo->exec(file_get_contents($seedPath) ?: '');

echo 'SQLite データベースを初期化しました。' . PHP_EOL;
echo 'Path: ' . basename($sqlitePath) . PHP_EOL;
