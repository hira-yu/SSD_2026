<?php

declare(strict_types=1);
?>
<section class="panel narrow-panel">
    <p class="eyebrow">Access Control</p>
    <h2>403 Forbidden</h2>
    <p class="lead compact">この画面へアクセスする権限がありません。</p>
    <dl class="detail-list">
        <div>
            <dt>必要なロール</dt>
            <dd><?= e((string) $requiredRoleLabel) ?></dd>
        </div>
        <div>
            <dt>現在のロール</dt>
            <dd><?= e((string) $currentRoleLabel) ?></dd>
        </div>
    </dl>
    <p><a class="button-link" href="<?= e(app_path('/')) ?>">トップへ戻る</a></p>
</section>
