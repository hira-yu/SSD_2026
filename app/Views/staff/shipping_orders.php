<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Shipping Workspace</p>
        <h2>商品発送係向け未発送注文一覧</h2>
        <p class="lead compact">発送可能な注文と、まだ支払い待ちの注文を確認できます。</p>
        <p><a class="text-link" href="/staff/shipper">商品発送係トップへ戻る</a></p>
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

<section class="panel">
    <h3>発送可能な注文</h3>
    <?php if ($shippable === []): ?>
        <p class="empty-state">発送可能な未発送注文はありません。</p>
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
                        <th>合計金額</th>
                        <th>状態</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shippable as $order): ?>
                        <tr>
                            <td><?= e((string) $order['order_no']) ?></td>
                            <td><?= e(substr((string) $order['order_date'], 0, 10)) ?></td>
                            <td><?= e((string) $order['customer_name']) ?></td>
                            <td><?= e((string) $order['payment_method_label']) ?></td>
                            <td><?= e((string) $order['payment_status_label']) ?></td>
                            <td><?= e((string) $order['shipping_status_label']) ?></td>
                            <td>¥<?= number_format((int) $order['total_amount']) ?></td>
                            <td class="status-ok"><?= e((string) $order['shipping_eligibility']['label']) ?></td>
                            <td><a class="text-link" href="/staff/shipper/orders/<?= e((string) $order['order_no']) ?>">詳細</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h3>支払い待ち / 発送対象外の未発送注文</h3>
    <?php if ($waiting === []): ?>
        <p class="empty-state">支払い待ちの未発送注文はありません。</p>
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
                        <th>合計金額</th>
                        <th>状態</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waiting as $order): ?>
                        <tr>
                            <td><?= e((string) $order['order_no']) ?></td>
                            <td><?= e(substr((string) $order['order_date'], 0, 10)) ?></td>
                            <td><?= e((string) $order['customer_name']) ?></td>
                            <td><?= e((string) $order['payment_method_label']) ?></td>
                            <td><?= e((string) $order['payment_status_label']) ?></td>
                            <td><?= e((string) $order['shipping_status_label']) ?></td>
                            <td>¥<?= number_format((int) $order['total_amount']) ?></td>
                            <td class="status-muted"><?= e((string) $order['shipping_eligibility']['label']) ?></td>
                            <td><a class="text-link" href="/staff/shipper/orders/<?= e((string) $order['order_no']) ?>">詳細</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
