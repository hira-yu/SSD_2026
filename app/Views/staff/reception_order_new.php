<?php

declare(strict_types=1);
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Tools</p>
        <h2>電話/FAX注文登録</h2>
        <p class="lead compact">電話またはFAXで受け付けた注文を代理登録します。必要な商品を選択し、確認画面へ進んでください。</p>
        <p><a class="text-link" href="<?= e(app_path('/staff/receptionist')) ?>">注文受付係トップへ戻る</a></p>
        <p><a class="text-link" href="<?= e(app_path('/staff/receptionist/orders')) ?>">登録済み注文一覧</a></p>
    </div>

    <aside class="status-card">
        <h3>ログイン情報</h3>
        <dl>
            <div>
                <dt>担当者名</dt>
                <dd><?= e((string) ($user['user_name'] ?? '')) ?></dd>
            </div>
            <div>
                <dt>ロール</dt>
                <dd><?= e((string) $roleLabel) ?></dd>
            </div>
        </dl>
        <form method="post" action="<?= e(app_path('/logout')) ?>" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">ログアウト</button>
        </form>
    </aside>
</section>

<section class="panel">
    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <strong>入力内容を確認してください。</strong>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(app_path('/staff/receptionist/orders/confirm')) ?>" class="reception-order-form">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">

        <section class="market-form-section">
            <div class="market-panel-heading">お客様情報</div>
            <div class="market-form-grid market-form-grid-two">
                <div class="form-field">
                    <label for="last_name">姓</label>
                    <input id="last_name" type="text" name="last_name" value="<?= e((string) ($form['last_name'] ?? '')) ?>" autocomplete="family-name" required>
                </div>
                <div class="form-field">
                    <label for="first_name">名</label>
                    <input id="first_name" type="text" name="first_name" value="<?= e((string) ($form['first_name'] ?? '')) ?>" autocomplete="given-name" required>
                </div>
                <div class="form-field">
                    <label for="last_name_kana">セイ</label>
                    <input id="last_name_kana" type="text" name="last_name_kana" value="<?= e((string) ($form['last_name_kana'] ?? '')) ?>" required>
                </div>
                <div class="form-field">
                    <label for="first_name_kana">メイ</label>
                    <input id="first_name_kana" type="text" name="first_name_kana" value="<?= e((string) ($form['first_name_kana'] ?? '')) ?>" required>
                </div>
            </div>
            <div class="form-field">
                <label for="customer_contact">電話番号</label>
                <input id="customer_contact" type="tel" name="customer_contact" value="<?= e((string) ($form['customer_contact'] ?? '')) ?>" autocomplete="tel" inputmode="tel" maxlength="11" placeholder="例: 09012345678" required>
            </div>
        </section>

        <section class="market-form-section" data-address-autofill-form>
            <div class="market-panel-heading">お届け先</div>
            <div class="market-form-grid market-form-grid-two">
                <div class="form-field">
                    <label for="postal_code">郵便番号</label>
                    <input id="postal_code" type="text" name="postal_code" value="<?= e((string) ($form['postal_code'] ?? '')) ?>" inputmode="numeric" maxlength="7" autocomplete="postal-code" placeholder="例: 1000001" required>
                </div>
                <div class="form-field align-end">
                    <button class="button-link button-secondary button-small" type="button" data-address-autofill-trigger>住所を自動入力</button>
                </div>
                <div class="form-field">
                    <label for="prefecture">都道府県</label>
                    <select id="prefecture" name="prefecture" autocomplete="address-level1" required>
                        <?php foreach ($prefectureOptions as $value => $label): ?>
                            <option value="<?= e((string) $value) ?>" <?= ((string) ($form['prefecture'] ?? '')) === (string) $value ? 'selected' : '' ?>>
                                <?= e((string) $label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="city">市区町村</label>
                    <input id="city" type="text" name="city" value="<?= e((string) ($form['city'] ?? '')) ?>" autocomplete="address-level2" required>
                </div>
            </div>
            <div class="form-field">
                <label for="address_line">町名・番地</label>
                <input id="address_line" type="text" name="address_line" value="<?= e((string) ($form['address_line'] ?? '')) ?>" autocomplete="address-line1" required>
            </div>
            <div class="form-field">
                <label for="building">建物名</label>
                <input id="building" type="text" name="building" value="<?= e((string) ($form['building'] ?? '')) ?>" autocomplete="address-line2">
            </div>
        </section>

        <section class="market-form-section">
            <div class="market-panel-heading">お支払い情報</div>
            <div class="form-field">
            <label for="payment_method">支払い方法</label>
            <select id="payment_method" name="payment_method" required>
                <?php foreach ($paymentOptions as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>" <?= (($form['payment_method'] ?? '') === $value) ? 'selected' : '' ?>>
                        <?= e((string) $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>
        </section>

        <div class="panel panel-subsection">
            <div class="section-heading">
                <div>
                    <h3>注文明細</h3>
                    <p class="help-text">商品と数量を指定してください。同一商品は数量を自動合算します。</p>
                </div>
                <button type="button" class="button-link button-secondary button-submit" data-add-order-row>商品追加ボタン</button>
            </div>

            <div class="table-wrap">
                <table class="data-table order-entry-table">
                    <thead>
                        <tr>
                            <th>商品選択</th>
                            <th>数量</th>
                        </tr>
                    </thead>
                    <tbody data-order-items>
                        <?php foreach (($form['items'] ?? []) as $item): ?>
                            <tr>
                                <td>
                                    <select name="product_ids[]">
                                        <option value="">商品を選択してください</option>
                                        <?php foreach ($productOptions as $product): ?>
                                            <option value="<?= e((string) $product['id']) ?>" <?= ((string) ($item['product_id'] ?? '') === (string) $product['id']) ? 'selected' : '' ?>>
                                                <?= e((string) $product['product_no']) ?> / <?= e((string) $product['name']) ?> / 在庫2: <?= e((string) $product['stock_quantity_2']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantities[]" min="1" step="1" value="<?= e((string) ($item['quantity'] ?? '1')) ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="search-actions">
            <button class="button-link button-submit" type="submit">注文確認へ進む</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading-bar">
        <h3>商品選択の参考</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table admin-data-table">
            <thead>
                <tr>
                    <th>画像</th>
                    <th>商品番号</th>
                    <th>商品名</th>
                    <th>在庫数量2</th>
                    <th>単価</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productOptions as $product): ?>
                    <tr>
                        <td class="admin-thumb-cell">
                            <img
                                class="admin-thumb"
                                src="<?= e(product_image_url((string) ($product['image_path'] ?? ''))) ?>"
                                alt="<?= e((string) $product['name']) ?>"
                                data-fallback-src="<?= e(product_image_url('')) ?>"
                            >
                        </td>
                        <td><?= e((string) $product['product_no']) ?></td>
                        <td><?= e((string) $product['name']) ?></td>
                        <td><?= e((string) $product['stock_quantity_2']) ?></td>
                        <td>¥<?= number_format((int) $product['price']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<template id="order-item-row-template">
    <tr>
        <td>
            <select name="product_ids[]">
                <option value="">商品を選択してください</option>
                <?php foreach ($productOptions as $product): ?>
                    <option value="<?= e((string) $product['id']) ?>">
                        <?= e((string) $product['product_no']) ?> / <?= e((string) $product['name']) ?> / 在庫2: <?= e((string) $product['stock_quantity_2']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="quantities[]" min="1" step="1" value="1">
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-add-order-row]');
    const tbody = document.querySelector('[data-order-items]');
    const template = document.querySelector('#order-item-row-template');

    if (!button || !tbody || !template) {
        return;
    }

    button.addEventListener('click', function () {
        const fragment = template.content.cloneNode(true);
        tbody.appendChild(fragment);
    });
});
</script>
