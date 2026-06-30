<?php

declare(strict_types=1);
?>
<section class="panel checkout-done-panel">
    <p class="eyebrow">Order Complete</p>
    <h2>ご注文ありがとうございました</h2>
    <p class="lead">ネット注文を受け付けました。これはデモ用の疑似決済による注文です。</p>

    <div class="done-order-number">
        <span>注文番号</span>
        <strong><?= e((string) $order['order_no']) ?></strong>
    </div>

    <div class="detail-split">
        <div class="detail-card">
            <h3>注文状態</h3>
            <dl class="detail-list">
                <div>
                    <dt>支払い状態</dt>
                    <dd class="status-ok">支払済</dd>
                </div>
                <div>
                    <dt>発送状態</dt>
                    <dd class="status-muted">未発送</dd>
                </div>
                <div>
                    <dt>注文日時</dt>
                    <dd><?= e((string) $order['order_date']) ?></dd>
                </div>
            </dl>
        </div>

        <div class="detail-card">
            <h3>ご請求金額</h3>
            <dl class="detail-list">
                <div>
                    <dt>商品小計</dt>
                    <dd>¥<?= number_format((int) $order['subtotal']) ?></dd>
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
    </div>

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

    <div class="search-actions">
        <a class="button-link" href="/products">商品一覧へ戻る</a>
        <a class="button-link button-secondary" href="/cart">カートを見る</a>
    </div>
</section>
