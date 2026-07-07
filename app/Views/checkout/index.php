<?php

declare(strict_types=1);
?>
<section class="market-order-page">
    <div class="market-breadcrumb">
        <a href="/">トップ</a>
        <a href="/cart">カート</a>
        <span>ご注文手続き</span>
    </div>

    <div class="market-step-bar">
        <div class="market-step-item is-done">1. カート</div>
        <div class="market-step-item is-active">2. 注文情報入力</div>
        <div class="market-step-item">3. 注文内容確認</div>
        <div class="market-step-item">4. 注文完了</div>
    </div>

    <div class="market-results-summary">
        <div>
            <h2>ご注文手続き</h2>
            <p>お届け先とお支払い情報を入力して、内容確認へ進みます。</p>
        </div>
    </div>

    <?php if ($successMessage ?? null): ?>
        <div class="alert alert-success" role="status"><?= e((string) $successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage ?? null): ?>
        <div class="alert alert-error" role="alert"><?= e((string) $errorMessage) ?></div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert-error" role="alert">
            <strong>入力内容をご確認ください。</strong>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="market-checkout-layout">
        <form class="market-checkout-form" method="post" action="/checkout/confirm">
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
                    <input id="customer_contact" type="tel" name="customer_contact" value="<?= e((string) ($form['customer_contact'] ?? '')) ?>" autocomplete="tel" inputmode="tel" placeholder="例: 09012345678" required>
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

                <p class="form-help-text" data-address-autofill-status>郵便番号を入力すると、対応する住所候補を補完できます。</p>
            </section>

            <section class="market-form-section">
                <div class="market-panel-heading">お支払い情報</div>
                <div class="market-card-guidance">
                    <p><?= e((string) $demoNotice) ?></p>
                    <p>カード番号、有効期限、セキュリティコードをご入力ください。</p>
                </div>

                <div class="form-field">
                    <label for="card_number">カード番号</label>
                    <input id="card_number" type="text" name="card_number" value="" autocomplete="off" inputmode="numeric" maxlength="19" placeholder="半角数字 13〜19桁" required>
                </div>

                <div class="form-field">
                    <label for="cardholder_name">名義人</label>
                    <input id="cardholder_name" type="text" name="cardholder_name" value="<?= e((string) ($form['cardholder_name'] ?? '')) ?>" autocomplete="cc-name" placeholder="例: TARO YAMADA" required>
                </div>

                <div class="market-form-grid market-form-grid-three">
                    <div class="form-field">
                        <label for="card_expiry_month">有効期限 月</label>
                        <select id="card_expiry_month" name="card_expiry_month" autocomplete="cc-exp-month" required>
                            <option value="">月</option>
                            <?php foreach ($expiryMonthOptions as $month): ?>
                                <option value="<?= e((string) $month) ?>" <?= ((string) ($form['card_expiry_month'] ?? '')) === (string) $month ? 'selected' : '' ?>>
                                    <?= e((string) $month) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="card_expiry_year">有効期限 年</label>
                        <select id="card_expiry_year" name="card_expiry_year" autocomplete="cc-exp-year" required>
                            <option value="">年</option>
                            <?php foreach ($expiryYearOptions as $year): ?>
                                <option value="<?= e((string) $year) ?>" <?= ((string) ($form['card_expiry_year'] ?? '')) === (string) $year ? 'selected' : '' ?>>
                                    <?= e((string) $year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="security_code">セキュリティコード</label>
                        <input id="security_code" type="password" name="security_code" value="" autocomplete="off" inputmode="numeric" maxlength="4" placeholder="3〜4桁" required>
                    </div>
                </div>
            </section>

            <div class="market-summary-actions">
                <a class="button-link button-secondary" href="/cart">カートへ戻る</a>
                <button class="button-link button-submit" type="submit">入力内容を確認する</button>
            </div>
        </form>

        <aside class="market-order-summary">
            <div class="market-summary-card">
                <div class="market-panel-heading">ご注文内容</div>
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
                                <span><?= e((string) $item['quantity']) ?>点 / ¥<?= number_format((int) $item['line_total']) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

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
            </div>
        </aside>
    </div>
</section>
