<?php

declare(strict_types=1);

class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function showLogin(): void
    {
        $this->auth->requireGuest();

        $this->render('auth/login', [
            'pageTitle' => '担当者ログイン',
            'errorMessage' => get_flash('error'),
            'loginId' => (string) old_input('login_id', ''),
            'csrfToken' => csrf_token(),
        ]);
    }

    public function login(): void
    {
        $this->auth->requireGuest();

        $loginId = trim((string) ($_POST['login_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $token = (string) ($_POST['_csrf'] ?? '');

        store_old_input(['login_id' => $loginId]);

        if (!verify_csrf_token($token)) {
            flash('error', '不正なリクエストです。再度ログインしてください。');
            $this->redirect('/login');
        }

        if ($loginId === '' || $password === '') {
            flash('error', 'ログインIDとパスワードを入力してください。');
            $this->redirect('/login');
        }

        if (!$this->auth->attemptLogin($loginId, $password)) {
            flash('error', 'ログインIDまたはパスワードが正しくありません');
            $this->redirect('/login');
        }

        $role = (string) ($this->auth->user()['role'] ?? '');
        $this->redirect($this->auth->destinationForRole($role));
    }

    public function logout(): void
    {
        $token = (string) ($_POST['_csrf'] ?? $_GET['_csrf'] ?? '');

        if ($token !== '' && verify_csrf_token($token)) {
            $this->auth->logout();
            session_start();
            csrf_token();
            flash('success', 'ログアウトしました。');
            $this->redirect('/login');
        }

        flash('error', '不正なリクエストです。');
        $this->redirect('/login');
    }
}
