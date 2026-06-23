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
            ['title' => '電話/FAX注文登録', 'description' => '電話またはFAXで受け付けた注文を代理登録します。', 'url' => null],
            ['title' => '商品検索', 'description' => '商品番号や商品名で検索し、在庫状況を確認します。', 'url' => '/staff/receptionist/products'],
            ['title' => '注文内容確認', 'description' => '受付済み注文の内容確認機能を今後追加します。', 'url' => null],
        ],
        'accountant' => [
            ['title' => '注文検索', 'description' => '注文番号、注文日、購入者氏名、支払い状態で注文を検索します。', 'url' => '/staff/accountant/orders'],
            ['title' => '支払い状態更新', 'description' => '未払い注文の支払い状態を支払済へ更新します。', 'url' => '/staff/accountant/orders'],
        ],
        'shipper' => [
            ['title' => '未発送注文一覧', 'description' => '未発送注文の確認機能を今後追加します。', 'url' => null],
            ['title' => '納品書・請求書表示', 'description' => '帳票表示機能を今後追加します。', 'url' => null],
            ['title' => '発送状態更新', 'description' => '発送状態の更新機能を今後追加します。', 'url' => null],
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
