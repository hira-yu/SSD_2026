<?php

declare(strict_types=1);
?>
<section class="hero">
    <div>
        <p class="eyebrow">Project Skeleton</p>
        <h2><?= e($appName) ?></h2>
        <p class="lead">
            授業プロジェクト向けに、SQLite と MariaDB を設定で切り替えられる
            素の PHP 構成を準備しました。
        </p>
    </div>
    <div class="status-card">
        <h3>開発環境</h3>
        <dl>
            <div>
                <dt>APP_ENV</dt>
                <dd><?= e($appEnv) ?></dd>
            </div>
            <div>
                <dt>DB_DRIVER</dt>
                <dd><?= e($dbDriver) ?></dd>
            </div>
        </dl>
    </div>
</section>

<section class="panel">
    <h3>今後実装予定の機能</h3>
    <ul class="feature-list">
        <?php foreach ($plannedFeatures as $feature): ?>
            <li><?= e((string) $feature) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h3>確認用リンク</h3>
    <div class="action-links">
        <a class="button-link" href="/products">商品一覧・商品検索へ</a>
        <a class="button-link button-secondary" href="/system/db-check">DB接続確認ページへ</a>
    </div>
</section>
