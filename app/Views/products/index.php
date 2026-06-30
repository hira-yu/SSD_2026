<?php

declare(strict_types=1);

$name = (string) ($filters['name'] ?? '');
$selectedCategory = (string) ($filters['category'] ?? '');
$selectedMaker = (string) ($filters['maker'] ?? '');
$placeholderImage = '/assets/img/products/placeholder.svg';
?>
<section class="catalog-shell">
    <div class="catalog-toolbar">
        <div class="catalog-heading">
            <p class="eyebrow">Product Catalog</p>
            <h2>商品一覧</h2>
            <p>キーワード、カテゴリ、メーカーから商品を探せます。</p>
        </div>
        <a class="catalog-cart-shortcut" href="/cart">カート <?= e((string) $cartItemCount) ?>点</a>
    </div>

    <form class="catalog-search-box" method="get" action="/products">
        <div class="form-field">
            <label for="name">キーワード</label>
            <input id="name" type="text" name="name" value="<?= e($name) ?>" placeholder="例: マウス / キーボード / 充電器">
        </div>
        <div class="form-field">
            <label for="category">カテゴリ</label>
            <select id="category" name="category">
                <option value="">すべてのカテゴリ</option>
                <?php foreach ($categoryOptions as $option): ?>
                    <option value="<?= e((string) $option['value']) ?>" <?= $selectedCategory === (string) $option['value'] ? 'selected' : '' ?>>
                        <?= e((string) $option['value']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="maker">メーカー</label>
            <select id="maker" name="maker">
                <option value="">すべてのメーカー</option>
                <?php foreach ($makerOptions as $option): ?>
                    <option value="<?= e((string) $option['value']) ?>" <?= $selectedMaker === (string) $option['value'] ? 'selected' : '' ?>>
                        <?= e((string) $option['value']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索する</button>
            <a class="button-link button-secondary" href="/products">条件をクリア</a>
        </div>
    </form>

    <div class="catalog-layout-grid">
        <aside class="catalog-filter-column">
            <section class="filter-card">
                <h3>カテゴリ</h3>
                <ul class="filter-link-list">
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

            <section class="filter-card">
                <h3>メーカー</h3>
                <ul class="filter-link-list">
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
        </aside>

        <div class="catalog-results-column">
            <div class="catalog-results-header">
                <p><?= e((string) count($products)) ?>件の商品が見つかりました。</p>
                <p class="catalog-results-help">価格、在庫、配送条件を確認してそのままカートへ追加できます。</p>
            </div>

            <?php if ($products === []): ?>
                <section class="empty-result-card">
                    <h3>該当する商品が見つかりませんでした</h3>
                    <p>キーワードを短くするか、カテゴリ・メーカー条件を外してお試しください。</p>
                </section>
            <?php else: ?>
                <section class="ec-product-grid catalog-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="ec-product-card catalog-card">
                            <a class="product-image-frame catalog-image" href="/products?name=<?= urlencode((string) $product['name']) ?>">
                                <img
                                    src="<?= e((string) $product['image_url']) ?>"
                                    alt="<?= e((string) $product['name']) ?>"
                                    data-fallback-src="<?= e($placeholderImage) ?>"
                                >
                            </a>
                            <div class="product-card-body">
                                <p class="product-card-meta"><?= e((string) $product['category']) ?> / <?= e((string) $product['maker']) ?></p>
                                <h3><?= e((string) $product['name']) ?></h3>
                                <p class="product-card-price">¥<?= number_format((int) $product['price']) ?></p>
                                <dl class="product-facts">
                                    <div>
                                        <dt>在庫</dt>
                                        <dd><?= e((string) $product['stock_quantity_2']) ?></dd>
                                    </div>
                                    <div>
                                        <dt>配送</dt>
                                        <dd>通常 2-4 日</dd>
                                    </div>
                                </dl>
                                <div class="product-card-footer">
                                    <span class="stock-chip <?= e((string) $product['availability_class']) ?>">
                                        <?= e((string) $product['availability_label']) ?>
                                    </span>
                                    <span class="product-code"><?= e((string) $product['product_no']) ?></span>
                                </div>

                                <?php if (!empty($product['is_orderable'])): ?>
                                    <form class="product-cart-form" method="post" action="/cart/add">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                        <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                        <div class="form-field inline-quantity-field">
                                            <label for="qty-<?= e((string) $product['id']) ?>">数量</label>
                                            <input id="qty-<?= e((string) $product['id']) ?>" type="number" name="quantity" min="1" value="1" inputmode="numeric">
                                        </div>
                                        <button class="button-link button-submit customer-buy-button" type="submit">カートに追加</button>
                                    </form>
                                <?php else: ?>
                                    <p class="product-card-note status-ng">現在在庫がないため、カートに追加できません。</p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
