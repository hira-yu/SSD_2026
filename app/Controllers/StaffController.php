<?php

declare(strict_types=1);

class StaffController extends Controller
{
    private AuthService $auth;

    /**
     * @var array<string, array<int, string>>
     */
    private array $roleMenus = [
        'receptionist' => ['電話/FAX注文登録', '商品検索', '注文内容確認'],
        'accountant' => ['注文検索', '支払い状態更新'],
        'shipper' => ['未発送注文一覧', '納品書・請求書表示', '発送状態更新'],
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
