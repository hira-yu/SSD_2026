<?php

declare(strict_types=1);

$mode = (string) ($mode ?? 'new');
$isEdit = $mode === 'edit';
$form = isset($form) && is_array($form) ? $form : [];
$productId = is_array($product ?? null) ? (int) ($product['id'] ?? 0) : 0;
$action = $isEdit ? '/staff/product-manager/products/' . $productId : '/staff/product-manager/products';
?>
<section class="staff-hero">
    <div>
        <p class="eyebrow">Product Management</p>
        <h2><?= $isEdit ? '商品編集' : '商品新規追加' ?></h2>
        <p>商品情報、在庫、セール価格、期間限定販売を設定します。</p>
        <p><a class="text-link" href="<?= e(app_path('/staff/product-manager/products')) ?>">商品一覧へ戻る</a></p>
    </div>
    <div class="panel">
        <h3>ログイン情報</h3>
        <dl class="definition-list">
            <div><dt>担当者</dt><dd><?= e((string) ($user['user_name'] ?? '')) ?></dd></div>
            <div><dt>ロール</dt><dd><?= e((string) $roleLabel) ?></dd></div>
        </dl>
    </div>
</section>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success" role="status"><?= e((string) $successMessage) ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error" role="alert"><?= e((string) $errorMessage) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="panel">
    <form class="staff-product-form" method="post" action="<?= e(app_path($action)) ?>" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
        <div class="staff-form-grid">
            <label class="form-field">
                <span>商品番号</span>
                <input type="text" name="product_no" value="<?= e((string) ($form['product_no'] ?? '')) ?>" required>
            </label>
            <label class="form-field">
                <span>商品名</span>
                <input type="text" name="name" value="<?= e((string) ($form['name'] ?? '')) ?>" required>
            </label>
            <label class="form-field">
                <span>価格</span>
                <input type="number" name="price" min="0" step="1" value="<?= e((string) ($form['price'] ?? '')) ?>" required>
            </label>
            <label class="form-field">
                <span>カテゴリ</span>
                <input type="text" name="category" value="<?= e((string) ($form['category'] ?? '')) ?>" required>
            </label>
            <label class="form-field">
                <span>メーカー</span>
                <input type="text" name="maker" value="<?= e((string) ($form['maker'] ?? '')) ?>" required>
            </label>
            <label class="form-field">
                <span>商品画像</span>
                <input type="file" name="product_image" accept="image/png,image/jpeg,image/webp,image/gif">
            </label>
        </div>

        <?php if ($isEdit && !empty($form['image_path'])): ?>
            <div class="staff-current-image">
                <img src="<?= e(product_image_url((string) $form['image_path'])) ?>" alt="現在の商品画像" data-fallback-src="<?= e(product_image_url('')) ?>">
                <div>
                    <strong>現在の画像</strong>
                    <p><?= e((string) $form['image_path']) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="staff-form-grid">
            <label class="form-field">
                <span>セール価格</span>
                <input type="number" name="sale_price" min="0" step="1" value="<?= e((string) ($form['sale_price'] ?? '')) ?>" placeholder="未設定なら空欄">
            </label>
            <label class="form-field">
                <span>セール開始</span>
                <input type="text" name="sale_starts_at" value="<?= e((string) ($form['sale_starts_at'] ?? '')) ?>" placeholder="2026/07/08 09:00">
            </label>
            <label class="form-field">
                <span>セール終了</span>
                <input type="text" name="sale_ends_at" value="<?= e((string) ($form['sale_ends_at'] ?? '')) ?>" placeholder="2026/07/08 21:00">
            </label>
            <label class="form-field">
                <span>販売開始</span>
                <input type="text" name="available_from" value="<?= e((string) ($form['available_from'] ?? '')) ?>" placeholder="2026/07/08 09:00">
            </label>
            <label class="form-field">
                <span>販売終了</span>
                <input type="text" name="available_until" value="<?= e((string) ($form['available_until'] ?? '')) ?>" placeholder="2026/07/08 21:00">
            </label>
        </div>

        <div class="search-actions">
            <button class="button-link button-submit" type="submit"><?= $isEdit ? '商品情報を更新' : '商品を登録' ?></button>
            <a class="button-link button-secondary" href="<?= e(app_path('/staff/product-manager/products')) ?>">キャンセル</a>
        </div>
    </form>
</section>

<?php if ($isEdit): ?>
    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Current Stock</p>
                <h3>現在の在庫状況</h3>
            </div>
        </div>
        <dl class="definition-list">
            <div>
                <dt>在庫1</dt>
                <dd><?= e((string) ($product['stock_quantity_1'] ?? 0)) ?></dd>
            </div>
            <div>
                <dt>在庫2</dt>
                <dd><?= e((string) ($product['stock_quantity_2'] ?? 0)) ?></dd>
            </div>
        </dl>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Stock Receiving</p>
                <h3>仕入れ入庫</h3>
            </div>
        </div>
        <p>入庫数量を入力すると、在庫1・在庫2の両方へ加算します。</p>
        <form class="search-form" method="post" action="<?= e(app_path('/staff/product-manager/products/' . $productId . '/stock')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <label class="form-field">
                <span>入庫数量</span>
                <input type="number" name="quantity" min="1" step="1" value="1" required>
            </label>
            <div class="search-actions">
                <button class="button-link button-submit" type="submit">入庫する</button>
            </div>
        </form>
    </section>
<?php endif; ?>
