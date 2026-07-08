<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Console</p>
        <h2>注文受付係向け注文詳細</h2>
        <p class="lead compact">注文内容の参照専用画面です。購入者情報と明細を確認できます。</p>
        <p><a class="text-link" href="<?= e(app_path('/staff/receptionist/orders')) ?>">登録済み注文一覧へ戻る</a></p>
    </div>

    <aside class="status-card">
        <h3>注文基本情報</h3>
        <dl>
            <div>
                <dt>注文番号</dt>
                <dd><?= e((string) $order['order_no']) ?></dd>
            </div>
            <div>
                <dt>注文日</dt>
                <dd><?= e((string) $order['order_date']) ?></dd>
            </div>
            <div>
                <dt>注文種別</dt>
                <dd><?= e((string) $order['order_type']) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<section class="panel">
    <div class="detail-split">
        <div class="detail-card">
            <h3>購入者情報</h3>
            <dl class="detail-list">
                <div>
                    <dt>購入者氏名</dt>
                    <dd><?= e((string) $order['customer_name']) ?></dd>
                </div>
                <div>
                    <dt>住所</dt>
                    <dd><?= e((string) $order['customer_address']) ?></dd>
                </div>
                <div>
                    <dt>連絡先</dt>
                    <dd><?= e((string) $order['customer_contact']) ?></dd>
                </div>
            </dl>
        </div>

        <div class="detail-card">
            <h3>決済・発送状態</h3>
            <dl class="detail-list">
                <div>
                    <dt>支払い方法</dt>
                    <dd><?= e((string) $order['payment_method_label']) ?></dd>
                </div>
                <div>
                    <dt>支払い状態</dt>
                    <dd><?= e((string) $order['payment_status_label']) ?></dd>
                </div>
                <div>
                    <dt>発送状態</dt>
                    <dd><?= e((string) $order['shipping_status_label']) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table admin-data-table">
            <thead>
                <tr>
                    <th>画像</th>
                    <th>商品番号</th>
                    <th>商品名</th>
                    <th>単価</th>
                    <th>数量</th>
                    <th>小計</th>
                </tr>
            </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="admin-thumb-cell">
                                <img
                                    class="admin-thumb"
                                    src="<?= e((string) ($item['image_url'] ?? product_image_url((string) ($item['image_path'] ?? '')))) ?>"
                                    alt="<?= e((string) $item['product_name']) ?>"
                                    data-fallback-src="<?= e(product_image_url('')) ?>"
                                >
                            </td>
                            <td><?= e((string) $item['product_no']) ?></td>
                            <td><?= e((string) $item['product_name']) ?></td>
                            <td>¥<?= number_format((int) $item['unit_price']) ?></td>
                        <td><?= e((string) $item['quantity']) ?></td>
                        <td>¥<?= number_format((int) $item['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="order-summary">
        <dl class="detail-list">
            <div>
                <dt>商品小計</dt>
                <dd>¥<?= number_format((int) $order['subtotal']) ?></dd>
            </div>
            <div>
                <dt>手数料</dt>
                <dd>¥<?= number_format((int) $order['fee']) ?></dd>
            </div>
            <div>
                <dt>配送料</dt>
                <dd>¥<?= number_format((int) $order['shipping_fee']) ?></dd>
            </div>
            <div class="total-row">
                <dt>合計金額</dt>
                <dd>¥<?= number_format((int) $order['total_amount']) ?></dd>
            </div>
        </dl>
    </div>
</section>
