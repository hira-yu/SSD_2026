<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

if ($argc < 4) {
    fwrite(STDERR, "使い方: php scripts/quickwbs_update_task.php <task_id> <claim|start|block|complete|report> \"メッセージ\" [--progress=60] [--summary=\"...\"] [--work-notes=\"...\"] [--artifacts=a,b] [--next-actions=a,b] [--result-url=https://...] [--blockers=\"...\"] [--execute]" . PHP_EOL);
    exit(1);
}

$taskId = (string) $argv[1];
$status = (string) $argv[2];
$message = (string) $argv[3];
$execute = in_array('--execute', $argv, true);
$payload = ['message' => $message];

foreach (array_slice($argv, 4) as $argument) {
    if ($argument === '--execute') {
        continue;
    }

    if (preg_match('/^--progress=(.+)$/', $argument, $matches) === 1) {
        $payload['progress'] = $matches[1];
        continue;
    }

    if (preg_match('/^--summary=(.+)$/', $argument, $matches) === 1) {
        $payload['summary'] = $matches[1];
        continue;
    }

    if (preg_match('/^--work-notes=(.+)$/', $argument, $matches) === 1) {
        $payload['work_notes'] = $matches[1];
        continue;
    }

    if (preg_match('/^--artifacts=(.+)$/', $argument, $matches) === 1) {
        $payload['artifacts'] = $matches[1];
        continue;
    }

    if (preg_match('/^--next-actions=(.+)$/', $argument, $matches) === 1) {
        $payload['next_actions'] = $matches[1];
        continue;
    }

    if (preg_match('/^--result-url=(.+)$/', $argument, $matches) === 1) {
        $payload['result_url'] = $matches[1];
        continue;
    }

    if (preg_match('/^--blockers=(.+)$/', $argument, $matches) === 1) {
        $payload['blockers'] = $matches[1];
    }
}

$service = new QuickWbsService();

try {
    $result = $service->updateTask($taskId, $status, $payload, $execute);

    if ($result['dry_run']) {
        echo "[DRY-RUN] QuickWBS に送る予定の内容" . PHP_EOL;
        echo 'Method: ' . $result['method'] . PHP_EOL;
        echo 'Endpoint: ' . $result['endpoint'] . PHP_EOL;
        echo 'Action: ' . $result['action'] . PHP_EOL;
        echo 'Payload: ' . json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        foreach ($result['notes'] as $note) {
            echo '- ' . $note . PHP_EOL;
        }

        exit(0);
    }

    echo 'タスク更新に成功しました。' . PHP_EOL;
    echo 'HTTPステータス: ' . $result['status_code'] . PHP_EOL;
    echo 'Action: ' . $result['action'] . PHP_EOL;
    exit(0);
} catch (QuickWbsApiException $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
