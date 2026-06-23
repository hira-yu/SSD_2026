<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

if ($argc < 4) {
    fwrite(STDERR, "使い方: php scripts/quickwbs_create_task.php <parent_task_id> \"タスク名\" \"説明\" [--priority=medium] [--estimate-hours=4] [--acceptance-criteria=\"...\"] [--execute]" . PHP_EOL);
    exit(1);
}

$parentTaskId = (string) $argv[1];
$title = (string) $argv[2];
$description = (string) $argv[3];
$execute = in_array('--execute', $argv, true);
$options = [];

foreach (array_slice($argv, 4) as $argument) {
    if ($argument === '--execute') {
        continue;
    }

    if (preg_match('/^--priority=(.+)$/', $argument, $matches) === 1) {
        $options['priority'] = $matches[1];
        continue;
    }

    if (preg_match('/^--estimate-hours=(.+)$/', $argument, $matches) === 1) {
        $options['estimate_hours'] = $matches[1];
        continue;
    }

    if (preg_match('/^--acceptance-criteria=(.+)$/', $argument, $matches) === 1) {
        $options['acceptance_criteria'] = $matches[1];
    }
}

$service = new QuickWbsService();

try {
    $result = $service->createTask($parentTaskId, $title, $description, $options, $execute);

    if ($result['dry_run']) {
        echo "[DRY-RUN] QuickWBS に送る予定の内容" . PHP_EOL;
        echo 'Method: ' . $result['method'] . PHP_EOL;
        echo 'Endpoint: ' . $result['endpoint'] . PHP_EOL;
        echo 'Payload: ' . json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        foreach ($result['notes'] as $note) {
            echo '- ' . $note . PHP_EOL;
        }

        exit(0);
    }

    echo 'タスク作成に成功しました。' . PHP_EOL;
    echo 'HTTPステータス: ' . $result['status_code'] . PHP_EOL;
    echo 'タスクID: ' . ($result['task_id'] ?? '判別不可') . PHP_EOL;

    if (($result['task_url'] ?? null) !== null) {
        echo 'タスクURL: ' . $result['task_url'] . PHP_EOL;
    }

    exit(0);
} catch (QuickWbsApiException $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
