<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Accounting Workspace</p>
        <h2>会計係向け注文検索</h2>
        <p class="lead compact">注文番号、注文日、購入者氏名、支払い状態で注文を検索し、未払い注文を支払済へ更新できます。</p>
        <p><a class="text-link" href="/staff/accountant">会計係トップへ戻る</a></p>
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
        <form method="post" action="/logout" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">ログアウト</button>
        </form>
    </aside>
</section>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e((string) $successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage): ?>
    <div class="alert alert-error"><?= e((string) $errorMessage) ?></div>
<?php endif; ?>

<section class="panel search-panel">
    <form class="search-form accountant-search-form" method="get" action="/staff/accountant/orders">
        <div class="form-field">
            <label for="order_no">注文番号</label>
            <input id="order_no" type="text" name="order_no" value="<?= e((string) $filters['order_no']) ?>" placeholder="例: ORD202606230001">
        </div>
        <div class="form-field">
            <label for="order_date">注文日</label>
            <input id="order_date" type="date" name="order_date" value="<?= e((string) $filters['order_date']) ?>">
        </div>
        <div class="form-field">
            <label for="customer_name">購入者氏名</label>
            <input id="customer_name" type="text" name="customer_name" value="<?= e((string) $filters['customer_name']) ?>" placeholder="例: 山田">
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
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索する</button>
            <a class="button-link button-secondary" href="/staff/accountant/orders">条件をクリア</a>
        </div>
    </form>
</section>

<section class="panel">
    <?php if ($orders === []): ?>
        <p class="empty-state">該当する注文がありません。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>注文番号</th>
                        <th>注文日</th>
                        <th>購入者氏名</th>
                        <th>支払い方法</th>
                        <th>支払い状態</th>
                        <th>発送状態</th>
                        <th>商品小計</th>
                        <th>手数料</th>
                        <th>配送料</th>
                        <th>合計金額</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a class="text-link" href="/staff/accountant/orders/<?= e((string) $order['order_no']) ?>"><?= e((string) $order['order_no']) ?></a></td>
                            <td><?= e(substr((string) $order['order_date'], 0, 10)) ?></td>
                            <td><?= e((string) $order['customer_name']) ?></td>
                            <td><?= e((string) $order['payment_method_label']) ?></td>
                            <td><?= e((string) $order['payment_status_label']) ?></td>
                            <td><?= e((string) $order['shipping_status_label']) ?></td>
                            <td>¥<?= number_format((int) $order['subtotal']) ?></td>
                            <td>¥<?= number_format((int) $order['fee']) ?></td>
                            <td>¥<?= number_format((int) $order['shipping_fee']) ?></td>
                            <td>¥<?= number_format((int) $order['total_amount']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="text-link" href="/staff/accountant/orders/<?= e((string) $order['order_no']) ?>">詳細</a>
                                    <?php if (!empty($order['can_update_payment'])): ?>
                                        <form method="post" action="/staff/accountant/orders/<?= e((string) $order['order_no']) ?>/payment">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                            <button class="button-link button-submit button-small" type="submit">支払済へ更新</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-muted"><?= e((string) $order['payment_status_label']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
