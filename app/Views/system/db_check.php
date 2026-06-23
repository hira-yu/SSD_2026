<?php

declare(strict_types=1);
?>
<section class="panel">
    <h2>DB接続確認</h2>
    <p class="<?= $result['success'] ? 'status-ok' : 'status-ng' ?>">
        <?= e((string) $result['message']) ?>
    </p>

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
