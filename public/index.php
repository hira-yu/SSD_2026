<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app(startSession: true);

set_exception_handler(static function (Throwable $exception): void {
    app_log('Unhandled exception', [
        'type' => $exception::class,
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
    ]);

    http_response_code(500);
    echo 'システムエラーが発生しました。時間をおいて再度お試しください。';
});

$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->get('/system/db-check', [SystemController::class, 'dbCheck']);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
