<?php

declare(strict_types=1);
?>
<section class="auth-shell">
    <div class="auth-card">
        <p class="eyebrow">Staff Sign In</p>
        <h2>担当者ログイン</h2>
        <p class="lead compact">
            注文受付係、会計係、商品発送係の担当者専用ログインです。
        </p>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error" role="alert">
                <?= e((string) $errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php $successMessage = get_flash('success'); ?>
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" role="status">
                <?= e((string) $successMessage) ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="/login">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">

            <label class="form-field">
                <span>ログインID</span>
                <input type="text" name="login_id" value="<?= e((string) $loginId) ?>" autocomplete="username" required>
            </label>

            <label class="form-field">
                <span>パスワード</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="button-link button-submit" type="submit">ログイン</button>
        </form>

        <div class="panel auth-hint">
            <h3>初期ログイン情報</h3>
            <ul class="feature-list">
                <li>注文受付係: <code>reception01</code> / <code>reception123</code></li>
                <li>会計係: <code>account01</code> / <code>account123</code></li>
                <li>商品発送係: <code>shipper01</code> / <code>shipper123</code></li>
            </ul>
        </div>
    </div>
</section>
