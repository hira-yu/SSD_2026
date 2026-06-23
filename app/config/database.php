<?php

declare(strict_types=1);

return [
    'driver' => (string) env('DB_DRIVER', 'sqlite'),
    'sqlite' => [
        'path' => (string) env('DB_SQLITE_PATH', 'database/local.sqlite'),
    ],
    'mysql' => [
        'host' => (string) env('DB_HOST', 'localhost'),
        'port' => (string) env('DB_PORT', '3306'),
        'database' => (string) env('DB_NAME', 'tsuhan_system'),
        'username' => (string) env('DB_USER', 'root'),
        'password' => (string) env('DB_PASSWORD', ''),
        'charset' => (string) env('DB_CHARSET', 'utf8mb4'),
    ],
];
