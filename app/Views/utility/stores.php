<?php

declare(strict_types=1);
?>
<section class="market-utility-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <span>店舗のご案内</span>
    </div>

    <section class="market-utility-hero">
        <div>
            <p class="market-utility-kicker">Store Guide</p>
            <h1>店舗でも受け取りや相談に対応</h1>
            <p>オンライン注文と店頭サポートをつなげ、受け取り、修理、法人相談まで案内できる構成にしています。</p>
        </div>
        <div class="market-utility-stat">
            <strong><?= e((string) count($stores)) ?></strong>
            <span>ご案内店舗</span>
        </div>
    </section>

    <div class="market-store-grid">
        <?php foreach ($stores as $store): ?>
            <article class="market-store-card">
                <h2><?= e((string) $store['name']) ?></h2>
                <dl>
                    <div>
                        <dt>住所</dt>
                        <dd><?= e((string) $store['address']) ?></dd>
                    </div>
                    <div>
                        <dt>営業時間</dt>
                        <dd><?= e((string) $store['hours']) ?></dd>
                    </div>
                </dl>
                <ul class="market-store-service-list">
                    <?php foreach ($store['services'] as $service): ?>
                        <li><?= e((string) $service) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="market-store-actions">
                    <a class="button-link button-secondary" href="/products">
                        <i data-lucide="search" aria-hidden="true"></i>
                        商品を探す
                    </a>
                    <a class="button-link button-submit" href="/checkout">
                        <i data-lucide="clipboard-check" aria-hidden="true"></i>
                        注文手続きへ
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
