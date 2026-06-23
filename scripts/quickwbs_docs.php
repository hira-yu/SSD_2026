<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

$showRaw = in_array('--raw', $argv, true);
$service = new QuickWbsService();

try {
    $result = $service->fetchAgentDocs();
    $notesPath = $service->writeApiNotes([
        'success' => true,
        'analysis' => $result['analysis'],
    ]);

    echo "QuickWBS Agent Docs の取得に成功しました。" . PHP_EOL;
    echo 'HTTPステータス: ' . $result['response']['status_code'] . PHP_EOL;
    echo '形式: ' . ($result['analysis']['format'] ?? 'unknown') . PHP_EOL;
    echo 'API名: ' . ($result['analysis']['api_name'] ?? '不明') . PHP_EOL;
    echo 'Agent: ' . (($result['analysis']['agent']['actor_label'] ?? null) ?: '不明') . PHP_EOL;
    echo 'ノート保存先: ' . $notesPath . PHP_EOL;
    echo PHP_EOL;
    echo "主要エンドポイント:" . PHP_EOL;

    foreach (($result['analysis']['endpoints'] ?? []) as $label => $endpoint) {
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

    if ($showRaw) {
        echo PHP_EOL;
        echo json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    exit(0);
} catch (QuickWbsApiException $exception) {
    $notesPath = $service->writeApiNotes([
        'success' => false,
        'analysis' => [
            'format' => 'unknown',
            'api_name' => '不明',
            'version' => '不明',
            'agent' => [],
            'documents' => [],
            'endpoints' => [],
            'required_parameters' => [],
            'status_actions' => [],
            'scope_rules' => [],
            'response_examples' => [],
            'error_format' => null,
            'notes' => [$exception->getMessage()],
        ],
    ]);

    echo $exception->getMessage() . PHP_EOL;
    echo 'ノート保存先: ' . $notesPath . PHP_EOL;
    exit(1);
}
