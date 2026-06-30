<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Shopping Cart</p>
        <h2>カート</h2>
        <p class="lead compact">選択した商品と数量、合計金額を確認できます。</p>
        <p><a class="text-link" href="/products">商品一覧へ戻る</a></p>
    </div>

    <aside class="status-card">
        <h3>カート概要</h3>
        <dl>
            <div>
                <dt>商品点数</dt>
                <dd><?= e((string) $item_count) ?></dd>
            </div>
            <div>
                <dt>商品小計</dt>
                <dd>¥<?= number_format((int) $subtotal) ?></dd>
            </div>
            <div>
                <dt>合計金額</dt>
                <dd>¥<?= number_format((int) $total_amount) ?></dd>
            </div>
        </dl>
    </aside>
</section>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e((string) $successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage): ?>
    <div class="alert alert-error"><?= e((string) $errorMessage) ?></div>
<?php endif; ?>

<?php if ($warnings !== []): ?>
    <section class="panel">
        <h3>ご確認ください</h3>
        <ul class="error-list">
            <?php foreach ($warnings as $warning): ?>
                <li><?= e((string) $warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($items === []): ?>
    <section class="panel">
        <p class="empty-state">カートは空です。商品一覧から追加してください。</p>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="table-wrap">
            <table class="data-table cart-table">
                <thead>
                    <tr>
                        <th>商品番号</th>
                        <th>商品名</th>
                        <th>単価</th>
                        <th>数量</th>
                        <th>小計</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e((string) $item['product_no']) ?></td>
                            <td>
                                <?= e((string) $item['product_name']) ?>
                                <?php if (!empty($item['warning'])): ?>
                                    <p class="cart-warning"><?= e((string) $item['warning']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>¥<?= number_format((int) $item['unit_price']) ?></td>
                            <td>
                                <form class="cart-quantity-form" method="post" action="/cart/update">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <input type="number" name="quantity" min="0" value="<?= e((string) $item['quantity']) ?>" inputmode="numeric">
                                    <button class="button-link button-submit button-small" type="submit">更新</button>
                                </form>
                            </td>
                            <td>¥<?= number_format((int) $item['line_total']) ?></td>
                            <td>
                                <form method="post" action="/cart/remove">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
                                    <button class="button-link button-secondary button-submit button-small" type="submit">削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary-card">
            <dl class="detail-list">
                <div>
                    <dt>商品小計</dt>
                    <dd>¥<?= number_format((int) $subtotal) ?></dd>
                </div>
                <div>
                    <dt>配送料</dt>
                    <dd>¥<?= number_format((int) $shipping_fee) ?></dd>
                </div>
                <div class="total-row">
                    <dt>合計金額</dt>
                    <dd>¥<?= number_format((int) $total_amount) ?></dd>
                </div>
            </dl>
        </div>

        <div class="search-actions">
            <a class="button-link button-secondary" href="/products">商品一覧へ戻る</a>
            <a class="button-link" href="/checkout">注文情報入力へ進む</a>
        </div>
    </section>
<?php endif; ?>
