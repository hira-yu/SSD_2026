<?php

declare(strict_types=1);

$applicationName = (string) config('app.name', '通信販売システム');
$documentTitle = isset($pageTitle) ? $pageTitle . ' | ' . $applicationName : $applicationName;
$authUser = $_SESSION['auth'] ?? null;
$cartCount = 0;

foreach ((array) ($_SESSION[(string) config('app.online_order.cart_session_key', 'online_cart')] ?? []) as $quantity) {
    if (is_int($quantity) && $quantity > 0) {
        $cartCount += $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($documentTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="brand-block">
            <p class="eyebrow">Lightweight PHP MVC</p>
            <h1><?= e($applicationName) ?></h1>
        </div>
        <nav class="site-nav" aria-label="主要メニュー">
            <a href="/">トップ</a>
            <a href="/products">商品一覧</a>
            <a href="/cart">カート<?= $cartCount > 0 ? ' (' . e((string) $cartCount) . ')' : '' ?></a>
            <a href="/system/db-check">DB接続確認</a>
            <?php if (is_array($authUser) && !empty($authUser['authenticated'])): ?>
                <a href="<?= e((new AuthService())->destinationForRole((string) ($authUser['role'] ?? ''))) ?>">担当者トップ</a>
            <?php else: ?>
                <a href="/login">担当者ログイン</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page-content">
