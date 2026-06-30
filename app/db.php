<?php

declare(strict_types=1);

function db_connection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $driver = (string) config('database.driver', 'sqlite');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($driver === 'sqlite') {
        $sqlitePath = resolve_path((string) config('database.sqlite.path', 'database/local.sqlite'));
        $sqliteDirectory = dirname($sqlitePath);

        if (!is_dir($sqliteDirectory)) {
            mkdir($sqliteDirectory, 0775, true);
        }

        $connection = new PDO('sqlite:' . $sqlitePath, null, null, $options);
        $connection->exec('PRAGMA foreign_keys = ON');
        $connection->exec('PRAGMA busy_timeout = 5000');

        return $connection;
    }

    if ($driver === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            (string) config('database.mysql.host', 'localhost'),
            (string) config('database.mysql.port', '3306'),
            (string) config('database.mysql.database', 'tsuhan_system'),
            (string) config('database.mysql.charset', 'utf8mb4')
        );

        $connection = new PDO(
            $dsn,
            (string) config('database.mysql.username', 'root'),
            (string) config('database.mysql.password', ''),
            $options
        );

        return $connection;
    }

    throw new RuntimeException('Unsupported DB driver: ' . $driver);
}
