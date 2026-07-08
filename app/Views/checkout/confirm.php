<?php

declare(strict_types=1);
?>
<section class="market-order-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <a href="/cart">カート</a>
        <a href="/checkout">ご注文手続き</a>
        <span>ご注文内容の確認</span>
    </div>

    <div class="market-step-bar">
        <div class="market-step-item is-done">1. カート</div>
        <div class="market-step-item is-done">2. 注文情報入力</div>
        <div class="market-step-item is-active">3. 注文内容確認</div>
        <div class="market-step-item">4. 注文完了</div>
    </div>

    <div class="market-results-summary">
        <div>
            <h2>ご注文内容の確認</h2>
            <p>商品、お届け先、お支払い情報をご確認のうえ、ご注文を確定してください。</p>
        </div>
    </div>

    <div class="market-confirm-layout">
        <section class="market-confirm-main">
            <div class="market-form-section">
                <div class="market-panel-heading">お届け先</div>
                <dl class="market-detail-list">
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

            <div class="market-form-section">
                <div class="market-panel-heading">お支払い情報</div>
                <dl class="market-detail-list">
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

            <div class="market-form-section">
                <div class="market-panel-heading">商品内容</div>
                <ul class="market-summary-item-list">
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

        <aside class="market-order-summary">
            <div class="market-summary-card">
                <div class="market-panel-heading">お支払い金額</div>
                <dl class="market-summary-list">
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

                <div class="market-summary-actions">
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
