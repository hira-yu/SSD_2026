<?php

declare(strict_types=1);

$placeholderImage = product_image_url('');
$favoriteProductIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$isFavorite = in_array((int) $product['id'], $favoriteProductIds, true);
$redirectTo = $_SERVER['REQUEST_URI'] ?? '/products/' . (string) $product['id'];
$deliverySchedule = is_array($deliverySchedule ?? null) ? $deliverySchedule : [];
$deadlineHours = (int) ($deliverySchedule['deadline_hours'] ?? 0);
$deadlineMinutes = (int) ($deliverySchedule['deadline_minutes'] ?? 0);
$deadlineParts = [];

if ($deadlineHours > 0) {
    $deadlineParts[] = sprintf('%d時間', $deadlineHours);
}

if ($deadlineMinutes > 0) {
    $deadlineParts[] = sprintf('%d分', $deadlineMinutes);
}

$deadlineLabel = implode('と', $deadlineParts);

if ($deadlineLabel !== '') {
    $deadlineLabel .= '以内';
}
?>
<section class="market-product-detail-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <a href="<?= e(app_path('/products')) ?>">商品一覧</a>
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
                <p class="market-detail-price-label">価格</p>
                <p class="market-detail-price">
                    <?php if (!empty($product['is_on_sale'])): ?>
                        <span class="market-regular-price">¥<?= number_format((int) $product['regular_price']) ?></span>
                        <span class="market-sale-badge">SALE</span>
                    <?php endif; ?>
                    ¥<?= number_format((int) $product['price']) ?>
                </p>
                <p class="market-detail-delivery <?= e((string) $product['availability_class']) ?>">
                    <?= e((string) $product['availability_label']) ?>
                </p>
                <?php if (!empty($product['sales_period_label'])): ?>
                    <p class="market-detail-delivery-note">販売期間: <?= e((string) $product['sales_period_label']) ?></p>
                <?php endif; ?>
                <?php if (($deliverySchedule['summary_type'] ?? '') === 'orderable'): ?>
                    <p class="market-detail-delivery-note market-detail-delivery-note-strong">
                        <?php if (!empty($deliverySchedule['supports_same_day'])): ?>
                            今から
                            <span class="market-detail-delivery-emphasis"><?= e($deadlineLabel) ?></span>
                            のご注文で、
                            <span class="market-detail-delivery-emphasis">本日中に</span>
                            お届けします。
                        <?php else: ?>
                            今から
                            <span class="market-detail-delivery-emphasis"><?= e($deadlineLabel) ?></span>
                            のご注文で、
                            <span class="market-detail-delivery-emphasis"><?= e((string) ($deliverySchedule['arrival_date_label'] ?? '')) ?></span>
                            までにお届けします。
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="market-detail-delivery-note"><?= e((string) ($deliverySchedule['summary_text'] ?? '')) ?></p>
                <?php endif; ?>
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
            </dl>
        </div>

        <aside class="market-detail-buy-rail">
            <div class="market-detail-buy-card">
                <?php if (!empty($product['is_orderable'])): ?>
                    <form class="market-detail-buy-form" method="post" action="<?= e(app_path('/cart/add')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <div class="form-field">
                            <label for="detail-qty">数量</label>
                            <input id="detail-qty" type="number" name="quantity" min="1" value="1" inputmode="numeric">
                        </div>
                        <button class="button-link button-submit button-full" type="submit">
                            <i data-lucide="shopping-cart" aria-hidden="true"></i>
                            ショッピングカートに入れる
                        </button>
                    </form>

                    <form class="market-favorite-form" method="post" action="<?= e(app_path($isFavorite ? '/favorites/remove' : '/favorites/add')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                        <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                        <button class="button-link button-secondary button-full" type="submit">
                            <i data-lucide="<?= $isFavorite ? 'heart-off' : 'heart' ?>" aria-hidden="true"></i>
                            <?= $isFavorite ? 'お気に入りから外す' : 'お気に入りに追加' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="market-stock-copy status-ng">現在在庫がないため、カートに追加できません。</p>
                <?php endif; ?>
            </div>

            <div class="market-detail-note-card">
                <h2><i data-lucide="info" aria-hidden="true"></i>ご案内</h2>
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
                    <a class="market-product-thumb" href="<?= e(app_path('/products/' . (string) $relatedProduct['id'])) ?>">
                        <img
                            src="<?= e((string) $relatedProduct['image_url']) ?>"
                            alt="<?= e((string) $relatedProduct['name']) ?>"
                            data-fallback-src="<?= e($placeholderImage) ?>"
                        >
                    </a>
                    <div class="market-product-body">
                        <p class="market-product-meta"><?= e((string) $relatedProduct['maker']) ?></p>
                        <a class="market-product-title" href="<?= e(app_path('/products/' . (string) $relatedProduct['id'])) ?>">
                            <?= e((string) $relatedProduct['name']) ?>
                        </a>
                        <p class="market-price-row">
                            <?php if (!empty($relatedProduct['is_on_sale'])): ?>
                                <span class="market-regular-price">¥<?= number_format((int) $relatedProduct['regular_price']) ?></span>
                                <span class="market-sale-badge">SALE</span>
                            <?php endif; ?>
                            ¥<?= number_format((int) $relatedProduct['price']) ?>
                        </p>
                        <p class="market-stock-copy <?= e((string) $relatedProduct['availability_class']) ?>">
                            <?= e((string) $relatedProduct['availability_label']) ?>
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
                        <a class="market-product-thumb" href="<?= e(app_path('/products/' . (string) $makerProduct['id'])) ?>">
                            <img
                                src="<?= e((string) $makerProduct['image_url']) ?>"
                                alt="<?= e((string) $makerProduct['name']) ?>"
                                data-fallback-src="<?= e($placeholderImage) ?>"
                            >
                        </a>
                        <div class="market-product-body">
                            <p class="market-product-meta"><?= e((string) $makerProduct['category']) ?></p>
                            <a class="market-product-title" href="<?= e(app_path('/products/' . (string) $makerProduct['id'])) ?>">
                                <?= e((string) $makerProduct['name']) ?>
                            </a>
                            <p class="market-price-row">
                                <?php if (!empty($makerProduct['is_on_sale'])): ?>
                                    <span class="market-regular-price">¥<?= number_format((int) $makerProduct['regular_price']) ?></span>
                                    <span class="market-sale-badge">SALE</span>
                                <?php endif; ?>
                                ¥<?= number_format((int) $makerProduct['price']) ?>
                            </p>
                            <p class="market-stock-copy <?= e((string) $makerProduct['availability_class']) ?>">
                                <?= e((string) $makerProduct['availability_label']) ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
