<?php

declare(strict_types=1);

class DatabaseRepository
{
    public function probeConnection(): array
    {
        $pdo = db_connection();
        $statement = $pdo->query('SELECT 1 AS result');
        $row = $statement->fetch();

        return [
            'success' => true,
            'driver' => (string) config('database.driver', 'sqlite'),
            'db_type' => strtoupper((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)),
            'checked_at' => date('Y-m-d H:i:s'),
            'query_result' => (string) ($row['result'] ?? '1'),
            'message' => 'DB接続に成功しました。',
        ];
    }
}
