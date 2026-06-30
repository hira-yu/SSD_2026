<?php

declare(strict_types=1);

$name = (string) ($filters['name'] ?? '');
$cartItemCount = (int) ($cartItemCount ?? 0);
?>
<section class="panel search-panel">
    <p class="eyebrow">Products</p>
    <h2>商品一覧・商品検索</h2>
    <p class="lead compact">商品名の部分一致で検索できます。未入力のまま検索すると全商品を表示します。</p>

    <form class="search-form" method="get" action="/products">
        <div class="form-field">
            <label for="name">商品名</label>
            <input id="name" type="text" name="name" value="<?= e($name) ?>" placeholder="例: マウス">
        </div>
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索する</button>
            <a class="button-link button-secondary" href="/products">条件をクリア</a>
        </div>
    </form>
</section>

<section class="panel quick-cart-panel">
    <div class="section-heading compact-heading">
        <div>
            <h3>カート</h3>
            <p class="help-text">購入予定の商品をまとめて確認できます。</p>
        </div>
        <a class="button-link" href="/cart">カートを見る<?= $cartItemCount > 0 ? ' (' . e((string) $cartItemCount) . ')' : '' ?></a>
    </div>
</section>

<?php if ($products === []): ?>
    <section class="panel">
        <p class="empty-state">該当する商品がありません。</p>
    </section>
<?php else: ?>
    <section class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <div class="product-card-header">
                    <p class="product-no"><?= e((string) $product['product_no']) ?></p>
                    <p class="<?= e((string) $product['availability_class']) ?> product-status">
                        <?= e((string) $product['availability_label']) ?>
                    </p>
                </div>
                <h3><?= e((string) $product['name']) ?></h3>
                <dl class="product-meta">
                    <div>
                        <dt>単価</dt>
                        <dd>¥<?= number_format((int) $product['price']) ?></dd>
                    </div>
                    <div>
                        <dt>商品カテゴリ</dt>
                        <dd><?= e((string) $product['category']) ?></dd>
                    </div>
                    <div>
                        <dt>メーカー名</dt>
                        <dd><?= e((string) $product['maker']) ?></dd>
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
                        <button class="button-link button-submit" type="submit">カートに追加</button>
                    </form>
                <?php else: ?>
                    <p class="status-ng product-cart-disabled">在庫なしのため追加できません。</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
