<?php

declare(strict_types=1);
?>
<section class="storefront-hero">
    <aside class="storefront-sidebar">
        <div class="sidebar-card">
            <h3>人気カテゴリ</h3>
            <ul class="facet-list">
                <?php foreach (array_slice($categoryOptions, 0, 6) as $category): ?>
                    <li>
                        <a href="/products?category=<?= urlencode((string) $category['value']) ?>">
                            <?= e((string) $category['value']) ?>
                        </a>
                        <span><?= e((string) $category['count']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="sidebar-card">
            <h3>サポート情報</h3>
            <ul class="feature-list compact-feature-list">
                <li>配送状況と在庫数量2を商品一覧ですぐ確認できます。</li>
                <li>ネット注文は疑似クレジットカード決済で体験できます。</li>
                <li>担当者ログイン後は受付・会計・発送の確認画面へ進めます。</li>
            </ul>
        </div>
    </aside>

    <div class="storefront-main">
        <div class="hero-banner">
            <div class="hero-copy">
                <p class="eyebrow">Blue Accent Demo Store</p>
                <h2><?= e($appName) ?></h2>
                <p class="lead">PC周辺機器と事務用品を中心に、授業デモで触りやすい購入体験をまとめた IPUT EC のトップページです。</p>
                <div class="action-links">
                    <a class="button-link" href="/products">商品一覧を見る</a>
                    <a class="button-link button-secondary" href="/cart">カートを確認</a>
                </div>
            </div>
            <div class="hero-badges">
                <div class="hero-badge">
                    <strong>配送料</strong>
                    <span>全国一律 660円</span>
                </div>
                <div class="hero-badge">
                    <strong>支払い</strong>
                    <span>疑似クレジットカード決済対応</span>
                </div>
                <div class="hero-badge">
                    <strong>環境</strong>
                    <span><?= e($appEnv) ?> / <?= e($dbDriver) ?></span>
                </div>
            </div>
        </div>

        <div class="promotion-strip">
            <article class="promotion-card">
                <h3>最短で注文体験へ</h3>
                <p>トップから商品検索、カート投入、注文確認までスマホ幅でも迷いにくい導線に調整しています。</p>
            </article>
            <article class="promotion-card">
                <h3>在庫連動</h3>
                <p>在庫数量2を使った引当制御と、発送時の整合性チェックを同時に確認できます。</p>
            </article>
        </div>
    </div>
</section>

<section class="panel customer-panel">
    <div class="section-heading">
        <div>
            <h3>新着・注目商品</h3>
            <p class="help-text">青系アクセントのEC風レイアウトで、価格と在庫を見比べやすくしています。</p>
        </div>
        <a class="button-link button-secondary" href="/products">すべての商品を見る</a>
    </div>
    <section class="product-grid compact-product-grid">
        <?php foreach ($newArrivalProducts as $product): ?>
            <article class="product-card customer-product-card">
                <div class="product-card-header">
                    <p class="product-no"><?= e((string) $product['product_no']) ?></p>
                    <p class="<?= e((string) $product['availability_class']) ?> product-status">
                        <?= e((string) $product['availability_label']) ?>
                    </p>
                </div>
                <h3><?= e((string) $product['name']) ?></h3>
                <p class="customer-product-meta"><?= e((string) $product['category']) ?> / <?= e((string) $product['maker']) ?></p>
                <p class="customer-price">¥<?= number_format((int) $product['price']) ?></p>
                <div class="customer-card-footer">
                    <span>在庫2: <?= e((string) $product['stock_quantity_2']) ?></span>
                    <a class="text-link" href="/products?name=<?= urlencode((string) $product['name']) ?>">詳細を見る</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</section>
