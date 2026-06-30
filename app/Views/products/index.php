<?php

declare(strict_types=1);

$name = (string) ($filters['name'] ?? '');
$selectedCategory = (string) ($filters['category'] ?? '');
$selectedMaker = (string) ($filters['maker'] ?? '');
$cartItemCount = (int) ($cartItemCount ?? 0);
?>
<section class="catalog-hero">
    <div class="panel customer-panel search-panel catalog-search-panel">
        <p class="eyebrow">Product Search</p>
        <h2>商品一覧・商品検索</h2>
        <p class="lead compact">キーワード検索に加えて、カテゴリやメーカーから絞り込めます。</p>

        <form class="catalog-search-form" method="get" action="/products">
            <div class="form-field">
                <label for="name">キーワード</label>
                <input id="name" type="text" name="name" value="<?= e($name) ?>" placeholder="例: マウス / キーボード">
            </div>
            <div class="form-field">
                <label for="category">カテゴリ</label>
                <select id="category" name="category">
                    <option value="">すべてのカテゴリ</option>
                    <?php foreach ($categoryOptions as $option): ?>
                        <option value="<?= e((string) $option['value']) ?>" <?= ($selectedCategory === (string) $option['value']) ? 'selected' : '' ?>>
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
                        <option value="<?= e((string) $option['value']) ?>" <?= ($selectedMaker === (string) $option['value']) ? 'selected' : '' ?>>
                            <?= e((string) $option['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-actions">
                <button class="button-link button-submit" type="submit">検索</button>
                <a class="button-link button-secondary" href="/products">条件クリア</a>
            </div>
        </form>
    </div>

    <aside class="catalog-cart-panel">
        <div class="sidebar-card">
            <h3>カート</h3>
            <p class="cart-panel-count"><?= e((string) $cartItemCount) ?> 点</p>
            <p class="help-text">気になる商品をまとめて比較し、そのまま注文確認へ進めます。</p>
            <a class="button-link" href="/cart">カートを見る</a>
        </div>
    </aside>
</section>

<section class="catalog-layout">
    <aside class="catalog-sidebar">
        <div class="sidebar-card">
            <h3>カテゴリ</h3>
            <ul class="facet-list">
                <?php foreach ($categoryOptions as $option): ?>
                    <li class="<?= ($selectedCategory === (string) $option['value']) ? 'active' : '' ?>">
                        <a href="/products?name=<?= urlencode($name) ?>&category=<?= urlencode((string) $option['value']) ?>&maker=<?= urlencode($selectedMaker) ?>">
                            <?= e((string) $option['value']) ?>
                        </a>
                        <span><?= e((string) $option['count']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-card">
            <h3>メーカー</h3>
            <ul class="facet-list">
                <?php foreach ($makerOptions as $option): ?>
                    <li class="<?= ($selectedMaker === (string) $option['value']) ? 'active' : '' ?>">
                        <a href="/products?name=<?= urlencode($name) ?>&category=<?= urlencode($selectedCategory) ?>&maker=<?= urlencode((string) $option['value']) ?>">
                            <?= e((string) $option['value']) ?>
                        </a>
                        <span><?= e((string) $option['count']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <div class="catalog-results">
        <section class="panel customer-panel results-summary-panel">
            <div class="results-summary">
                <div>
                    <h3>検索結果</h3>
                    <p class="help-text"><?= e((string) count($products)) ?> 件の商品が見つかりました。</p>
                </div>
                <div class="results-badges">
                    <span class="results-badge">青系アクセント</span>
                    <span class="results-badge">スマホ対応</span>
                </div>
            </div>
        </section>

        <?php if ($products === []): ?>
            <section class="panel customer-panel">
                <p class="empty-state">該当する商品がありません。検索条件を変更してください。</p>
            </section>
        <?php else: ?>
            <section class="product-grid catalog-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card customer-product-card">
                        <div class="product-card-header">
                            <p class="product-no"><?= e((string) $product['product_no']) ?></p>
                            <p class="<?= e((string) $product['availability_class']) ?> product-status">
                                <?= e((string) $product['availability_label']) ?>
                            </p>
                        </div>
                        <h3><?= e((string) $product['name']) ?></h3>
                        <p class="customer-product-meta"><?= e((string) $product['category']) ?> / <?= e((string) $product['maker']) ?></p>
                        <dl class="product-meta">
                            <div>
                                <dt>販売価格</dt>
                                <dd class="customer-price">¥<?= number_format((int) $product['price']) ?></dd>
                            </div>
                            <div>
                                <dt>在庫数量2</dt>
                                <dd><?= e((string) $product['stock_quantity_2']) ?></dd>
                            </div>
                        </dl>
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
                            <p class="status-ng product-cart-disabled">在庫なしのため追加できません。</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
