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
        <div class="customer-topbar">
            <div class="container customer-topbar-inner">
                <p><?= e($shippingCopy) ?></p>
                <div class="customer-topbar-links">
                    <?php if (is_array($authUser) && !empty($authUser['authenticated'])): ?>
                        <a href="<?= e($authService->destinationForRole((string) ($authUser['role'] ?? ''))) ?>">担当者トップ</a>
                    <?php else: ?>
                        <a href="/login">担当者ログイン</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="container customer-brand-row">
            <a class="brand-block brand-link" href="/">
                <p class="eyebrow">Online Store</p>
                <h1><?= e($serviceName) ?></h1>
                <p class="brand-support-copy"><?= e($serviceTagline) ?></p>
            </a>
            <form class="global-search-form" method="get" action="/products" role="search">
                <label class="sr-only" for="global-search">商品検索</label>
                <input id="global-search" type="text" name="name" value="<?= e($searchQuery) ?>" placeholder="商品名・キーワードで検索">
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
                    <a href="/products">商品一覧</a>
                    <a href="/cart">カート</a>
                    <a href="/checkout">ご注文手続き</a>
                </nav>
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
