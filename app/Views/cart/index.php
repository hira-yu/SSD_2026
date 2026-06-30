<?php

declare(strict_types=1);
?>
<section class="cart-page-shell">
    <div class="section-intro">
        <p class="eyebrow">Shopping Cart</p>
        <h2>カート</h2>
        <p>商品内容、数量、合計金額を確認してご注文手続きへ進めます。</p>
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
        <section class="empty-result-card">
            <h3>カートに商品が入っていません</h3>
            <p>商品一覧から気になる商品を追加してください。</p>
            <a class="button-link button-submit" href="/products">商品一覧へ進む</a>
        </section>
    <?php else: ?>
        <div class="cart-layout-grid">
            <section class="cart-item-list">
                <?php foreach ($items as $item): ?>
                    <article class="cart-item-card">
                        <div class="cart-item-image">
                            <img
                                src="<?= e((string) $item['image_url']) ?>"
                                alt="<?= e((string) $item['product_name']) ?>"
                                data-fallback-src="/assets/img/products/placeholder.svg"
                            >
                        </div>

                        <div class="cart-item-main">
                            <div class="cart-item-header">
                                <div>
                                    <p class="product-card-meta"><?= e((string) $item['category']) ?> / <?= e((string) $item['maker']) ?></p>
                                    <h3><?= e((string) $item['product_name']) ?></h3>
                                    <p class="product-code"><?= e((string) $item['product_no']) ?></p>
                                </div>
                                <p class="cart-item-price">¥<?= number_format((int) $item['unit_price']) ?></p>
                            </div>

                            <?php if (!empty($item['warning'])): ?>
                                <p class="product-card-note status-ng"><?= e((string) $item['warning']) ?></p>
                            <?php endif; ?>

                            <div class="cart-item-actions">
                                <form class="cart-quantity-form" method="post" action="/cart/update">
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

                        <div class="cart-item-total">
                            <span>小計</span>
                            <strong>¥<?= number_format((int) $item['line_total']) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="cart-summary-panel">
                <div class="summary-card">
                    <h3>ご注文内容</h3>
                    <dl class="summary-list">
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
                    <a class="button-link button-submit button-full" href="/checkout">注文へ進む</a>
                    <a class="button-link button-secondary button-full" href="/products">買い物を続ける</a>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</section>
