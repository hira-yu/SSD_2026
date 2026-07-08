<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Tools</p>
        <h2>電話/FAX注文確認</h2>
        <p class="lead compact">内容を確認し、問題がなければ注文を確定してください。</p>
    </div>

    <aside class="status-card">
        <h3>購入者情報</h3>
        <dl>
            <div>
                <dt>氏名</dt>
                <dd><?= e((string) $form['customer_name']) ?></dd>
            </div>
            <div>
                <dt>連絡先</dt>
                <dd><?= e((string) $form['customer_contact']) ?></dd>
            </div>
            <div>
                <dt>支払い方法</dt>
                <dd><?= e((string) $paymentLabel) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<section class="panel">
    <h3>注文内容</h3>
    <div class="detail-card">
        <p><strong>住所:</strong> <?= nl2br(e((string) $form['customer_address'])) ?></p>
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

    <div class="order-summary">
        <dl class="detail-list">
            <div>
                <dt>商品小計</dt>
                <dd>¥<?= number_format((int) $totals['subtotal']) ?></dd>
            </div>
            <div>
                <dt>手数料</dt>
                <dd>¥<?= number_format((int) $totals['fee']) ?></dd>
            </div>
            <div>
                <dt>配送料</dt>
                <dd>¥<?= number_format((int) $totals['shipping_fee']) ?></dd>
            </div>
            <div class="total-row">
                <dt>合計金額</dt>
                <dd>¥<?= number_format((int) $totals['total_amount']) ?></dd>
            </div>
        </dl>
    </div>

    <p class="help-text"><?= e((string) $paymentGuide) ?></p>

    <form method="post" action="<?= e(app_path('/staff/receptionist/orders')) ?>" class="search-actions">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
        <input type="hidden" name="customer_name" value="<?= e((string) $form['customer_name']) ?>">
        <input type="hidden" name="customer_contact" value="<?= e((string) $form['customer_contact']) ?>">
        <input type="hidden" name="payment_method" value="<?= e((string) $form['payment_method']) ?>">
        <textarea name="customer_address" hidden><?= e((string) $form['customer_address']) ?></textarea>
        <?php foreach ($items as $item): ?>
            <input type="hidden" name="product_ids[]" value="<?= e((string) $item['product_id']) ?>">
            <input type="hidden" name="quantities[]" value="<?= e((string) $item['quantity']) ?>">
        <?php endforeach; ?>
        <button class="button-link button-submit" type="submit">注文確定ボタン</button>
        <button class="button-link button-secondary button-submit" type="button" onclick="history.back()">戻るボタン</button>
    </form>
</section>
