<?php

declare(strict_types=1);

$name = (string) ($filters['name'] ?? '');
$selectedCategory = (string) ($filters['category'] ?? '');
$selectedMaker = (string) ($filters['maker'] ?? '');
$placeholderImage = '/assets/img/products/placeholder.svg';
$favoriteProductIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$redirectTo = $_SERVER['REQUEST_URI'] ?? '/products';
?>
<section class="market-catalog-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <span>商品一覧</span>
    </div>

    <div class="market-results-summary">
        <div>
            <h2>商品一覧</h2>
            <p><?= e((string) count($products)) ?>件中 <?= e((string) count($products)) ?>件を表示しています。</p>
        </div>
        <p>キーワード、カテゴリ、メーカーから条件を指定して商品を探せます。</p>
    </div>

    <div class="market-catalog-layout">
        <aside class="market-filter-column">
            <section class="market-filter-panel">
                <div class="market-panel-heading">カテゴリ</div>
                <ul class="market-filter-list">
                    <?php foreach ($categoryOptions as $option): ?>
                        <li class="<?= $selectedCategory === (string) $option['value'] ? 'active' : '' ?>">
                            <a href="/products?name=<?= urlencode($name) ?>&category=<?= urlencode((string) $option['value']) ?>&maker=<?= urlencode($selectedMaker) ?>">
                                <?= e((string) $option['value']) ?>
                            </a>
                            <span><?= e((string) $option['count']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="market-filter-panel">
                <div class="market-panel-heading">メーカー</div>
                <ul class="market-filter-list">
                    <?php foreach ($makerOptions as $option): ?>
                        <li class="<?= $selectedMaker === (string) $option['value'] ? 'active' : '' ?>">
                            <a href="/products?name=<?= urlencode($name) ?>&category=<?= urlencode($selectedCategory) ?>&maker=<?= urlencode((string) $option['value']) ?>">
                                <?= e((string) $option['value']) ?>
                            </a>
                            <span><?= e((string) $option['count']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="market-filter-panel">
                <div class="market-panel-heading">条件を変更する</div>
                <form class="market-side-filter-form" method="get" action="/products">
                    <div class="form-field">
                        <label for="maker">メーカー</label>
                        <select id="maker" name="maker">
                            <option value="">全てのメーカー</option>
                            <?php foreach ($makerOptions as $option): ?>
                                <option value="<?= e((string) $option['value']) ?>" <?= $selectedMaker === (string) $option['value'] ? 'selected' : '' ?>>
                                    <?= e((string) $option['value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="name" value="<?= e($name) ?>">
                    <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">
                    <button class="button-link button-secondary button-full" type="submit">条件を更新</button>
                    <a class="button-link button-ghost button-full" href="/products">条件をクリア</a>
                </form>
            </section>
        </aside>

        <div class="market-results-column">
            <div class="market-results-header">
                <p><strong><?= e((string) count($products)) ?>件</strong>の商品が見つかりました。</p>
                <p>価格、在庫、型番を確認しながら、そのままカートへ追加できます。</p>
            </div>

            <?php if ($products === []): ?>
                <section class="market-empty-state">
                    <h3>該当する商品が見つかりませんでした</h3>
                    <p>キーワードを短くするか、カテゴリ・メーカー条件を外してお試しください。</p>
                </section>
            <?php else: ?>
                <section class="market-product-grid market-product-grid-catalog">
                    <?php foreach ($products as $product): ?>
                        <article class="market-product-card market-product-card-grid market-grid-card">
                            <a class="market-product-thumb market-product-thumb-grid" href="/products/<?= e((string) $product['id']) ?>">
                                <img
                                    src="<?= e((string) $product['image_url']) ?>"
                                    alt="<?= e((string) $product['name']) ?>"
                                    data-fallback-src="<?= e($placeholderImage) ?>"
                                >
                            </a>
                            <div class="market-product-body">
                                <p class="market-product-meta"><?= e((string) $product['category']) ?> / <?= e((string) $product['maker']) ?></p>
                                <a class="market-product-title" href="/products/<?= e((string) $product['id']) ?>">
                                    <?= e((string) $product['name']) ?>
                                </a>
                                <p class="market-price-row">¥<?= number_format((int) $product['price']) ?></p>
                                <p class="market-stock-copy <?= e((string) $product['availability_class']) ?>">
                                    <?= e((string) $product['availability_label']) ?> / 在庫 <?= e((string) $product['stock_quantity_2']) ?>
                                </p>
                                <p class="market-product-code"><?= e((string) $product['product_no']) ?></p>

                                <?php if (!empty($product['is_orderable'])): ?>
                                    <form class="market-grid-actions" method="post" action="/cart/add">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                        <div class="market-quantity-inline">
                                            <label for="qty-<?= e((string) $product['id']) ?>">数量</label>
                                            <input id="qty-<?= e((string) $product['id']) ?>" type="number" name="quantity" min="1" value="1" inputmode="numeric">
                                        </div>
                                        <button class="button-link button-submit button-full" type="submit">カートに入れる</button>
                                    </form>
                                <?php else: ?>
                                    <p class="market-stock-copy status-ng">現在在庫がないため、カートに追加できません。</p>
                                <?php endif; ?>

                                <form class="market-favorite-form" method="post" action="<?= in_array((int) $product['id'], $favoriteProductIds, true) ? '/favorites/remove' : '/favorites/add' ?>">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                    <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
                                    <button class="button-link button-secondary button-small button-full market-favorite-button" type="submit">
                                        <?= in_array((int) $product['id'], $favoriteProductIds, true) ? 'お気に入りから外す' : 'お気に入りに追加' ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
