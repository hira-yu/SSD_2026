<?php

declare(strict_types=1);
?>
<section class="market-order-page">
    <div class="market-breadcrumb">
        <a href="<?= e(app_path('/')) ?>">トップ</a>
        <span>カート</span>
    </div>

    <div class="market-step-bar">
        <div class="market-step-item is-active">1. カート</div>
        <div class="market-step-item">2. 注文情報入力</div>
        <div class="market-step-item">3. 注文内容確認</div>
        <div class="market-step-item">4. 注文完了</div>
    </div>

    <div class="market-cart-page-header">
        <h2>ショッピングカート</h2>
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
            <a class="button-link button-submit" href="<?= e(app_path('/products')) ?>">商品一覧へ進む</a>
        </section>
    <?php else: ?>
        <div class="market-cart-layout">
            <section class="market-cart-list">
                <div class="market-cart-list-head" aria-hidden="true">
                    <span class="market-cart-list-head-product">商品</span>
                    <span class="market-cart-list-head-price">価格</span>
                    <span class="market-cart-list-head-qty">数量</span>
                </div>
                <?php foreach ($items as $item): ?>
                    <?php
                    $deliverySchedule = is_array($item['delivery_schedule'] ?? null) ? $item['delivery_schedule'] : [];
                    $deadlineLabel = product_delivery_deadline_label($deliverySchedule);
                    ?>
                    <article class="market-cart-item">
                        <div class="market-cart-image">
                            <img
                                src="<?= e((string) $item['image_url']) ?>"
                                alt="<?= e((string) $item['product_name']) ?>"
                                data-fallback-src="<?= e(product_image_url('')) ?>"
                            >
                        </div>

                        <div class="market-cart-main">
                            <div class="market-cart-heading">
                                <div>
                                    <p class="market-product-meta"><?= e((string) $item['maker']) ?></p>
                                    <h3 class="market-cart-title">
                                        <a href="<?= e(app_path('/products/' . (int) $item['product_id'])) ?>"><?= e((string) $item['product_name']) ?></a>
                                    </h3>
                                </div>
                                <div class="market-cart-price-block">
                                    <p class="market-price-row">¥<?= number_format((int) $item['unit_price']) ?></p>
                                    <span>(税込)</span>
                                </div>
                            </div>

                            <div class="market-cart-stock-row">
                                <?php if (!empty($item['warning'])): ?>
                                    <p class="market-stock-copy status-ng"><?= e((string) $item['warning']) ?></p>
                                <?php elseif (!empty($item['availability_label'])): ?>
                                    <p class="market-stock-copy <?= e((string) $item['availability_class']) ?>"><?= e((string) $item['availability_label']) ?></p>
                                <?php endif; ?>

                                <?php if (empty($item['warning']) && ($deliverySchedule['summary_type'] ?? '') === 'orderable'): ?>
                                    <p class="market-cart-delivery-note market-detail-delivery-note-strong">
                                        今から
                                        <span class="market-detail-delivery-emphasis"><?= e($deadlineLabel) ?></span>
                                        のご注文で、
                                        <?php if (!empty($deliverySchedule['supports_same_day'])): ?>
                                            <span class="market-detail-delivery-emphasis">本日中に</span>
                                            お届けします。
                                        <?php else: ?>
                                            <span class="market-detail-delivery-emphasis"><?= e((string) ($deliverySchedule['arrival_date_label'] ?? '')) ?></span>
                                            までにお届けします。
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="market-cart-utility">
                                <form class="market-cart-remove-form" method="post" action="<?= e(app_path('/cart/remove')) ?>">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <button class="button-link button-ghost button-small" type="submit">削除</button>
                                </form>
                            </div>
                        </div>

                        <div class="market-cart-side">
                            <div class="market-cart-actions">
                                <form class="market-cart-qty-form" method="post" action="<?= e(app_path('/cart/update')) ?>">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <label for="cart-qty-<?= e((string) $item['product_id']) ?>">数量</label>
                                    <input id="cart-qty-<?= e((string) $item['product_id']) ?>" type="number" name="quantity" min="0" value="<?= e((string) $item['quantity']) ?>" inputmode="numeric">
                                    <button class="button-link button-secondary button-small" type="submit">更新</button>
                                </form>
                            </div>
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
                        <a class="button-link button-submit button-full" href="<?= e(app_path('/checkout')) ?>">購入手続きに進む</a>
                        <a class="button-link button-secondary button-full" href="<?= e(app_path('/products')) ?>">買い物を続ける</a>
                    </div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</section>
