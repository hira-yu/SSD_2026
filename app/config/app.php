<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', '通信販売システム'),
    'env' => (string) env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => (string) env('APP_URL', 'http://localhost:8000'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Tokyo'),
    'session_name' => (string) env('SESSION_NAME', 'TSUHAN_SESSION'),
    'planned_features' => [
        '商品検索',
        'ネット注文',
        '電話/FAX注文登録',
        '会計処理',
        '発送処理',
        '在庫管理',
        '担当者認証',
    ],
    'reception_order' => [
        'shipping_fee' => 660,
        'payment_fees' => [
            'bank' => 0,
            'convenience' => 220,
            'cod' => 330,
        ],
        'payment_methods' => [
            'bank' => '銀行振込',
            'convenience' => 'コンビニ決済',
            'cod' => '代金引換',
        ],
    ],
];
