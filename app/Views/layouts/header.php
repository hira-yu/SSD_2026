<?php

declare(strict_types=1);

$applicationName = (string) config('app.name', 'IPUT EC');
$adminApplicationName = (string) config('app.admin_name', 'IPUT EC 管理画面');
$authUser = $_SESSION['auth'] ?? null;
$currentPath = current_path();
$isAdminArea = $currentPath === '/login' || str_starts_with($currentPath, '/staff');
$isCustomerArea = !$isAdminArea;
$bodyClass = $isAdminArea ? 'admin-shell' : 'customer-shell';
$serviceName = (string) config('app.customer_ui.service_name', 'IPUT EC');
$titleBase = $isAdminArea ? $adminApplicationName : $serviceName;
$documentTitle = isset($pageTitle) ? $pageTitle . ' | ' . $titleBase : $titleBase;
$serviceTagline = (string) config('app.customer_ui.tagline', '');
$searchQuery = trim((string) ($_GET['name'] ?? ''));
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
<body class="<?= e($bodyClass) ?>">
<?php if ($isCustomerArea): ?>
    <header class="site-header customer-header">
        <div class="customer-topbar">
            <div class="container customer-topbar-inner">
                <p><?= e($serviceTagline) ?></p>
                <div class="customer-topbar-links">
                    <a href="/products">商品一覧</a>
                    <a href="/cart">カート<?= $cartCount > 0 ? ' (' . e((string) $cartCount) . ')' : '' ?></a>
                    <?php if (is_array($authUser) && !empty($authUser['authenticated'])): ?>
                        <a href="<?= e((new AuthService())->destinationForRole((string) ($authUser['role'] ?? ''))) ?>">担当者トップ</a>
                    <?php else: ?>
                        <a href="/login">担当者ログイン</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="container customer-brand-row">
            <div class="brand-block">
                <p class="eyebrow">Campus Demo Store</p>
                <h1><?= e($serviceName) ?></h1>
            </div>
            <form class="global-search-form" method="get" action="/products" role="search">
                <input type="text" name="name" value="<?= e($searchQuery) ?>" placeholder="商品名・キーワードで検索">
                <button class="button-link button-submit" type="submit">検索</button>
            </form>
            <a class="customer-cart-link" href="/cart">
                <span>カート</span>
                <strong><?= e((string) $cartCount) ?></strong>
            </a>
        </div>
        <div class="customer-nav-wrap">
            <div class="container">
                <nav class="site-nav customer-nav" aria-label="主要メニュー">
                    <a href="/">トップ</a>
                    <a href="/products">商品一覧・検索</a>
                    <a href="/checkout">ご注文手続き</a>
                    <a href="/system/db-check">動作確認</a>
                </nav>
            </div>
        </div>
    </header>
<?php else: ?>
    <header class="site-header admin-header">
        <div class="admin-header-bar">
            <div class="container admin-header-inner">
                <div class="brand-block admin-brand-block">
                    <p class="eyebrow">Operations Console</p>
                    <h1><?= e($adminApplicationName) ?></h1>
                </div>
                <nav class="site-nav admin-nav" aria-label="管理メニュー">
                    <a href="/">購入者画面</a>
                    <a href="/system/db-check">DB接続確認</a>
                    <?php if (is_array($authUser) && !empty($authUser['authenticated'])): ?>
                        <a href="<?= e((new AuthService())->destinationForRole((string) ($authUser['role'] ?? ''))) ?>">担当者トップ</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
<?php endif; ?>
<main class="container page-content">
