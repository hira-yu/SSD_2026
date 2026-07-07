<?php

declare(strict_types=1);

$placeholderImage = '/assets/img/products/placeholder.svg';
$favoriteProductIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$isFavorite = in_array((int) $product['id'], $favoriteProductIds, true);
$redirectTo = $_SERVER['REQUEST_URI'] ?? '/products/' . (string) $product['id'];
?>
<section class="market-product-detail-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <a href="/products">商品一覧</a>
        <span><?= e((string) $product['name']) ?></span>
    </div>

    <div class="market-detail-layout">
        <div class="market-detail-gallery">
            <div class="market-detail-image-frame">
                <img
                    src="<?= e((string) $product['image_url']) ?>"
                    alt="<?= e((string) $product['name']) ?>"
                    data-fallback-src="<?= e($placeholderImage) ?>"
                >
            </div>
        </div>

        <div class="market-detail-main">
            <p class="market-detail-maker"><?= e((string) $product['maker']) ?></p>
            <h1><?= e((string) $product['name']) ?></h1>
            <p class="market-detail-copy">商品番号 <?= e((string) $product['product_no']) ?> / <?= e((string) $product['category']) ?></p>

            <div class="market-detail-price-box">
                <p class="market-detail-price-label">販売価格</p>
                <p class="market-detail-price">¥<?= number_format((int) $product['price']) ?></p>
                <p class="market-detail-delivery <?= e((string) $product['availability_class']) ?>">
                    <?= e((string) $product['availability_label']) ?> / 在庫 <?= e((string) $product['stock_quantity_2']) ?>
                </p>
                <p class="market-detail-delivery-note"><?= e((string) $deliverySummary) ?></p>
            </div>

            <dl class="market-detail-specs">
                <div>
                    <dt>メーカー</dt>
                    <dd><?= e((string) $product['maker']) ?></dd>
                </div>
                <div>
                    <dt>カテゴリ</dt>
                    <dd><?= e((string) $product['category']) ?></dd>
                </div>
                <div>
                    <dt>商品番号</dt>
                    <dd><?= e((string) $product['product_no']) ?></dd>
                </div>
                <div>
                    <dt>配送</dt>
                    <dd>通常 2-4 日</dd>
                </div>
            </dl>
        </div>

        <aside class="market-detail-buy-rail">
            <div class="market-detail-buy-card">
                <?php if (!empty($product['is_orderable'])): ?>
                    <form class="market-detail-buy-form" method="post" action="/cart/add">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <div class="form-field">
                            <label for="detail-qty">数量</label>
                            <input id="detail-qty" type="number" name="quantity" min="1" value="1" inputmode="numeric">
                        </div>
                        <button class="button-link button-submit button-full" type="submit">ショッピングカートに入れる</button>
                    </form>

                    <form class="market-favorite-form" method="post" action="<?= $isFavorite ? '/favorites/remove' : '/favorites/add' ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                        <button class="button-link button-secondary button-full" type="submit">
                            <?= $isFavorite ? 'お気に入りから外す' : 'お気に入りに追加' ?>
                        </button>
                    </form>

                    <a class="button-link button-secondary button-full" href="/cart">カートを見る</a>
                <?php else: ?>
                    <p class="market-stock-copy status-ng">現在在庫がないため、カートに追加できません。</p>
                <?php endif; ?>
            </div>

            <div class="market-detail-note-card">
                <h2>ご案内</h2>
                <p>ご注文前に数量と配送先情報をご確認ください。</p>
                <p>在庫状況はご注文時点の情報をもとにご案内しています。</p>
            </div>
        </aside>
    </div>

    <section class="market-merch-section">
        <div class="market-merch-header">
            <div>
                <h3><?= e((string) $product['name']) ?>と同じカテゴリの商品</h3>
                <p>関連する商品をまとめて確認できます。</p>
            </div>
        </div>

        <div class="market-product-row">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <article class="market-product-card">
                    <a class="market-product-thumb" href="/products/<?= e((string) $relatedProduct['id']) ?>">
                        <img
                            src="<?= e((string) $relatedProduct['image_url']) ?>"
                            alt="<?= e((string) $relatedProduct['name']) ?>"
                            data-fallback-src="<?= e($placeholderImage) ?>"
                        >
                    </a>
                    <div class="market-product-body">
                        <p class="market-product-meta"><?= e((string) $relatedProduct['maker']) ?></p>
                        <a class="market-product-title" href="/products/<?= e((string) $relatedProduct['id']) ?>">
                            <?= e((string) $relatedProduct['name']) ?>
                        </a>
                        <p class="market-price-row">¥<?= number_format((int) $relatedProduct['price']) ?></p>
                        <p class="market-stock-copy <?= e((string) $relatedProduct['availability_class']) ?>">
                            <?= e((string) $relatedProduct['availability_label']) ?> / 在庫 <?= e((string) $relatedProduct['stock_quantity_2']) ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($sameMakerProducts !== []): ?>
        <section class="market-merch-section">
            <div class="market-merch-header">
                <div>
                    <h3><?= e((string) $product['maker']) ?>の商品</h3>
                    <p>同じメーカーの商品もあわせてご覧いただけます。</p>
                </div>
            </div>

            <div class="market-product-row">
                <?php foreach ($sameMakerProducts as $makerProduct): ?>
                    <article class="market-product-card">
                        <a class="market-product-thumb" href="/products/<?= e((string) $makerProduct['id']) ?>">
                            <img
                                src="<?= e((string) $makerProduct['image_url']) ?>"
                                alt="<?= e((string) $makerProduct['name']) ?>"
                                data-fallback-src="<?= e($placeholderImage) ?>"
                            >
                        </a>
                        <div class="market-product-body">
                            <p class="market-product-meta"><?= e((string) $makerProduct['category']) ?></p>
                            <a class="market-product-title" href="/products/<?= e((string) $makerProduct['id']) ?>">
                                <?= e((string) $makerProduct['name']) ?>
                            </a>
                            <p class="market-price-row">¥<?= number_format((int) $makerProduct['price']) ?></p>
                            <p class="market-stock-copy <?= e((string) $makerProduct['availability_class']) ?>">
                                <?= e((string) $makerProduct['availability_label']) ?> / 在庫 <?= e((string) $makerProduct['stock_quantity_2']) ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
