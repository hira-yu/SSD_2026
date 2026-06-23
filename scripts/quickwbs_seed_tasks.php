<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app();

const PLACEHOLDER_PARENT_TASK_ID = '<ここに親タスクIDを入れる>';

/**
 * @return array<int, array<string, mixed>>
 */
function seedTaskDefinitions(): array
{
    return [
        [
            'title' => '開発環境・プロジェクト土台作成',
            'description' => 'publicを公開ディレクトリとしたPHP軽量MVC構成を作成する。SQLite / MariaDB を DB_DRIVER で切替可能にし、トップページとDB接続確認画面を作成する。',
            'initial_state' => '完了',
            'completion_message' => '開発環境・プロジェクト土台作成を完了しました。',
            'completion_summary' => 'PHP軽量MVC構成、DB接続切替、schema / seed、README、DB接続確認画面まで作成済み。',
            'completion_work_notes' => 'SQLite接続確認とPHP構文チェック済み。MariaDB実接続テストは未実施。',
            'completion_artifacts' => [
                'public/index.php',
                'app/db.php',
                'database/schema.sqlite.sql',
                'database/schema.mysql.sql',
                'README.md',
            ],
        ],
        [
            'title' => 'QuickWBS API連携土台作成',
            'description' => 'QuickWBS APIのAgent Docs取得、接続確認、タスク作成・更新CLIの土台を作成する。',
            'initial_state' => '完了',
            'completion_message' => 'QuickWBS API連携土台作成を完了しました。',
            'completion_summary' => 'Agent Docs取得成功、QuickWbsClient / QuickWbsService、docs保存、check / create / update CLI、dry-run確認まで完了。',
            'completion_work_notes' => 'トークン非表示も確認済み。',
            'completion_artifacts' => [
                'app/Repositories/QuickWbsClient.php',
                'app/Services/QuickWbsService.php',
                'scripts/quickwbs_docs.php',
                'scripts/quickwbs_check.php',
                'scripts/quickwbs_create_task.php',
                'scripts/quickwbs_update_task.php',
            ],
        ],
        [
            'title' => '担当者認証機能',
            'description' => '注文受付係、会計係、商品発送係のログイン認証とロール別アクセス制御を実装する。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '商品一覧・商品検索機能',
            'description' => '購入者および注文受付係が、商品番号または商品名で商品を検索し、単価・カテゴリ・メーカー・在庫数量2を確認できるようにする。',
            'initial_state' => '未着手',
        ],
        [
            'title' => 'ネット注文機能',
            'description' => '購入者が商品をカートに追加し、購入者情報と疑似クレジットカード情報を入力して注文できるようにする。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '電話/FAX注文登録機能',
            'description' => '注文受付係が電話またはFAXで受け付けた注文を代理登録できるようにする。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '会計処理機能',
            'description' => '会計係が注文番号、注文日、購入者氏名で注文を検索し、支払い状態を支払済へ更新できるようにする。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '発送処理機能',
            'description' => '商品発送係が未発送注文を確認し、納品書・請求書情報を表示し、発送済へ更新できるようにする。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '在庫整合性・排他制御',
            'description' => '注文登録時の在庫数量2減算、発送済更新時の在庫数量1減算、SQLite / MariaDBでのトランザクション処理を実装・検証する。',
            'initial_state' => '未着手',
        ],
        [
            'title' => '発表デモ調整',
            'description' => 'QRコードからアクセスできる画面、デモ用注意書き、スマートフォン表示、疑似決済案内、発表用データを整える。',
            'initial_state' => '未着手',
        ],
    ];
}

function isPlaceholderParentTaskId(string $parentTaskId): bool
{
    return $parentTaskId === '' || $parentTaskId === PLACEHOLDER_PARENT_TASK_ID || str_contains($parentTaskId, 'ここに親タスクIDを入れる');
}

if ($argc < 2) {
    fwrite(STDERR, "使い方: php scripts/quickwbs_seed_tasks.php <parent_task_id> [--execute]" . PHP_EOL);
    fwrite(STDERR, "親タスクID未指定時の dry-run 例: php scripts/quickwbs_seed_tasks.php \"" . PLACEHOLDER_PARENT_TASK_ID . "\"" . PHP_EOL);
    exit(1);
}

$parentTaskId = (string) $argv[1];
$execute = in_array('--execute', $argv, true);
$hasRealParentTaskId = !isPlaceholderParentTaskId($parentTaskId);

if ($execute && !$hasRealParentTaskId) {
    fwrite(STDERR, "親タスクIDが未指定のため、--execute は実行できません。dry-run のみ可能です。" . PHP_EOL);
    exit(1);
}

$service = new QuickWbsService();
$seedTasks = seedTaskDefinitions();
$createdTasks = [];

echo ($execute ? '[EXECUTE]' : '[DRY-RUN]') . ' 初期タスク登録準備' . PHP_EOL;
echo '親タスクID: ' . ($hasRealParentTaskId ? $parentTaskId : PLACEHOLDER_PARENT_TASK_ID . ' (未指定扱い)') . PHP_EOL;
echo '登録対象件数: ' . count($seedTasks) . PHP_EOL;
echo PHP_EOL;

foreach ($seedTasks as $index => $task) {
    $taskNumber = $index + 1;
    $isCompletedAtCreationFlow = ($task['initial_state'] ?? '') === '完了';

    echo $taskNumber . '. ' . $task['title'] . PHP_EOL;
    echo '  状態: ' . $task['initial_state'] . PHP_EOL;

    try {
        $createResult = $service->createTask(
            $hasRealParentTaskId ? $parentTaskId : PLACEHOLDER_PARENT_TASK_ID,
            (string) $task['title'],
            (string) $task['description'],
            [],
            $execute
        );

        if (($createResult['dry_run'] ?? false) === true) {
            echo '  create: ' . $createResult['method'] . ' ' . $createResult['endpoint'] . PHP_EOL;
            echo '  payload: ' . json_encode($createResult['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            $createdTaskId = $createResult['task_id'] ?? null;
            $createdTasks[] = [
                'title' => $task['title'],
                'task_id' => $createdTaskId,
                'completed' => false,
            ];

            echo '  created_task_id: ' . ($createdTaskId === null ? '判別不可' : (string) $createdTaskId) . PHP_EOL;
        }

        if ($isCompletedAtCreationFlow) {
            $completionPayload = [
                'message' => $task['completion_message'] ?? ($task['title'] . ' を完了しました。'),
                'summary' => $task['completion_summary'] ?? null,
                'work_notes' => $task['completion_work_notes'] ?? null,
                'artifacts' => $task['completion_artifacts'] ?? [],
            ];

            $updateTargetTaskId = $execute
                ? (string) ($createResult['task_id'] ?? '')
                : '<created-child-task-id-from-previous-step>';

            $updateResult = $service->updateTask(
                $updateTargetTaskId,
                'complete',
                $completionPayload,
                $execute
            );

            if (($updateResult['dry_run'] ?? false) === true) {
                echo '  complete: ' . $updateResult['method'] . ' ' . $updateResult['endpoint'] . PHP_EOL;
                echo '  complete_payload: ' . json_encode($updateResult['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            } else {
                $lastIndex = array_key_last($createdTasks);
                if ($lastIndex !== null) {
                    $createdTasks[$lastIndex]['completed'] = true;
                }

                echo '  completion: success (' . $updateResult['action'] . ')' . PHP_EOL;
            }
        }
    } catch (QuickWbsApiException $exception) {
        echo '  error: ' . $exception->getMessage() . PHP_EOL;
        if ($execute) {
            exit(1);
        }
    }

    echo PHP_EOL;
}

if (!$execute) {
    echo 'dry-run 完了: QuickWBS への実登録は行っていません。' . PHP_EOL;
    if (!$hasRealParentTaskId) {
        echo '親タスクIDが未指定のため、表示上はプレースホルダを使っています。' . PHP_EOL;
    }
    exit(0);
}

echo '作成された子タスク一覧:' . PHP_EOL;

foreach ($createdTasks as $createdTask) {
    echo '- ' . $createdTask['title'] . ': '
        . (($createdTask['task_id'] ?? null) === null ? 'ID判別不可' : (string) $createdTask['task_id'])
        . ' / ' . ($createdTask['completed'] ? '完了扱い' : '未完了')
        . PHP_EOL;
}
