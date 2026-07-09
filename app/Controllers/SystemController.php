<?php

declare(strict_types=1);

class SystemController extends Controller
{
    public function dbCheck(): void
    {
        $auth = new AuthService();
        $auth->requireAuthentication();
        $service = new SystemService();

        $this->render('system/db_check', [
            'pageTitle' => 'DB接続確認',
            'result' => $service->getDatabaseHealth(),
            'user' => $auth->user(),
            'roleLabel' => $auth->roleLabel((string) ($auth->user()['role'] ?? '')),
        ]);
    }
}
