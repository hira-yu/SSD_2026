<?php

declare(strict_types=1);
?>
<section class="market-order-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <span>カート</span>
    </div>

    <div class="market-step-bar">
        <div class="market-step-item is-active">1. カート</div>
        <div class="market-step-item">2. 注文情報入力</div>
        <div class="market-step-item">3. 注文内容確認</div>
        <div class="market-step-item">4. 注文完了</div>
    </div>

    <div class="market-results-summary">
        <div>
            <h2>ショッピングカート</h2>
            <p>商品内容、数量、合計金額を確認してご注文手続きへ進めます。</p>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success" role="status"><?= e((string) $successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-error" role="alert"><?= e((string) $errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($warnings !== []): ?>
        <div class="alert alert-error" role="alert">
            <strong>カート内容をご確認ください。</strong>
            <ul class="error-list">
                <?php foreach ($warnings as $warning): ?>
                    <li><?= e((string) $warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <section class="market-empty-state">
            <h3>カートに商品が入っていません</h3>
            <p>商品一覧から気になる商品を追加してください。</p>
            <a class="button-link button-submit" href="/products">商品一覧へ進む</a>
        </section>
    <?php else: ?>
        <div class="market-cart-layout">
            <section class="market-cart-list">
                <?php foreach ($items as $item): ?>
                    <article class="market-cart-item">
                        <div class="market-cart-image">
                            <img
                                src="<?= e((string) $item['image_url']) ?>"
                                alt="<?= e((string) $item['product_name']) ?>"
                                data-fallback-src="/assets/img/products/placeholder.svg"
                            >
                        </div>

                        <div class="market-cart-main">
                            <div class="market-cart-heading">
                                <div>
                                    <p class="market-product-meta"><?= e((string) $item['category']) ?> / <?= e((string) $item['maker']) ?></p>
                                    <h3 class="market-cart-title"><?= e((string) $item['product_name']) ?></h3>
                                    <p class="market-product-code"><?= e((string) $item['product_no']) ?></p>
                                </div>
                                <p class="market-price-row">¥<?= number_format((int) $item['unit_price']) ?></p>
                            </div>

                            <?php if (!empty($item['warning'])): ?>
                                <p class="market-stock-copy status-ng"><?= e((string) $item['warning']) ?></p>
                            <?php endif; ?>

                            <div class="market-cart-actions">
                                <form class="market-cart-qty-form" method="post" action="/cart/update">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <label for="cart-qty-<?= e((string) $item['product_id']) ?>">数量</label>
                                    <input id="cart-qty-<?= e((string) $item['product_id']) ?>" type="number" name="quantity" min="0" value="<?= e((string) $item['quantity']) ?>" inputmode="numeric">
                                    <button class="button-link button-secondary button-small" type="submit">数量を更新</button>
                                </form>

                                <form method="post" action="/cart/remove">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <button class="button-link button-ghost button-small" type="submit">削除</button>
                                </form>
                            </div>
                        </div>

                        <div class="market-cart-total">
                            <span>小計</span>
                            <strong>¥<?= number_format((int) $item['line_total']) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="market-order-summary">
                <div class="market-summary-card">
                    <div class="market-panel-heading">ご注文内容</div>
                    <dl class="market-summary-list">
                        <div>
                            <dt>商品点数</dt>
                            <dd><?= e((string) $item_count) ?>点</dd>
                        </div>
                        <div>
                            <dt>商品小計</dt>
                            <dd>¥<?= number_format((int) $subtotal) ?></dd>
                        </div>
                        <div>
                            <dt>送料</dt>
                            <dd>¥<?= number_format((int) $shipping_fee) ?></dd>
                        </div>
                        <div class="total-row">
                            <dt>合計</dt>
                            <dd>¥<?= number_format((int) $total_amount) ?></dd>
                        </div>
                    </dl>
                    <div class="market-summary-actions">
                        <a class="button-link button-submit button-full" href="/checkout">注文へ進む</a>
                        <a class="button-link button-secondary button-full" href="/products">買い物を続ける</a>
                    </div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</section>
