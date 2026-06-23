<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

$service = new QuickWbsService();
$result = $service->checkConnection();

echo ($result['success'] ? '接続成功' : '接続失敗') . PHP_EOL;
echo 'API Base URL: ' . $result['base_url'] . PHP_EOL;
echo 'HTTPステータス: ' . ($result['status_code'] === 0 ? '取得不可' : $result['status_code']) . PHP_EOL;
echo 'API名: ' . $result['api_name'] . PHP_EOL;
echo 'バージョン: ' . $result['version'] . PHP_EOL;

if (is_array($result['agent'])) {
    echo 'Agent: ' . (($result['agent']['actor_label'] ?? null) ?: '不明') . PHP_EOL;
}

echo '利用可能タスク数: ' . $result['available_task_count'] . PHP_EOL;

if ($result['endpoints'] !== []) {
    echo '利用する主要エンドポイント:' . PHP_EOL;

    foreach ($result['endpoints'] as $label => $endpoint) {
        if (isset($endpoint['method'], $endpoint['path'])) {
            echo '- ' . $label . ': ' . $endpoint['method'] . ' ' . $endpoint['path'] . PHP_EOL;
            continue;
        }

        if (is_array($endpoint)) {
            foreach ($endpoint as $action => $item) {
                if (isset($item['method'], $item['path'])) {
                    echo '- ' . $label . '.' . $action . ': ' . $item['method'] . ' ' . $item['path'] . PHP_EOL;
                }
            }
        }
    }
}

echo 'メッセージ: ' . $result['message'] . PHP_EOL;

exit($result['success'] ? 0 : 1);
