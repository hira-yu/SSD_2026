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
                    ['label' => '配送・納期', 'url' => '/shipping-guide'],
                    ['label' => '返品・交換', 'url' => '/returns'],
                    ['label' => 'サイトマップ', 'url' => '/sitemap'],
                    ['label' => '担当者ログイン', 'url' => '/login'],
                    ['label' => 'DB接続確認', 'url' => '/system/db-check'],
                ],
            ],
            [
                'heading' => '規約・方針',
                'links' => [
                    ['label' => 'ご利用規約', 'url' => '/terms'],
                    ['label' => '個人情報保護方針', 'url' => '/privacy'],
                    ['label' => '特定商取引法に基づく表示', 'url' => '/commercial-transactions'],
                    ['label' => '返品・交換', 'url' => '/returns'],
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

    public function terms(): void
    {
        $this->renderLegalPage('ご利用規約', 'Terms of Use', [
            ['heading' => '適用範囲', 'body' => '本規約は、学内デモ用ECサイト「IPUT EC」の閲覧、商品検索、カート、注文機能の利用に適用されます。本サイトは授業・検証目的の試作であり、実際の商品販売を目的とするものではありません。'],
            ['heading' => '利用上の注意', 'body' => '利用者は、虚偽の注文情報入力、第三者になりすました操作、システムの検証目的を超えるアクセスを行わないものとします。'],
            ['heading' => '注文情報の扱い', 'body' => '入力された注文情報は、デモシナリオにおける注文受付、会計、発送、商品管理の確認に利用します。実在の決済や配送は行われません。'],
            ['heading' => '免責', 'body' => '本サイトは学習目的で提供されるため、掲載情報、在庫、価格、配送予定、決済表示は実運用の保証を行うものではありません。'],
        ]);
    }

    public function privacy(): void
    {
        $this->renderLegalPage('個人情報保護方針', 'Privacy Policy', [
            ['heading' => '取得する情報', 'body' => '注文フォームで入力される氏名、住所、電話番号、注文内容、担当者ログイン情報などを、デモ環境の動作確認に必要な範囲で取り扱います。'],
            ['heading' => '利用目的', 'body' => '取得した情報は、注文登録、注文確認、会計処理、発送処理、商品管理機能の検証および授業内レビューのために利用します。'],
            ['heading' => '第三者提供', 'body' => '授業・検証目的の範囲を超えて第三者へ提供しません。ただし、動作確認に必要な範囲で担当教員または開発関係者が確認する場合があります。'],
            ['heading' => '管理', 'body' => 'デモ終了後は、必要に応じてデータベースを初期化し、不要な入力情報を保持しない運用とします。'],
        ]);
    }

    public function commercialTransactions(): void
    {
        $this->renderLegalPage('特定商取引法に基づく表示', 'Legal Notice', [
            ['heading' => '販売事業者', 'body' => 'IPUT EC 学内デモプロジェクト'],
            ['heading' => '所在地・連絡先', 'body' => '学内デモ用のため、実在の販売事業者情報は掲載していません。問い合わせは授業内の担当者へ行ってください。'],
            ['heading' => '販売価格', 'body' => '各商品ページに税込想定価格として表示します。表示価格はデモ用であり、実際の請求は発生しません。'],
            ['heading' => '支払方法・引渡時期', 'body' => 'クレジットカード等の表示はデモ入力です。実決済および実配送は行われません。'],
            ['heading' => '返品・キャンセル', 'body' => '実販売を伴わないため返品・返金は発生しません。デモ注文の取消は担当者画面またはDB初期化で扱います。'],
        ]);
    }

    public function returns(): void
    {
        $this->renderLegalPage('返品・交換について', 'Returns and Exchanges', [
            ['heading' => '基本方針', 'body' => '本サイトは学内デモ用であり、実際の商品発送・返品・返金は発生しません。'],
            ['heading' => 'デモ注文の修正', 'body' => '入力内容に誤りがある場合は、注文確定前にカートまたは注文情報入力画面で修正してください。確定後の確認は担当者向け注文確認画面で行います。'],
            ['heading' => '商品不備の扱い', 'body' => '商品不備、配送事故、交換対応などの表示は、EC業務フロー学習のための想定項目です。実在の商品対応ではありません。'],
        ]);
    }

    public function shippingGuide(): void
    {
        $this->renderLegalPage('配送・納期について', 'Shipping Guide', [
            ['heading' => '配送表示', 'body' => '配送予定や在庫表示はデモ用の情報です。実際の配送会社、配送日、送料請求は発生しません。'],
            ['heading' => '納期目安', 'body' => '商品詳細では在庫状態に応じた案内を表示しますが、学習目的の表示であり到着日を保証するものではありません。'],
            ['heading' => '店頭受け取り', 'body' => '店舗案内・店頭受け取りの表示は画面導線確認用です。実際の受け取り窓口はありません。'],
        ]);
    }

    public function afterService(): void
    {
        $this->renderLegalPage('アフターサービス', 'After Service', [
            ['heading' => '修理・保証', 'body' => '修理、保証、交換、リサイクル等の案内はECサイトとしての導線確認用です。実際の受付は行いません。'],
            ['heading' => '問い合わせ', 'body' => '学内デモの内容に関する確認は、授業内の担当者または開発メンバーへ行ってください。'],
        ]);
    }

    /**
     * @param array<int, array{heading: string, body: string}> $sections
     */
    private function renderLegalPage(string $title, string $kicker, array $sections): void
    {
        $catalog = $this->products->homePageData();

        $this->render('utility/static_policy', [
            'pageTitle' => $title,
            'title' => $title,
            'kicker' => $kicker,
            'sections' => $sections,
            'cartItemCount' => $this->cart->itemCount(),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            ...$catalog,
        ]);
    }
}
