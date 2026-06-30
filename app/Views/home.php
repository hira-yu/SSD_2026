<?php

declare(strict_types=1);
?>
<section class="ec-home-hero">
    <div class="hero-copy-block">
        <p class="eyebrow">IPUT EC</p>
        <h2>必要な商品を、必要なときに、迷わず注文できるオンラインストア。</h2>
        <p class="hero-description">
            PC周辺機器から事務用品まで、価格・在庫・配送条件を確認しながらスムーズにご購入いただけます。
        </p>

        <form class="hero-search-form" method="get" action="/products" role="search">
            <label class="sr-only" for="hero-search">商品検索</label>
            <input id="hero-search" type="text" name="name" placeholder="商品名・型番・キーワードで探す">
            <button class="button-link button-submit" type="submit">商品を探す</button>
        </form>

        <div class="hero-actions">
            <a class="button-link button-submit" href="/products">商品一覧を見る</a>
            <a class="button-link button-secondary" href="/cart">カートを確認</a>
        </div>

        <ul class="trust-list">
            <li>全国一律送料 660円</li>
            <li>在庫状況を商品一覧で確認可能</li>
            <li>担当者向け管理画面あり</li>
        </ul>
    </div>

    <aside class="hero-side-stack">
        <div class="hero-side-card">
            <h3>人気カテゴリ</h3>
            <ul class="quick-link-list">
                <?php foreach (array_slice($categoryOptions, 0, 6) as $category): ?>
                    <li>
                        <a href="/products?category=<?= urlencode((string) $category['value']) ?>">
                            <?= e((string) $category['value']) ?>
                        </a>
                        <span><?= e((string) $category['count']) ?>件</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="hero-side-card">
            <h3>ご利用案内</h3>
            <ul class="support-list">
                <li>ご注文前にカートと配送先情報をご確認ください。</li>
                <li>担当者ログインは画面右上のリンクからご利用いただけます。</li>
                <li>学習目的の試作システムとして運用しています。</li>
            </ul>
        </div>
    </aside>
</section>

<section class="ec-section">
    <div class="section-header">
        <div>
            <h3>おすすめ商品</h3>
            <p>定番の周辺機器と業務で使いやすい商品をピックアップしています。</p>
        </div>
        <a class="text-link" href="/products">商品一覧へ</a>
    </div>

    <div class="ec-product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <article class="ec-product-card">
                <a class="product-image-frame" href="/products?name=<?= urlencode((string) $product['name']) ?>">
                    <img
                        src="<?= e((string) $product['image_url']) ?>"
                        alt="<?= e((string) $product['name']) ?>"
                        data-fallback-src="/assets/img/products/placeholder.svg"
                    >
                </a>
                <div class="product-card-body">
                    <p class="product-card-meta"><?= e((string) $product['category']) ?> / <?= e((string) $product['maker']) ?></p>
                    <h4><?= e((string) $product['name']) ?></h4>
                    <p class="product-card-price">¥<?= number_format((int) $product['price']) ?></p>
                    <div class="product-card-footer">
                        <span class="stock-chip <?= e((string) $product['availability_class']) ?>">
                            <?= e((string) $product['availability_label']) ?>
                        </span>
                        <span class="stock-count">在庫 <?= e((string) $product['stock_quantity_2']) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="ec-section subtle-section">
    <div class="section-header">
        <div>
            <h3>新着商品</h3>
            <p>商品一覧ページからカテゴリやメーカーでも絞り込めます。</p>
        </div>
    </div>

    <div class="ec-product-grid compact">
        <?php foreach ($newArrivalProducts as $product): ?>
            <article class="ec-product-card compact-card">
                <a class="product-image-frame compact-image" href="/products?name=<?= urlencode((string) $product['name']) ?>">
                    <img
                        src="<?= e((string) $product['image_url']) ?>"
                        alt="<?= e((string) $product['name']) ?>"
                        data-fallback-src="/assets/img/products/placeholder.svg"
                    >
                </a>
                <div class="product-card-body">
                    <h4><?= e((string) $product['name']) ?></h4>
                    <p class="product-card-price">¥<?= number_format((int) $product['price']) ?></p>
                    <a class="text-link" href="/products?name=<?= urlencode((string) $product['name']) ?>">詳細を見る</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
