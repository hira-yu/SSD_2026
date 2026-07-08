<?php

declare(strict_types=1);

$footerCategorySeed = $headerCategoryOptions ?? [];

if (!is_array($footerCategorySeed) || $footerCategorySeed === []) {
    $footerCategorySeed = [
        ['value' => 'PC・周辺機器'],
        ['value' => '事務用品'],
        ['value' => '家電'],
        ['value' => '生活用品'],
        ['value' => 'モバイルアクセサリ'],
        ['value' => 'オーディオ'],
        ['value' => 'DIY・工具'],
        ['value' => '季節家電'],
    ];
}

$footerCategoryLabels = array_values(array_map(
    static fn (array $item): string => (string) ($item['value'] ?? ''),
    array_filter($footerCategorySeed, static fn (array $item): bool => trim((string) ($item['value'] ?? '')) !== '')
));

if ($footerCategoryLabels === []) {
    $footerCategoryLabels = ['PC・周辺機器', '事務用品', '家電', '生活用品'];
}

$footerCategoryColumns = array_chunk(array_slice($footerCategoryLabels, 0, 12), 6);
$footerInformationColumns = [
    [
        'heading' => 'メーカーから商品を選ぶ',
        'links' => [
            ['label' => 'メーカーから探す', 'url' => '/products'],
            ['label' => 'お気に入り商品', 'url' => '/favorites'],
        ],
    ],
    [
        'heading' => 'はじめてのお客様へ',
        'links' => [
            ['label' => 'お買い物の流れ', 'url' => '/checkout'],
            ['label' => '配送・納期', 'url' => '/shipping-guide'],
            ['label' => '商品を探す', 'url' => '/products'],
        ],
    ],
    [
        'heading' => 'アフターサービス',
        'links' => [
            ['label' => '修理', 'url' => '/after-service'],
            ['label' => '返品・交換', 'url' => '/returns'],
            ['label' => '配送設置サービス', 'url' => '/shipping-guide'],
        ],
    ],
    [
        'heading' => '便利なサービス',
        'links' => [
            ['label' => '受け取りサービス', 'url' => '/stores'],
            ['label' => 'サイトマップ', 'url' => '/sitemap'],
            [
                'label' => is_array($authUser ?? null) && !empty($authUser['authenticated'])
                    ? '担当者トップ'
                    : '担当者ログイン',
                'url' => is_array($authUser ?? null) && !empty($authUser['authenticated'])
                    ? $authService->destinationForRole((string) ($authUser['role'] ?? ''))
                    : '/login',
            ],
        ],
    ],
];

$footerPolicyLinks = [
    ['label' => 'お客様サポートトップ', 'url' => '/'],
    ['label' => '店舗のご案内', 'url' => '/stores'],
    ['label' => 'ご利用規約', 'url' => '/terms'],
    ['label' => '個人情報保護方針', 'url' => '/privacy'],
    ['label' => '特定商取引法に基づく表示', 'url' => '/commercial-transactions'],
];
$appJsVersion = is_file(base_path('public/assets/js/app.js')) ? (string) filemtime(base_path('public/assets/js/app.js')) : '1';
$addressJsVersion = is_file(base_path('public/assets/js/address-autofill.js')) ? (string) filemtime(base_path('public/assets/js/address-autofill.js')) : '1';
?>
</main>
<footer class="site-footer">
    <div class="container">
        <?php if (current_path() === '/login' || str_starts_with(current_path(), '/staff')): ?>
            <p>IPUT EC 管理画面 | 学習目的の試作システムです。</p>
        <?php else: ?>
            <div class="customer-footer-mega">
                <div class="customer-footer-column-group">
                    <?php foreach ($footerCategoryColumns as $index => $column): ?>
                        <section class="customer-footer-column">
                            <h3 class="<?= $index === 0 ? '' : 'customer-footer-heading-spacer' ?>">
                                <?= $index === 0 ? 'カテゴリから選ぶ' : 'カテゴリ' ?>
                            </h3>
                            <ul>
                                <?php foreach ($column as $label): ?>
                                    <li><a href="<?= e(app_path('/products')) ?>?category=<?= urlencode($label) ?>"><?= e($label) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endforeach; ?>
                </div>

                <div class="customer-footer-column-group customer-footer-service-columns">
                    <?php foreach ($footerInformationColumns as $column): ?>
                        <section class="customer-footer-column">
                            <h3><?= e((string) $column['heading']) ?></h3>
                            <ul>
                                <?php foreach ($column['links'] as $link): ?>
                                    <li><a href="<?= e(app_path((string) $link['url'])) ?>"><?= e((string) $link['label']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="customer-footer-bottom">
                <div class="customer-footer-brand">IPUT EC</div>
                <nav class="customer-footer-policy-links" aria-label="フッターリンク">
                    <?php foreach ($footerPolicyLinks as $link): ?>
                        <a href="<?= e(app_path((string) $link['url'])) ?>"><?= e((string) $link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
                <p>Copyright (C) IPUT EC All Rights Reserved.</p>
            </div>
        <?php endif; ?>
    </div>
</footer>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<script src="<?= e(app_path('/assets/js/app.js')) ?>?v=<?= e($appJsVersion) ?>"></script>
<script src="<?= e(app_path('/assets/js/address-autofill.js')) ?>?v=<?= e($addressJsVersion) ?>"></script>
</body>
</html>
