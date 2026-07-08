<?php

declare(strict_types=1);
?>
<section class="market-utility-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <span>サイトマップ</span>
    </div>

    <section class="market-utility-hero">
        <div>
            <p class="market-utility-kicker">Site Map</p>
            <h1>IPUT EC の導線を一覧で確認</h1>
            <p>商品検索、注文手続き、店舗案内、担当者向け入口まで、主要ページへ直接移動できます。</p>
        </div>
        <div class="market-utility-stat">
            <strong><?= e((string) count($sections)) ?></strong>
            <span>主要グループ</span>
        </div>
    </section>

    <div class="market-sitemap-grid">
        <?php foreach ($sections as $section): ?>
            <section class="market-sitemap-card">
                <h2><?= e((string) $section['heading']) ?></h2>
                <ul class="market-sitemap-links">
                    <?php foreach ($section['links'] as $link): ?>
                        <li><a href="<?= e(app_path((string) $link['url'])) ?>"><?= e((string) $link['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <section class="market-sitemap-card">
            <h2>カテゴリから探す</h2>
            <ul class="market-sitemap-links">
                <?php foreach (array_slice($categoryOptions, 0, 12) as $category): ?>
                    <li>
                        <a href="<?= e(app_path('/products')) ?>?category=<?= urlencode((string) $category['value']) ?>">
                            <?= e((string) $category['value']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</section>
