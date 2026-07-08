<?php

declare(strict_types=1);

class StaffController extends Controller
{
    private AuthService $auth;

    /**
     * @var array<string, array<int, array<string, string|null>>>
     */
    private array $roleMenus = [
        'receptionist' => [
            ['title' => '電話/FAX注文登録', 'description' => '電話またはFAXで受け付けた注文を代理登録します。', 'url' => '/staff/receptionist/orders/new'],
            ['title' => '商品検索', 'description' => '商品番号や商品名で検索し、在庫状況を確認します。', 'url' => '/staff/receptionist/products'],
            ['title' => '注文内容確認', 'description' => '登録済み注文を検索し、注文詳細を確認します。', 'url' => '/staff/receptionist/orders'],
        ],
        'accountant' => [
            ['title' => '注文検索', 'description' => '注文番号、注文日、購入者氏名、支払い状態で注文を検索します。', 'url' => '/staff/accountant/orders'],
            ['title' => '支払い状態更新', 'description' => '未払い注文の支払い状態を支払済へ更新します。', 'url' => '/staff/accountant/orders'],
        ],
        'shipper' => [
            ['title' => '未発送注文一覧', 'description' => '発送対象の未発送注文と支払い待ち注文を確認します。', 'url' => '/staff/shipper/orders'],
            ['title' => '納品書・請求書表示', 'description' => '注文詳細画面で納品書情報と請求書情報を確認します。', 'url' => '/staff/shipper/orders'],
            ['title' => '発送状態更新', 'description' => '発送完了後に発送状態を発送済へ更新します。', 'url' => '/staff/shipper/orders'],
        ],
        'product_manager' => [
            ['title' => '商品一覧・編集', 'description' => '商品情報、価格、販売期間、セール設定を編集します。', 'url' => '/staff/product-manager/products'],
            ['title' => '商品新規追加', 'description' => '新しい商品を追加し、初期在庫を設定します。', 'url' => '/staff/product-manager/products/new'],
            ['title' => '仕入れ入庫', 'description' => '仕入れに伴う在庫数量を商品ごとに加算します。', 'url' => '/staff/product-manager/products'],
        ],
    ];

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function receptionist(): void
    {
        $this->renderStaffHome('receptionist');
    }

    public function accountant(): void
    {
        $this->renderStaffHome('accountant');
    }

    public function shipper(): void
    {
        $this->renderStaffHome('shipper');
    }

    public function productManager(): void
    {
        $this->renderStaffHome('product_manager');
    }

    private function renderStaffHome(string $role): void
    {
        $this->auth->authorizeRole($role);
        $user = $this->auth->user();

        $this->render('staff/' . $role, [
            'pageTitle' => $this->auth->roleLabel($role) . 'トップ',
            'user' => $user,
            'roleLabel' => $this->auth->roleLabel($role),
            'menuItems' => $this->roleMenus[$role] ?? [],
            'csrfToken' => csrf_token(),
        ]);
    }
}
