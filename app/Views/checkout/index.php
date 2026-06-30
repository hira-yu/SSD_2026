<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Checkout</p>
        <h2>ネット注文情報入力</h2>
        <p class="lead compact">購入者情報と疑似クレジットカード情報を入力してください。</p>
        <p><a class="text-link" href="/cart">カートへ戻る</a></p>
    </div>

    <aside class="status-card">
        <h3>注文サマリー</h3>
        <dl>
            <div>
                <dt>商品点数</dt>
                <dd><?= e((string) ($cart['item_count'] ?? 0)) ?></dd>
            </div>
            <div>
                <dt>商品小計</dt>
                <dd>¥<?= number_format((int) ($cart['subtotal'] ?? 0)) ?></dd>
            </div>
            <div>
                <dt>合計金額</dt>
                <dd>¥<?= number_format((int) ($cart['total_amount'] ?? 0)) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<?php if ($successMessage ?? null): ?>
    <div class="alert alert-success"><?= e((string) $successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage ?? null): ?>
    <div class="alert alert-error"><?= e((string) $errorMessage) ?></div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="alert alert-error">
        入力内容をご確認ください。
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="panel">
    <div class="checkout-demo-note">
        <h3>デモ用注意書き</h3>
        <p><?= e((string) $demoNotice) ?></p>
        <p class="help-text">
            例: <?= e((string) ($demoCardExample['number'] ?? '')) ?>
            / <?= e((string) ($demoCardExample['holder'] ?? '')) ?>
            / <?= e((string) ($demoCardExample['expiry'] ?? '')) ?>
            / <?= e((string) ($demoCardExample['security_code'] ?? '')) ?>
        </p>
    </div>

    <form class="checkout-form" method="post" action="/checkout/confirm">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">

        <div class="detail-split">
            <div class="detail-card">
                <h3>購入者情報</h3>
                <div class="form-field">
                    <label for="customer_name">購入者氏名</label>
                    <input id="customer_name" type="text" name="customer_name" value="<?= e((string) ($form['customer_name'] ?? '')) ?>">
                </div>
                <div class="form-field">
                    <label for="customer_address">住所</label>
                    <textarea id="customer_address" name="customer_address" rows="4"><?= e((string) ($form['customer_address'] ?? '')) ?></textarea>
                </div>
                <div class="form-field">
                    <label for="customer_contact">連絡先</label>
                    <input id="customer_contact" type="text" name="customer_contact" value="<?= e((string) ($form['customer_contact'] ?? '')) ?>" placeholder="例: 090-1234-5678">
                </div>
            </div>

            <div class="detail-card">
                <h3>疑似クレジットカード情報</h3>
                <div class="form-field">
                    <label for="card_number">カード番号</label>
                    <input id="card_number" type="text" name="card_number" value="" autocomplete="off" inputmode="numeric" placeholder="13〜19桁の数字">
                </div>
                <div class="form-field">
                    <label for="cardholder_name">名義人</label>
                    <input id="cardholder_name" type="text" name="cardholder_name" value="<?= e((string) ($form['cardholder_name'] ?? '')) ?>" placeholder="例: TARO YAMADA">
                </div>
                <div class="checkout-inline-fields">
                    <div class="form-field">
                        <label for="card_expiry">有効期限</label>
                        <input id="card_expiry" type="text" name="card_expiry" value="<?= e((string) ($form['card_expiry'] ?? '')) ?>" placeholder="MM/YY">
                    </div>
                    <div class="form-field">
                        <label for="security_code">セキュリティコード</label>
                        <input id="security_code" type="password" name="security_code" value="" autocomplete="off" inputmode="numeric" placeholder="3〜4桁">
                    </div>
                </div>
            </div>
        </div>

        <div class="cart-summary-card">
            <dl class="detail-list">
                <div>
                    <dt>商品小計</dt>
                    <dd>¥<?= number_format((int) ($cart['subtotal'] ?? 0)) ?></dd>
                </div>
                <div>
                    <dt>配送料</dt>
                    <dd>¥<?= number_format((int) ($cart['shipping_fee'] ?? 0)) ?></dd>
                </div>
                <div class="total-row">
                    <dt>合計金額</dt>
                    <dd>¥<?= number_format((int) ($cart['total_amount'] ?? 0)) ?></dd>
                </div>
            </dl>
        </div>

        <div class="search-actions">
            <a class="button-link button-secondary" href="/cart">カートへ戻る</a>
            <button class="button-link button-submit" type="submit">注文確認へ進む</button>
        </div>
    </form>
</section>
