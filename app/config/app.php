<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'IPUT EC'),
    'admin_name' => (string) env('APP_ADMIN_NAME', 'IPUT EC 管理画面'),
    'env' => (string) env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => (string) env('APP_URL', 'http://localhost:8000'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Tokyo'),
    'session_name' => (string) env('SESSION_NAME', 'TSUHAN_SESSION'),
    'planned_features' => [
        '商品検索',
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
    'online_order' => [
        'shipping_fee' => 660,
        'payment_fee' => 0,
        'payment_method' => 'credit',
        'payment_status' => 'paid',
        'shipping_status' => 'unshipped',
        'order_type' => 'online',
        'cart_session_key' => 'online_cart',
        'checkout_draft_session_key' => 'online_checkout_draft',
        'checkout_confirmation_session_key' => 'online_checkout_confirmation',
        'demo_notice' => 'これはデモ用の疑似決済です。実在する個人情報や本物のカード情報は入力しないでください。',
        'demo_card_example' => [
            'number' => '4111111111111111',
            'holder' => 'TARO YAMADA',
            'expiry' => '12/30',
            'security_code' => '123',
        ],
    ],
    'customer_ui' => [
        'service_name' => 'IPUT EC',
        'tagline' => '学内デモ向けPC・周辺機器ストア',
        'shipping_copy' => '在庫がある商品はすぐに引当処理へ進みます。',
        'support_copy' => 'これは授業デモ用のEC画面です。実在する個人情報や本物のカード情報は入力しないでください。',
    ],
];
