<?php

declare(strict_types=1);

$filters = isset($filters) && is_array($filters) ? $filters : ['product_no' => '', 'name' => ''];
$formatManagedDateTime = static function (mixed $value): string {
    $value = trim((string) $value);

    return $value === '' ? '' : str_replace('-', '/', substr($value, 0, 16));
};
?>
<section class="staff-hero">
    <div>
        <p class="eyebrow">Product Management</p>
        <h2>商品管理</h2>
        <p>商品の新規追加、編集、セール設定、販売期間設定、仕入れ入庫を行います。</p>
        <p><a class="text-link" href="<?= e(app_path('/staff/product-manager')) ?>">商品管理トップへ戻る</a></p>
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

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Search</p>
            <h3>商品を検索</h3>
        </div>
        <a class="button-link button-submit" href="<?= e(app_path('/staff/product-manager/products/new')) ?>">商品を新規追加</a>
    </div>
    <form class="search-form receptionist-search-form" method="get" action="<?= e(app_path('/staff/product-manager/products')) ?>">
        <label class="form-field">
            <span>商品番号</span>
            <input type="text" name="product_no" value="<?= e((string) ($filters['product_no'] ?? '')) ?>" placeholder="PRD-001">
        </label>
        <label class="form-field">
            <span>商品名</span>
            <input type="text" name="name" value="<?= e((string) ($filters['name'] ?? '')) ?>" placeholder="商品名で検索">
        </label>
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索</button>
            <a class="button-link button-secondary" href="<?= e(app_path('/staff/product-manager/products')) ?>">条件クリア</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Products</p>
            <h3>登録商品一覧</h3>
        </div>
        <p><?= e((string) count($products)) ?>件</p>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>商品</th>
                    <th>カテゴリ</th>
                    <th>価格</th>
                    <th>セール</th>
                    <th>販売期間</th>
                    <th>在庫1</th>
                    <th>在庫2</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <strong><?= e((string) $product['product_no']) ?></strong><br>
                            <?= e((string) $product['name']) ?><br>
                            <small><?= e((string) $product['maker']) ?></small>
                        </td>
                        <td><?= e((string) $product['category']) ?></td>
                        <td>¥<?= number_format((int) $product['price']) ?></td>
                        <td>
                            <?php if (!empty($product['sale_price'])): ?>
                                ¥<?= number_format((int) $product['sale_price']) ?><br>
                                <small><?= e($formatManagedDateTime($product['sale_starts_at'] ?? '')) ?> - <?= e($formatManagedDateTime($product['sale_ends_at'] ?? '')) ?></small>
                            <?php else: ?>
                                未設定
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($product['available_from']) || !empty($product['available_until'])): ?>
                                <small><?= e($formatManagedDateTime($product['available_from'] ?? '')) ?> - <?= e($formatManagedDateTime($product['available_until'] ?? '')) ?></small>
                            <?php else: ?>
                                常時販売
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) $product['stock_quantity_1']) ?></td>
                        <td><?= e((string) $product['stock_quantity_2']) ?></td>
                        <td><a class="button-link button-secondary button-small" href="<?= e(app_path('/staff/product-manager/products/' . (string) $product['id'] . '/edit')) ?>">編集</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
