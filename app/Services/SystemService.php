<?php

declare(strict_types=1);

class SystemService
{
    private DatabaseRepository $databaseRepository;

    public function __construct()
    {
        $this->databaseRepository = new DatabaseRepository();
    }

    public function getDatabaseHealth(): array
    {
        try {
            return $this->databaseRepository->probeConnection();
        } catch (Throwable $exception) {
            app_log('Database check failed', [
                'driver' => (string) config('database.driver', 'sqlite'),
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'driver' => (string) config('database.driver', 'sqlite'),
                'db_type' => strtoupper((string) config('database.driver', 'sqlite')),
                'checked_at' => date('Y-m-d H:i:s'),
                'query_result' => null,
                'message' => 'DB接続に失敗しました。設定を確認してください。',
            ];
        }
    }
}
