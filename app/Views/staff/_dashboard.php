<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Staff Workspace</p>
        <h2><?= e((string) $roleLabel) ?>トップ</h2>
        <p class="lead compact">
            <?= e((string) ($user['user_name'] ?? '')) ?> さん向けの仮トップ画面です。
            今後の業務機能実装に備えたメニューを表示しています。
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
    <h3>業務メニュー</h3>
    <div class="menu-grid">
        <?php foreach ($menuItems as $item): ?>
            <article class="menu-card">
                <h4><?= e((string) $item) ?></h4>
                <p>今後この画面から操作できるように実装します。</p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
