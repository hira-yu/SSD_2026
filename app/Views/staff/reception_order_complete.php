<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Tools</p>
        <h2>注文登録が完了しました</h2>
        <p class="lead compact">電話/FAX注文を登録し、在庫数量2の更新まで完了しました。</p>
        <p><a class="text-link" href="/staff/receptionist/orders/new">続けて新しい注文を登録する</a></p>
    </div>

    <aside class="status-card">
        <h3>注文情報</h3>
        <dl>
            <div>
                <dt>注文番号</dt>
                <dd><?= e((string) $order['order_no']) ?></dd>
            </div>
            <div>
                <dt>支払い方法</dt>
                <dd><?= e((string) $paymentLabel) ?></dd>
            </div>
            <div>
                <dt>合計金額</dt>
                <dd>¥<?= number_format((int) $order['total_amount']) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<section class="panel">
    <h3>支払い案内</h3>
    <p class="lead compact"><?= e((string) $paymentGuide) ?></p>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
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
</section>
