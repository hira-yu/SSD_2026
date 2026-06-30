<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Staff Workspace</p>
        <h2><?= e((string) $roleLabel) ?>トップ</h2>
        <p class="lead compact">
            <?= e((string) ($user['user_name'] ?? '')) ?> さん向けの業務メニューです。
            本日の確認対象や登録・照会画面へここから移動できます。
        </p>
    </div>

    <aside class="status-card">
        <h3>ログイン情報</h3>
        <dl>
            <div>
                <dt>担当者名</dt>
                <dd><?= e((string) ($user['user_name'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>ログインID</dt>
                <dd><?= e((string) ($user['login_id'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>ロール</dt>
                <dd><?= e((string) $roleLabel) ?></dd>
            </div>
        </dl>
        <form method="post" action="/logout" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">ログアウト</button>
        </form>
    </aside>
</section>

<section class="panel">
    <div class="panel-heading-bar">
        <h3>業務メニュー</h3>
    </div>
    <div class="menu-grid">
        <?php foreach ($menuItems as $item): ?>
            <article class="menu-card">
                <h4><?= e((string) ($item['title'] ?? '')) ?></h4>
                <p><?= e((string) ($item['description'] ?? '')) ?></p>
                <?php if (!empty($item['url'])): ?>
                    <p class="menu-card-action"><a class="text-link" href="<?= e((string) $item['url']) ?>">画面を開く</a></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
