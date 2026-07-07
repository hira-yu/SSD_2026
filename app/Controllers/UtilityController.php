<?php

declare(strict_types=1);

class UtilityController extends Controller
{
    private ProductService $products;
    private FavoriteService $favorites;
    private CartService $cart;

    public function __construct()
    {
        $this->products = new ProductService();
        $this->favorites = new FavoriteService();
        $this->cart = new CartService();
    }

    public function sitemap(): void
    {
        $catalog = $this->products->homePageData();

        $sections = [
            [
                'heading' => 'お買い物',
                'links' => [
                    ['label' => 'トップ', 'url' => '/'],
                    ['label' => '商品一覧', 'url' => '/products'],
                    ['label' => 'お気に入り商品', 'url' => '/favorites'],
                    ['label' => 'カート', 'url' => '/cart'],
                    ['label' => 'ご注文手続き', 'url' => '/checkout'],
                ],
            ],
            [
                'heading' => 'ご利用案内',
                'links' => [
                    ['label' => '店舗のご案内', 'url' => '/stores'],
                    ['label' => 'サイトマップ', 'url' => '/sitemap'],
                    ['label' => '担当者ログイン', 'url' => '/login'],
                    ['label' => 'DB接続確認', 'url' => '/system/db-check'],
                ],
            ],
        ];

        $this->render('utility/sitemap', [
            'pageTitle' => 'サイトマップ',
            'sections' => $sections,
            'cartItemCount' => $this->cart->itemCount(),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            ...$catalog,
        ]);
    }

    public function stores(): void
    {
        $catalog = $this->products->homePageData();

        $stores = [
            [
                'name' => 'IPUT EC 東京',
                'address' => '東京都新宿区西新宿1丁目7-3',
                'hours' => '8:00-22:00',
                'services' => ['店頭受け取り', '修理相談', '法人見積もり'],
            ],
            [
                'name' => 'IPUT EC 名古屋',
                'address' => '愛知県名古屋市中村区名駅4丁目27番1号',
                'hours' => '9:00-20:00',
                'services' => ['配送相談', '家電設置相談', 'ギフト包装'],
            ],
            [
                'name' => 'IPUT EC 大阪',
                'address' => '大阪府大阪市北区梅田3丁目3番1号',
                'hours' => '8:30-21:30',
                'services' => ['即日受け取り', '法人窓口', 'アクセサリ相談'],
            ],
        ];

        $this->render('utility/stores', [
            'pageTitle' => '店舗のご案内',
            'stores' => $stores,
            'cartItemCount' => $this->cart->itemCount(),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            ...$catalog,
        ]);
    }
}
