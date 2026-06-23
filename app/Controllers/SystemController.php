<?php

declare(strict_types=1);

class SystemController extends Controller
{
    public function dbCheck(): void
    {
        $service = new SystemService();

        $this->render('system/db_check', [
            'pageTitle' => 'DB接続確認',
            'result' => $service->getDatabaseHealth(),
        ]);
    }
}
