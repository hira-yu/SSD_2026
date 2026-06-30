<?php

declare(strict_types=1);
?>
<section class="checkout-shell">
    <div class="order-complete-card">
        <p class="eyebrow">Order Complete</p>
        <h2>ご注文が完了しました</h2>
        <p>ご注文内容を受け付けました。注文番号はお問い合わせ時にも必要になります。</p>

        <div class="done-order-number">
            <span>注文番号</span>
            <strong><?= e((string) $order['order_no']) ?></strong>
        </div>

        <div class="completion-status-row">
            <div class="status-summary-box">
                <span>支払い状態</span>
                <strong class="status-ok">支払済</strong>
            </div>
            <div class="status-summary-box">
                <span>発送状態</span>
                <strong class="status-muted">未発送</strong>
            </div>
        </div>
    </div>

    <div class="confirmation-grid">
        <section class="confirmation-main">
            <div class="confirmation-card">
                <h3>ご注文商品</h3>
                <ul class="confirmation-item-list">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <img
                                src="<?= e((string) ($item['image_url'] ?? product_image_url((string) ($item['image_path'] ?? '')))) ?>"
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
                <h3>ご請求金額</h3>
                <dl class="summary-list">
                    <div>
                        <dt>商品小計</dt>
                        <dd>¥<?= number_format((int) $order['subtotal']) ?></dd>
                    </div>
                    <div>
                        <dt>送料</dt>
                        <dd>¥<?= number_format((int) $order['shipping_fee']) ?></dd>
                    </div>
                    <div class="total-row">
                        <dt>合計</dt>
                        <dd>¥<?= number_format((int) $order['total_amount']) ?></dd>
                    </div>
                </dl>

                <div class="search-actions stacked-actions">
                    <a class="button-link button-submit button-full" href="/products">商品一覧へ戻る</a>
                </div>
            </div>
        </aside>
    </div>
</section>
