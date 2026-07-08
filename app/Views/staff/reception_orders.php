<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Console</p>
        <h2>注文受付係向け注文一覧</h2>
        <p class="lead compact">登録済み注文を検索して、購入者情報と注文明細を確認できます。</p>
        <p><a class="text-link" href="<?= e(app_path('/staff/receptionist')) ?>">注文受付係トップへ戻る</a></p>
    </div>

    <aside class="status-card">
        <h3>ログイン情報</h3>
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
        <form method="post" action="<?= e(app_path('/logout')) ?>" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">ログアウト</button>
        </form>
    </aside>
</section>

<section class="panel search-panel admin-search-panel">
    <div class="panel-heading-bar">
        <h3>検索条件</h3>
    </div>
    <form class="search-form receptionist-order-search-form" method="get" action="<?= e(app_path('/staff/receptionist/orders')) ?>">
        <div class="form-field">
            <label for="order_no">注文番号</label>
            <input id="order_no" type="text" name="order_no" value="<?= e((string) $filters['order_no']) ?>">
        </div>
        <div class="form-field">
            <label for="order_date">注文日</label>
            <input id="order_date" type="date" name="order_date" value="<?= e((string) $filters['order_date']) ?>">
        </div>
        <div class="form-field">
            <label for="customer_name">購入者氏名</label>
            <input id="customer_name" type="text" name="customer_name" value="<?= e((string) $filters['customer_name']) ?>">
        </div>
        <div class="form-field">
            <label for="payment_method">支払い方法</label>
            <select id="payment_method" name="payment_method">
                <?php foreach ($paymentMethodOptions as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>" <?= ($filters['payment_method'] === $value) ? 'selected' : '' ?>>
                        <?= e((string) $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="payment_status">支払い状態</label>
            <select id="payment_status" name="payment_status">
                <?php foreach ($paymentStatusOptions as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>" <?= ($filters['payment_status'] === $value) ? 'selected' : '' ?>>
                        <?= e((string) $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="shipping_status">発送状態</label>
            <select id="shipping_status" name="shipping_status">
                <?php foreach ($shippingStatusOptions as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>" <?= ($filters['shipping_status'] === $value) ? 'selected' : '' ?>>
                        <?= e((string) $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索</button>
            <a class="button-link button-secondary" href="<?= e(app_path('/staff/receptionist/orders')) ?>">条件クリア</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading-bar">
        <h3>登録済み注文一覧</h3>
    </div>
    <?php if ($orders === []): ?>
        <p class="empty-state">該当する注文がありません。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table admin-data-table">
                <thead>
                    <tr>
                        <th>注文番号</th>
                        <th>注文日</th>
                        <th>購入者氏名</th>
                        <th>支払い方法</th>
                        <th>支払い状態</th>
                        <th>発送状態</th>
                        <th>合計金額</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= e((string) $order['order_no']) ?></td>
                            <td><?= e(substr((string) $order['order_date'], 0, 10)) ?></td>
                            <td><?= e((string) $order['customer_name']) ?></td>
                            <td><?= e((string) $order['payment_method_label']) ?></td>
                            <td><span class="admin-status-chip"><?= e((string) $order['payment_status_label']) ?></span></td>
                            <td><span class="admin-status-chip muted"><?= e((string) $order['shipping_status_label']) ?></span></td>
                            <td>¥<?= number_format((int) $order['total_amount']) ?></td>
                            <td><a class="text-link" href="<?= e(app_path('/staff/receptionist/orders/' . (string) $order['order_no'])) ?>">注文詳細を確認</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
