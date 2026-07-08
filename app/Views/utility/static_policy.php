<?php

declare(strict_types=1);
?>
<section class="market-utility-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <span><?= e((string) $title) ?></span>
    </div>

    <section class="market-utility-hero">
        <div>
            <p class="market-utility-kicker"><?= e((string) $kicker) ?></p>
            <h1><?= e((string) $title) ?></h1>
            <p>このページは学内デモ用ECサイトの表示確認を目的として設置しています。不特定多数への公開や実取引を前提としたものではありません。</p>
        </div>
        <div class="market-utility-stat">
            <strong><?= e((string) count($sections)) ?></strong>
            <span>確認項目</span>
        </div>
    </section>

    <div class="market-sitemap-grid">
        <?php foreach ($sections as $section): ?>
            <section class="market-sitemap-card">
                <h2><?= e((string) $section['heading']) ?></h2>
                <p><?= e((string) $section['body']) ?></p>
            </section>
        <?php endforeach; ?>
    </div>
</section>
