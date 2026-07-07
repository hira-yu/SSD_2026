<?php

declare(strict_types=1);

$applicationName = (string) config('app.name', 'IPUT EC');
$adminApplicationName = (string) config('app.admin_name', 'IPUT EC 管理画面');
$authUser = $_SESSION['auth'] ?? null;
$currentPath = current_path();
$isAdminArea = $currentPath === '/login' || str_starts_with($currentPath, '/staff');
$isCustomerArea = !$isAdminArea;
$bodyClass = $isAdminArea ? 'admin-shell admin-page' : 'customer-shell public-page';
$serviceName = (string) config('app.customer_ui.service_name', 'IPUT EC');
$titleBase = $isAdminArea ? $adminApplicationName : $serviceName;
$documentTitle = isset($pageTitle) ? $pageTitle . ' | ' . $titleBase : $titleBase;
$serviceTagline = (string) config('app.customer_ui.tagline', '');
$shippingCopy = (string) config('app.customer_ui.shipping_copy', '');
$supportCopy = (string) config('app.customer_ui.support_copy', '');
$searchQuery = trim((string) ($_GET['name'] ?? ''));
$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$headerCategoryOptions = isset($categoryOptions) && is_array($categoryOptions) ? array_slice($categoryOptions, 0, 16) : [];
$favoriteCount = 0;
$favoriteSessionKey = (string) config('app.online_order.favorite_session_key', 'favorite_products');
$favoriteSessionValues = $_SESSION[$favoriteSessionKey] ?? [];

if (is_array($favoriteSessionValues)) {
    foreach ($favoriteSessionValues as $favoriteValue) {
        if (is_int($favoriteValue) || (is_string($favoriteValue) && ctype_digit($favoriteValue))) {
            $favoriteCount++;
        }
    }
}

$customerUtilityLinks = [
    ['label' => sprintf('お気に入り商品%s', $favoriteCount > 0 ? ' (' . $favoriteCount . ')' : ''), 'url' => '/favorites'],
    ['label' => 'サイトマップ', 'url' => '/sitemap'],
    ['label' => '店舗のご案内', 'url' => '/stores'],
];
$cartCount = 0;
$authService = new AuthService();
$currentRole = (string) (($authUser['role'] ?? ''));
$adminMenu = [
    'receptionist' => [
        ['label' => '受付トップ', 'url' => '/staff/receptionist'],
        ['label' => '注文登録', 'url' => '/staff/receptionist/orders/new'],
        ['label' => '商品検索', 'url' => '/staff/receptionist/products'],
        ['label' => '注文確認', 'url' => '/staff/receptionist/orders'],
    ],
    'accountant' => [
        ['label' => '会計トップ', 'url' => '/staff/accountant'],
        ['label' => '注文検索', 'url' => '/staff/accountant/orders'],
    ],
    'shipper' => [
        ['label' => '発送トップ', 'url' => '/staff/shipper'],
        ['label' => '未発送一覧', 'url' => '/staff/shipper/orders'],
    ],
];

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;800&family=Noto+Serif+JP:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($isCustomerArea): ?>
    <header class="site-header customer-header">
        <div class="customer-utility-bar">
            <div class="container customer-utility-inner">
                <nav class="customer-utility-links" aria-label="補助メニュー">
                    <?php foreach ($customerUtilityLinks as $link): ?>
                        <a href="<?= e((string) $link['url']) ?>"><?= e((string) $link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="customer-utility-status">
                    <span><?= e($shippingCopy) ?></span>
                </div>
            </div>
        </div>

        <div class="customer-search-row">
            <div class="container customer-search-inner">
                <a class="customer-brand-link" href="/">
                    <strong><?= e($serviceName) ?></strong>
                </a>

                <form class="customer-search-form" method="get" action="/products" role="search">
                    <label class="sr-only" for="header-category">カテゴリ</label>
                    <select id="header-category" name="category" class="customer-search-category">
                        <option value="">カテゴリ</option>
                        <?php foreach ($headerCategoryOptions as $category): ?>
                            <option value="<?= e((string) $category['value']) ?>" <?= $selectedCategory === (string) $category['value'] ? 'selected' : '' ?>>
                                <?= e((string) $category['value']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="sr-only" for="global-search">商品検索</label>
                    <input id="global-search" type="text" name="name" value="<?= e($searchQuery) ?>" placeholder="ここにキーワードを入力">
                    <button class="button-link button-submit" type="submit">検索</button>
                </form>

                <a class="customer-cart-link" href="/cart">
                    <span class="cart-icon" aria-hidden="true">CART</span>
                    <span>カート</span>
                    <strong><?= e((string) $cartCount) ?></strong>
                </a>
            </div>
        </div>

        <div class="customer-nav-wrap">
            <div class="container customer-nav-inner">
                <nav class="site-nav customer-nav" aria-label="主要メニュー">
                    <a href="/">トップ</a>
                    <a href="/products">商品一覧</a>
                    <a href="/cart">カート</a>
                    <a href="/checkout">ご注文手続き</a>
                </nav>
                <p class="customer-speed-copy">日本全国スピードお届け実施中</p>
            </div>
        </div>
    </header>
<?php else: ?>
    <header class="site-header admin-header">
        <div class="admin-header-top">
            <div class="container admin-header-top-inner">
                <div class="admin-brand-block">
                    <p class="eyebrow">Operations Console</p>
                    <h1><?= e($adminApplicationName) ?></h1>
                </div>
                <div class="admin-session-block">
                    <?php if (is_array($authUser) && !empty($authUser['authenticated'])): ?>
                        <div class="admin-user-meta">
                            <span>ログイン中: <?= e((string) ($authUser['user_name'] ?? '')) ?></span>
                            <span>ロール: <?= e($authService->roleLabel($currentRole)) ?></span>
                        </div>
                        <form method="post" action="/logout" class="header-logout-form">
                            <input type="hidden" name="_csrf" value="<?= e((string) csrf_token()) ?>">
                            <button class="button-link button-secondary button-small" type="submit">ログアウト</button>
                        </form>
                    <?php else: ?>
                        <div class="admin-user-meta">
                            <span>担当者専用ログイン画面</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="admin-header-nav-wrap">
            <div class="container">
                <nav class="site-nav admin-nav" aria-label="業務メニュー">
                    <a href="/">購入者画面</a>
                    <a href="/system/db-check">DB接続確認</a>
                    <?php foreach (($adminMenu[$currentRole] ?? []) as $menuItem): ?>
                        <a href="<?= e((string) $menuItem['url']) ?>"><?= e((string) $menuItem['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </header>
<?php endif; ?>
<main class="container page-content">
