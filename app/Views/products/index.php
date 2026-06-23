<?php

declare(strict_types=1);

$name = (string) ($filters['name'] ?? '');
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
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
