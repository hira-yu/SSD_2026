<?php

declare(strict_types=1);
?>
<section class="staff-hero customer-hero">
    <div class="panel customer-panel">
        <p class="eyebrow">Checkout Confirm</p>
        <h2>ネット注文確認</h2>
        <p class="lead compact">入力内容と金額をご確認のうえ、注文を確定してください。</p>
        <p><a class="text-link" href="/checkout">入力画面へ戻る</a></p>
    </div>

    <aside class="status-card customer-status-card">
        <h3>疑似決済確認</h3>
        <dl>
            <div>
                <dt>カード番号</dt>
                <dd><?= e((string) ($cardSummary['masked_card_number'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>名義人</dt>
                <dd><?= e((string) ($cardSummary['cardholder_name'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>確認結果</dt>
                <dd class="status-ok"><?= e((string) ($cardSummary['validation_result'] ?? '')) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<section class="panel customer-panel">
    <div class="checkout-demo-note">
        <h3>デモ用注意書き</h3>
        <p><?= e((string) $demoNotice) ?></p>
    </div>

    <div class="detail-split">
        <div class="detail-card">
            <h3>購入者情報</h3>
            <dl class="detail-list">
                <div>
                    <dt>購入者氏名</dt>
                    <dd><?= e((string) ($form['customer_name'] ?? '')) ?></dd>
                </div>
                <div>
                    <dt>住所</dt>
                    <dd><?= nl2br(e((string) ($form['customer_address'] ?? ''))) ?></dd>
                </div>
                <div>
                    <dt>連絡先</dt>
                    <dd><?= e((string) ($form['customer_contact'] ?? '')) ?></dd>
                </div>
            </dl>
        </div>

        <div class="detail-card">
            <h3>決済情報</h3>
            <dl class="detail-list">
                <div>
                    <dt>支払い方法</dt>
                    <dd>クレジットカード</dd>
                </div>
                <div>
                    <dt>支払い状態</dt>
                    <dd class="status-ok">支払済</dd>
                </div>
                <div>
                    <dt>有効期限</dt>
                    <dd><?= e((string) ($cardSummary['card_expiry'] ?? '')) ?></dd>
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
                <?php foreach (($cart['items'] ?? []) as $item): ?>
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
        <a class="button-link button-secondary" href="/checkout">戻る</a>
        <form method="post" action="/checkout/complete">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">注文を確定する</button>
        </form>
    </div>
</section>
