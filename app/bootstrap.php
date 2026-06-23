<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function bootstrap_app(bool $startSession = false): void
{
    static $bootstrapped = false;

    if (!$bootstrapped) {
        load_env(base_path('.env'));

        if (!is_file(base_path('.env'))) {
            load_env(base_path('.env.example'));
        }

        $GLOBALS['config'] = [
            'app' => require base_path('app/config/app.php'),
            'database' => require base_path('app/config/database.php'),
            'quickwbs' => require base_path('app/config/quickwbs.php'),
        ];

        date_default_timezone_set((string) config('app.timezone', 'Asia/Tokyo'));
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        spl_autoload_register(static function (string $class): void {
            $directories = ['core', 'Controllers', 'Services', 'Repositories'];

            foreach ($directories as $directory) {
                $filePath = base_path('app/' . $directory . '/' . $class . '.php');

                if (is_file($filePath)) {
                    require_once $filePath;
                    return;
                }
            }
        });

        require_once base_path('app/db.php');

        $bootstrapped = true;
    }

    if ($startSession) {
        session_name((string) config('app.session_name', 'TSUHAN_SESSION'));

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
