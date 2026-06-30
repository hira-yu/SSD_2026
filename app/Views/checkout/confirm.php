<?php

declare(strict_types=1);
?>
<section class="checkout-shell">
    <div class="section-intro">
        <p class="eyebrow">Order Confirmation</p>
        <h2>ご注文内容の確認</h2>
        <p>商品、お届け先、お支払い情報をご確認のうえ、ご注文を確定してください。</p>
    </div>

    <div class="confirmation-grid">
        <section class="confirmation-main">
            <div class="confirmation-card">
                <h3>お届け先</h3>
                <dl class="detail-list">
                    <div>
                        <dt>お名前</dt>
                        <dd><?= e((string) ($customerSummary['name'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt>フリガナ</dt>
                        <dd><?= e((string) ($customerSummary['name_kana'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt>郵便番号</dt>
                        <dd><?= e((string) ($customerSummary['postal_code'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt>住所</dt>
                        <dd>
                            <?= e((string) ($customerSummary['address'] ?? '')) ?>
                            <?php if (!empty($customerSummary['building'])): ?>
                                <br><?= e((string) $customerSummary['building']) ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>電話番号</dt>
                        <dd><?= e((string) ($customerSummary['contact'] ?? '')) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="confirmation-card">
                <h3>お支払い情報</h3>
                <dl class="detail-list">
                    <div>
                        <dt>支払い方法</dt>
                        <dd>クレジットカード</dd>
                    </div>
                    <div>
                        <dt>カード番号</dt>
                        <dd><?= e((string) ($cardSummary['masked_card_number'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt>名義人</dt>
                        <dd><?= e((string) ($cardSummary['cardholder_name'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt>有効期限</dt>
                        <dd><?= e((string) ($cardSummary['card_expiry'] ?? '')) ?></dd>
                    </div>
                </dl>
                <p class="form-help-text"><?= e((string) $demoNotice) ?></p>
            </div>

            <div class="confirmation-card">
                <h3>商品内容</h3>
                <ul class="confirmation-item-list">
                    <?php foreach (($cart['items'] ?? []) as $item): ?>
                        <li>
                            <img
                                src="<?= e((string) $item['image_url']) ?>"
                                alt="<?= e((string) $item['product_name']) ?>"
                                data-fallback-src="/assets/img/products/placeholder.svg"
                            >
                            <div>
                                <strong><?= e((string) $item['product_name']) ?></strong>
                                <span><?= e((string) $item['quantity']) ?>点 / 単価 ¥<?= number_format((int) $item['unit_price']) ?></span>
                            </div>
                            <strong>¥<?= number_format((int) $item['line_total']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <aside class="checkout-summary-panel">
            <div class="summary-card">
                <h3>お支払い金額</h3>
                <dl class="summary-list">
                    <div>
                        <dt>商品小計</dt>
                        <dd>¥<?= number_format((int) ($cart['subtotal'] ?? 0)) ?></dd>
                    </div>
                    <div>
                        <dt>送料</dt>
                        <dd>¥<?= number_format((int) ($cart['shipping_fee'] ?? 0)) ?></dd>
                    </div>
                    <div class="total-row">
                        <dt>合計</dt>
                        <dd>¥<?= number_format((int) ($cart['total_amount'] ?? 0)) ?></dd>
                    </div>
                </dl>

                <div class="search-actions stacked-actions">
                    <a class="button-link button-secondary button-full" href="/checkout">入力内容を修正する</a>
                    <form method="post" action="/checkout/complete">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                        <button class="button-link button-submit button-full" type="submit">注文を確定する</button>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</section>
