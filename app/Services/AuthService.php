<?php

declare(strict_types=1);

class AuthService
{
    private UserRepository $users;

    /**
     * @var array<string, string>
     */
    private array $roleLabels = [
        'receptionist' => '注文受付係',
        'accountant' => '会計係',
        'shipper' => '商品発送係',
    ];

    /**
     * @var array<string, string>
     */
    private array $roleDestinations = [
        'receptionist' => '/staff/receptionist',
        'accountant' => '/staff/accountant',
        'shipper' => '/staff/shipper',
    ];

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function attemptLogin(string $loginId, string $password): bool
    {
        $user = $this->users->findByLoginId($loginId);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'user_id' => (int) $user['id'],
            'login_id' => (string) $user['login_id'],
            'user_name' => (string) $user['name'],
            'role' => (string) $user['role'],
            'authenticated' => true,
        ];
        clear_old_input();

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return (bool) (session_get('auth.authenticated', false) ?: ($_SESSION['auth']['authenticated'] ?? false));
    }

    public function user(): ?array
    {
        $user = $_SESSION['auth'] ?? null;

        return is_array($user) ? $user : null;
    }

    public function requireGuest(): void
    {
        if ($this->isAuthenticated()) {
            $role = (string) ($this->user()['role'] ?? '');
            redirect($this->destinationForRole($role));
        }
    }

    public function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {
            flash('error', 'ログインしてください。');
            redirect('/login');
        }
    }

    public function authorizeRole(string $role): void
    {
        $this->requireAuthentication();
        $currentRole = (string) ($this->user()['role'] ?? '');

        if ($currentRole !== $role) {
            http_response_code(403);
            View::render('errors/403', [
                'pageTitle' => '403 Forbidden',
                'requiredRoleLabel' => $this->roleLabel($role),
                'currentRoleLabel' => $this->roleLabel($currentRole),
            ]);
            exit;
        }
    }

    public function destinationForRole(string $role): string
    {
        return $this->roleDestinations[$role] ?? '/login';
    }

    public function roleLabel(string $role): string
    {
        return $this->roleLabels[$role] ?? '不明';
    }

    /**
     * @return array<string, string>
     */
    public function roleDestinations(): array
    {
        return $this->roleDestinations;
    }
}
