<?php

declare(strict_types=1);

$headlineCategories = array_slice($categoryOptions, 0, 14);
$headlineMakers = array_slice($makerOptions, 0, 10);
$recentProducts = array_slice($newArrivalProducts, 0, 8);
$recommendedProducts = $newArrivalProducts !== [] ? $newArrivalProducts : $featuredProducts;
$spotlightProducts = $featuredProducts !== [] ? $featuredProducts : $newArrivalProducts;
$favoriteProductIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$redirectTo = $_SERVER['REQUEST_URI'] ?? '/';
?>
<div class="market-breadcrumb">
    <a href="<?= e(app_path('/')) ?>">トップ</a>
    <span>IPUT EC</span>
</div>

<section class="market-stage">
    <aside class="market-category-panel">
        <div class="market-panel-heading">カテゴリ</div>
        <ul class="market-category-list">
            <?php foreach ($headlineCategories as $category): ?>
                <li>
                    <a href="<?= e(app_path('/products')) ?>?category=<?= urlencode((string) $category['value']) ?>">
                        <?= e((string) $category['value']) ?>
                    </a>
                    <span><?= e((string) $category['count']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="market-hero-column">
        <div class="market-promo-tabs">
            <a href="<?= e(app_path('/products')) ?>?feature=seasonal"><i data-lucide="fan" aria-hidden="true"></i>季節家電</a>
            <a href="<?= e(app_path('/products')) ?>?feature=bulk"><i data-lucide="package-check" aria-hidden="true"></i>日用品まとめ買い</a>
            <a href="<?= e(app_path('/products')) ?>?feature=pc"><i data-lucide="monitor" aria-hidden="true"></i>PC・周辺機器</a>
            <a href="<?= e(app_path('/products')) ?>?feature=limited-sale"><i data-lucide="badge-percent" aria-hidden="true"></i>数量限定SALE</a>
            <a href="<?= e(app_path('/products')) ?>?feature=new"><i data-lucide="sparkles" aria-hidden="true"></i>新着商品</a>
        </div>

        <div class="market-sub-promos">
            <a class="market-sub-promo market-sub-promo-blue" href="<?= e(app_path('/products')) ?>?feature=pc">
                <div>
                    <p>人気カテゴリ</p>
                    <strong>PC・周辺機器をまとめて確認</strong>
                </div>
                <span>検索へ<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
            <a class="market-sub-promo market-sub-promo-light" href="<?= e(app_path('/checkout')) ?>">
                <div>
                    <p>ご注文手続き</p>
                    <strong>配送先入力から確認画面までわかりやすく整理</strong>
                </div>
                <span>購入へ進む<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
        </div>
    </div>

    <aside class="market-side-rail">
        <div class="market-rail-card market-rail-card-alert">
            <h3><i data-lucide="truck" aria-hidden="true"></i>ご案内</h3>
            <p>IPUT ECは</p>
            <p>家電製品、ＰＣ、日用品、</p>
            <p>食品飲料、電子部品まで</p>
            <p>幅広い商品を豊富に取揃え</p>
        </div>

        <div class="market-rail-card">
            <h3><i data-lucide="factory" aria-hidden="true"></i>人気メーカー</h3>
            <ul class="market-mini-link-list">
                <?php foreach ($headlineMakers as $maker): ?>
                    <li>
                        <a href="<?= e(app_path('/products')) ?>?maker=<?= urlencode((string) $maker['value']) ?>">
                            <?= e((string) $maker['value']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</section>

<section class="market-merch-section">
    <div class="market-merch-header">
        <div>
            <h3>最近チェックした商品</h3>
            <p>いま選ばれている商品をピックアップしています。</p>
        </div>
        <a href="<?= e(app_path('/products')) ?>">商品一覧へ<i data-lucide="arrow-right" aria-hidden="true"></i></a>
    </div>

    <div class="market-product-row">
        <?php foreach ($recentProducts as $product): ?>
            <article class="market-product-card">
                <a class="market-product-thumb" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                    <img
                        src="<?= e((string) $product['image_url']) ?>"
                        alt="<?= e((string) $product['name']) ?>"
                        data-fallback-src="<?= e(product_image_url('')) ?>"
                    >
                </a>
                <div class="market-product-body">
                    <p class="market-product-meta"><?= e((string) $product['category']) ?></p>
                    <a class="market-product-title" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                        <?= e((string) $product['name']) ?>
                    </a>
                    <p class="market-price-row">
                        <?php if (!empty($product['is_on_sale'])): ?>
                            <span class="market-regular-price">¥<?= number_format((int) $product['regular_price']) ?></span>
                            <span class="market-sale-badge">SALE</span>
                        <?php endif; ?>
                        ¥<?= number_format((int) $product['price']) ?>
                    </p>
                    <p class="market-stock-copy <?= e((string) $product['availability_class']) ?>">
                        <?= e((string) $product['availability_label']) ?>
                    </p>
                    <form class="market-favorite-form" method="post" action="<?= e(app_path(in_array((int) $product['id'], $favoriteProductIds, true) ? '/favorites/remove' : '/favorites/add')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                        <button class="button-link button-ghost button-small market-favorite-button" type="submit">
                            <i data-lucide="<?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'heart-off' : 'heart' ?>" aria-hidden="true"></i>
                            <?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'お気に入り解除' : 'お気に入りに追加' ?>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="market-merch-section">
    <div class="market-merch-header">
        <div>
            <h3>お客様へのおすすめ</h3>
            <p>カテゴリやメーカーを絞り込んで、目的の商品を見つけやすくしました。</p>
        </div>
        <a href="<?= e(app_path('/products')) ?>">条件を指定して探す<i data-lucide="arrow-right" aria-hidden="true"></i></a>
    </div>

    <div class="market-product-grid">
        <?php foreach ($recommendedProducts as $product): ?>
            <article class="market-product-card market-product-card-grid">
                <a class="market-product-thumb market-product-thumb-grid" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                    <img
                        src="<?= e((string) $product['image_url']) ?>"
                        alt="<?= e((string) $product['name']) ?>"
                        data-fallback-src="<?= e(product_image_url('')) ?>"
                    >
                </a>
                <div class="market-product-body">
                    <p class="market-product-meta"><?= e((string) $product['maker']) ?> / <?= e((string) $product['category']) ?></p>
                    <a class="market-product-title" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                        <?= e((string) $product['name']) ?>
                    </a>
                    <p class="market-price-row">
                        <?php if (!empty($product['is_on_sale'])): ?>
                            <span class="market-regular-price">¥<?= number_format((int) $product['regular_price']) ?></span>
                            <span class="market-sale-badge">SALE</span>
                        <?php endif; ?>
                        ¥<?= number_format((int) $product['price']) ?>
                    </p>
                    <p class="market-stock-copy <?= e((string) $product['availability_class']) ?>">
                        <?= e((string) $product['availability_label']) ?>
                    </p>
                    <form class="market-favorite-form" method="post" action="<?= e(app_path(in_array((int) $product['id'], $favoriteProductIds, true) ? '/favorites/remove' : '/favorites/add')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                        <button class="button-link button-ghost button-small market-favorite-button" type="submit">
                            <i data-lucide="<?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'heart-off' : 'heart' ?>" aria-hidden="true"></i>
                            <?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'お気に入り解除' : 'お気に入りに追加' ?>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="market-merch-section market-maker-section">
    <div class="market-merch-header">
        <div>
            <h3>人気メーカーから探す</h3>
            <p>メーカー別の絞り込みにも対応しています。</p>
        </div>
    </div>

    <div class="market-maker-cloud">
        <?php foreach ($headlineMakers as $maker): ?>
            <a href="<?= e(app_path('/products')) ?>?maker=<?= urlencode((string) $maker['value']) ?>">
                <?= e((string) $maker['value']) ?>
                <span><?= e((string) $maker['count']) ?>件</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="market-merch-section">
    <div class="market-merch-header">
        <div>
            <h3>注目商品</h3>
            <p>注文しやすさを重視して、在庫と価格を見やすく表示しています。</p>
        </div>
    </div>

    <div class="market-product-row">
        <?php foreach ($spotlightProducts as $product): ?>
            <article class="market-product-card">
                <a class="market-product-thumb" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                    <img
                        src="<?= e((string) $product['image_url']) ?>"
                        alt="<?= e((string) $product['name']) ?>"
                        data-fallback-src="<?= e(product_image_url('')) ?>"
                    >
                </a>
                <div class="market-product-body">
                    <p class="market-product-meta"><?= e((string) $product['maker']) ?></p>
                    <a class="market-product-title" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                        <?= e((string) $product['name']) ?>
                    </a>
                    <p class="market-price-row">
                        <?php if (!empty($product['is_on_sale'])): ?>
                            <span class="market-regular-price">¥<?= number_format((int) $product['regular_price']) ?></span>
                            <span class="market-sale-badge">SALE</span>
                        <?php endif; ?>
                        ¥<?= number_format((int) $product['price']) ?>
                    </p>
                    <p class="market-stock-copy <?= e((string) $product['availability_class']) ?>">
                        <?= e((string) $product['availability_label']) ?>
                    </p>
                    <form class="market-favorite-form" method="post" action="<?= e(app_path(in_array((int) $product['id'], $favoriteProductIds, true) ? '/favorites/remove' : '/favorites/add')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                        <button class="button-link button-ghost button-small market-favorite-button" type="submit">
                            <i data-lucide="<?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'heart-off' : 'heart' ?>" aria-hidden="true"></i>
                            <?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'お気に入り解除' : 'お気に入りに追加' ?>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
