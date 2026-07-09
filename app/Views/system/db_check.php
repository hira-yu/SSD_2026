<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">System Status</p>
        <h2>DB接続確認</h2>
        <p class="lead compact">アプリケーションとデータベース間の接続状態を確認します。</p>
    </div>
    <aside class="status-card">
        <h3>確認担当者</h3>
        <dl>
            <div>
                <dt>担当者名</dt>
                <dd><?= e((string) ($user['user_name'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>ロール</dt>
                <dd><?= e((string) $roleLabel) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<section class="panel">
    <div class="panel-heading-bar">
        <h3>接続診断結果</h3>
        <span class="<?= $result['success'] ? 'status-ok' : 'status-ng' ?>">
            <?= e((string) $result['message']) ?>
        </span>
    </div>
    <dl class="detail-list">
        <div>
            <dt>DB接続</dt>
            <dd><?= $result['success'] ? '成功' : '失敗' ?></dd>
        </div>
        <div>
            <dt>使用中のDB_DRIVER</dt>
            <dd><?= e((string) $result['driver']) ?></dd>
        </div>
        <div>
            <dt>DB種別</dt>
            <dd><?= e((string) $result['db_type']) ?></dd>
        </div>
        <div>
            <dt>現在時刻</dt>
            <dd><?= e((string) $result['checked_at']) ?></dd>
        </div>
        <div>
            <dt>SELECT 1 の結果</dt>
            <dd><?= e((string) ($result['query_result'] ?? '-')) ?></dd>
        </div>
    </dl>

    <p class="help-text">
        失敗時の詳細は画面に出さず、<code>logs/app.log</code> に記録します。
    </p>
</section>
