<?php

declare(strict_types=1);
?>
<section class="market-order-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <a href="<?= e(app_path('/cart')) ?>">カート</a>
        <a href="<?= e(app_path('/checkout')) ?>">ご注文手続き</a>
        <span>注文完了</span>
    </div>

    <div class="market-step-bar">
        <div class="market-step-item is-done">1. カート</div>
        <div class="market-step-item is-done">2. 注文情報入力</div>
        <div class="market-step-item is-done">3. 注文内容確認</div>
        <div class="market-step-item is-active">4. 注文完了</div>
    </div>

    <div class="market-complete-box">
        <h2>ご注文が完了しました</h2>
        <p>ご注文内容を受け付けました。注文番号はお問い合わせ時にも必要になります。</p>

        <div class="market-complete-order-no">
            <span>注文番号</span>
            <strong><?= e((string) $order['order_no']) ?></strong>
        </div>

        <div class="market-complete-statuses">
            <div>
                <span>支払い状態</span>
                <strong class="status-ok">支払済</strong>
            </div>
            <div>
                <span>発送状態</span>
                <strong class="status-muted">未発送</strong>
            </div>
        </div>
    </div>

    <div class="market-confirm-layout">
        <section class="market-confirm-main">
            <div class="market-form-section">
                <div class="market-panel-heading">ご注文商品</div>
                <ul class="market-summary-item-list">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <img
                                src="<?= e((string) ($item['image_url'] ?? product_image_url((string) ($item['image_path'] ?? '')))) ?>"
                                alt="<?= e((string) $item['product_name']) ?>"
                                data-fallback-src="<?= e(product_image_url('')) ?>"
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
                <div class="market-panel-heading">ご請求金額</div>
                <dl class="market-summary-list">
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

                <div class="market-summary-actions">
                    <a class="button-link button-submit button-full" href="<?= e(app_path('/products')) ?>">商品一覧へ戻る</a>
                </div>
            </div>
        </aside>
    </div>
</section>
