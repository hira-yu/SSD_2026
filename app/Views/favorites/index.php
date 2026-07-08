<?php

declare(strict_types=1);

$favoriteProductIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$placeholderImage = product_image_url('');
?>
<section class="market-utility-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <span>お気に入り商品</span>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= e((string) $successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-error"><?= e((string) $errorMessage) ?></div>
    <?php endif; ?>

    <section class="market-utility-hero">
        <div>
            <p class="market-utility-kicker">Favorite List</p>
            <h1>気になる商品をまとめて確認</h1>
            <p>あとで比較したい商品を一覧で見直し、そのまま商品詳細やカート追加へ進めます。</p>
        </div>
        <div class="market-utility-stat">
            <strong><?= e((string) count($items)) ?></strong>
            <span>登録商品数</span>
        </div>
    </section>

    <?php if ($items === []): ?>
        <section class="market-empty-state market-empty-state-soft">
            <h2>お気に入り商品はまだ登録されていません</h2>
            <p>商品一覧やトップページから「お気に入りに追加」を押すと、ここに保存されます。</p>
            <a class="button-link button-submit" href="<?= e(app_path('/products')) ?>">
                <i data-lucide="search" aria-hidden="true"></i>
                商品を探す
            </a>
        </section>
    <?php else: ?>
        <section class="market-product-grid market-product-grid-catalog">
            <?php foreach ($items as $product): ?>
                <article class="market-product-card market-product-card-grid market-grid-card">
                    <a class="market-product-thumb market-product-thumb-grid" href="<?= e(app_path('/products/' . (string) $product['id'])) ?>">
                        <img
                            src="<?= e((string) $product['image_url']) ?>"
                            alt="<?= e((string) $product['name']) ?>"
                            data-fallback-src="<?= e($placeholderImage) ?>"
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

                        <?php if (!empty($product['is_orderable'])): ?>
                            <form class="market-grid-actions" method="post" action="<?= e(app_path('/cart/add')) ?>">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                <div class="market-quantity-inline">
                                    <label for="favorite-qty-<?= e((string) $product['id']) ?>">数量</label>
                                    <input id="favorite-qty-<?= e((string) $product['id']) ?>" type="number" name="quantity" min="1" value="1" inputmode="numeric">
                                </div>
                                <button class="button-link button-submit button-full" type="submit">
                                    <i data-lucide="shopping-cart" aria-hidden="true"></i>
                                    カートに入れる
                                </button>
                            </form>
                        <?php endif; ?>

                        <form class="market-favorite-form" method="post" action="<?= e(app_path('/favorites/remove')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                            <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                            <input type="hidden" name="redirect_to" value="<?= e(app_path('/favorites')) ?>">
                            <button class="button-link button-secondary button-small button-full market-favorite-button" type="submit">
                                <i data-lucide="heart-off" aria-hidden="true"></i>
                                お気に入りから外す
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>
