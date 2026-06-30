<?php

declare(strict_types=1);

$productNo = (string) ($filters['product_no'] ?? '');
$name = (string) ($filters['name'] ?? '');
?>
<section class="staff-hero">
    <div class="panel">
        <p class="eyebrow">Receptionist Tools</p>
        <h2>注文受付係向け商品検索</h2>
        <p class="lead compact">
            商品番号と商品名で検索できます。両方指定した場合は AND 条件で絞り込みます。
        </p>
        <p><a class="text-link" href="/staff/receptionist">注文受付係トップへ戻る</a></p>
        <p><a class="text-link" href="/staff/receptionist/orders">登録済み注文一覧</a></p>
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
        <form method="post" action="/logout" class="logout-form">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrfToken) ?>">
            <button class="button-link button-submit" type="submit">ログアウト</button>
        </form>
    </aside>
</section>

<section class="panel search-panel admin-search-panel">
    <div class="panel-heading-bar">
        <h3>検索条件</h3>
    </div>
    <form class="search-form receptionist-search-form" method="get" action="/staff/receptionist/products">
        <div class="form-field">
            <label for="product_no">商品番号</label>
            <input id="product_no" type="text" name="product_no" value="<?= e($productNo) ?>" placeholder="例: PRD-001">
        </div>
        <div class="form-field">
            <label for="name">商品名</label>
            <input id="name" type="text" name="name" value="<?= e($name) ?>" placeholder="例: マウス">
        </div>
        <div class="search-actions">
            <button class="button-link button-submit" type="submit">検索</button>
            <a class="button-link button-secondary" href="/staff/receptionist/products">条件クリア</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading-bar">
        <h3>商品検索結果</h3>
    </div>
    <?php if ($products === []): ?>
        <p class="empty-state">該当する商品がありません。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>商品番号</th>
                        <th>商品名</th>
                        <th>単価</th>
                        <th>商品カテゴリ</th>
                        <th>メーカー名</th>
                        <th>在庫数量1</th>
                        <th>在庫数量2</th>
                        <th>注文可能状態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e((string) $product['product_no']) ?></td>
                            <td><?= e((string) $product['name']) ?></td>
                            <td>¥<?= number_format((int) $product['price']) ?></td>
                            <td><?= e((string) $product['category']) ?></td>
                            <td><?= e((string) $product['maker']) ?></td>
                            <td><?= e((string) $product['stock_quantity_1']) ?></td>
                            <td><?= e((string) $product['stock_quantity_2']) ?></td>
                            <td class="<?= e((string) $product['availability_class']) ?>">
                                <?= e((string) $product['availability_label']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
