<?php

declare(strict_types=1);

class QuickWbsService
{
    private QuickWbsClient $client;

    /**
     * @var array<string, string>
     */
    private array $statusActionMap = [
        'claim' => 'claim',
        'ready' => 'claim',
        'start' => 'start',
        'in_progress' => 'start',
        'working' => 'start',
        'block' => 'block',
        'blocked' => 'block',
        'complete' => 'complete',
        'done' => 'complete',
        'report' => 'report',
        'progress' => 'report',
        '完了' => 'complete',
        '作業中' => 'start',
        '進行中' => 'start',
        '着手中' => 'start',
        'ブロック' => 'block',
        '報告' => 'report',
        '準備完了' => 'claim',
    ];

    public function __construct()
    {
        $this->client = new QuickWbsClient();
    }

    public function fetchAgentDocs(): array
    {
        $response = $this->client->getAbsolute($this->client->getAgentDocsUrl());
        $payload = $response['json'] ?? null;

        if (!is_array($payload)) {
            throw new QuickWbsApiException('QuickWBS Agent Docs のレスポンス形式を解釈できませんでした。', (int) $response['status_code'], $this->client->getAgentDocsUrl());
        }

        return [
            'response' => $response,
            'payload' => $payload,
            'analysis' => $this->analyzeDocsPayload($payload),
        ];
    }

    public function checkConnection(): array
    {
        try {
            $docs = $this->fetchAgentDocs();
            $me = $this->getCurrentAgent();
            $tasks = $this->listTasks();

            return [
                'success' => true,
                'message' => 'QuickWBS Agent API への接続に成功しました。',
                'status_code' => (int) $docs['response']['status_code'],
                'base_url' => $this->client->getBaseUrl(),
                'api_name' => $docs['analysis']['api_name'] ?? 'QuickWBS Agent API',
                'version' => $docs['analysis']['version'] ?? '不明',
                'agent' => $me['agent'],
                'available_task_count' => $tasks['count'],
                'documents' => $docs['analysis']['documents'] ?? [],
                'endpoints' => $docs['analysis']['endpoints'] ?? [],
            ];
        } catch (QuickWbsApiException $exception) {
            app_log('QuickWBS connection check failed', [
                'status_code' => $exception->getStatusCode(),
                'url' => $exception->getUrl(),
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
                'base_url' => $this->client->getBaseUrl(),
                'api_name' => '不明',
                'version' => '不明',
                'agent' => null,
                'available_task_count' => 0,
                'documents' => [],
                'endpoints' => [],
            ];
        }
    }

    public function getCurrentAgent(): array
    {
        $response = $this->client->get('agent/me');
        $agent = $response['json']['agent'] ?? null;

        if (!is_array($agent)) {
            throw new QuickWbsApiException('QuickWBS Agent 情報を取得できませんでした。', (int) $response['status_code'], $this->client->getBaseUrl() . '/agent/me');
        }

        return [
            'status_code' => (int) $response['status_code'],
            'agent' => $agent,
        ];
    }

    public function listTasks(): array
    {
        $response = $this->client->get('agent/tasks/available');
        $tasks = $response['json']['tasks'] ?? [];

        if (!is_array($tasks)) {
            throw new QuickWbsApiException('QuickWBS のタスク一覧レスポンスを解釈できませんでした。', (int) $response['status_code'], $this->client->getBaseUrl() . '/agent/tasks/available');
        }

        return [
            'status_code' => (int) $response['status_code'],
            'tasks' => $tasks,
            'count' => count($tasks),
        ];
    }

    public function createTask(
        string $parentTaskId,
        string $title,
        string $description = '',
        array $options = [],
        bool $execute = false
    ): array {
        if ($parentTaskId === '') {
            throw new QuickWbsApiException('親タスクIDが必要です。QuickWBS Agent API では子タスク作成のみサポートされています。');
        }

        $endpoint = 'agent/tasks/' . rawurlencode($parentTaskId) . '/children';
        $payload = array_filter([
            'title' => $title,
            'description' => $description,
            'priority' => $options['priority'] ?? null,
            'estimate_hours' => $this->normalizeNullableNumeric($options['estimate_hours'] ?? null),
            'acceptance_criteria' => $options['acceptance_criteria'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if (!isset($payload['title'])) {
            throw new QuickWbsApiException('タスク作成には title が必要です。');
        }

        if (!$execute) {
            return [
                'dry_run' => true,
                'method' => 'POST',
                'endpoint' => '/api/' . $endpoint,
                'payload' => $payload,
                'notes' => [
                    'QuickWBS Agent API は親タスク配下の子タスク作成のみをサポートしています。',
                    '実行するには --execute を付けてください。',
                ],
            ];
        }

        $response = $this->client->post($endpoint, $payload);
        $json = $response['json'] ?? [];

        return [
            'dry_run' => false,
            'status_code' => (int) $response['status_code'],
            'task_id' => $this->extractTaskId($json),
            'task_url' => $this->extractTaskUrl($json),
            'raw' => $json,
        ];
    }

    public function updateTask(
        string $taskId,
        string $statusOrAction,
        array $payload = [],
        bool $execute = false
    ): array {
        if ($taskId === '') {
            throw new QuickWbsApiException('更新対象のタスクIDが必要です。');
        }

        $action = $this->normalizeTaskAction($statusOrAction);
        $endpoint = 'agent/tasks/' . rawurlencode($taskId) . '/' . $action;
        $normalizedPayload = $this->normalizeUpdatePayload($payload);

        if (!$execute) {
            return [
                'dry_run' => true,
                'method' => 'POST',
                'endpoint' => '/api/' . $endpoint,
                'payload' => $normalizedPayload,
                'action' => $action,
                'notes' => [
                    '実行するには --execute を付けてください。',
                    'allowed actions: claim, start, block, complete, report',
                ],
            ];
        }

        $response = $this->client->post($endpoint, $normalizedPayload);

        return [
            'dry_run' => false,
            'status_code' => (int) $response['status_code'],
            'action' => $action,
            'raw' => $response['json'] ?? [],
        ];
    }

    public function writeApiNotes(array $summary): string
    {
        $analysis = $summary['analysis'];
        $lines = [
            '# QuickWBS API Notes',
            '',
            '- 更新日時: ' . date('Y-m-d H:i:s'),
            '- API Base URL: ' . $this->client->getBaseUrl(),
            '- Agent Docs URL: ' . $this->client->getAgentDocsUrl(),
            '- 取得状態: ' . ($summary['success'] ? '成功' : '失敗'),
            '- 形式: ' . ($analysis['format'] ?? 'unknown'),
            '- API 名: ' . ($analysis['api_name'] ?? '不明'),
            '- バージョン: ' . ($analysis['version'] ?? '不明'),
            '',
            '## Agent Identity',
            '',
            '- Agent名: ' . (($analysis['agent']['name'] ?? null) ?: '不明'),
            '- Owner: ' . (($analysis['agent']['owner_name'] ?? null) ?: '不明'),
            '- Actor Label: ' . (($analysis['agent']['actor_label'] ?? null) ?: '不明'),
            '',
            '## 取得できたドキュメント',
            '',
        ];

        foreach (($analysis['documents'] ?? []) as $document) {
            $lines[] = '- ' . $document['id'] . ': ' . $document['title'];
        }

        $lines[] = '';
        $lines[] = '## 判明したAPIエンドポイント';
        $lines[] = '';

        foreach (($analysis['endpoints'] ?? []) as $label => $endpoint) {
            if (is_array($endpoint) && isset($endpoint['method'], $endpoint['path'])) {
                $lines[] = '- ' . $label . ': `' . $endpoint['method'] . ' ' . $endpoint['path'] . '`';
                continue;
            }

            if (is_array($endpoint)) {
                $lines[] = '- ' . $label . ':';

                foreach ($endpoint as $action => $item) {
                    $lines[] = '  - ' . $action . ': `' . $item['method'] . ' ' . $item['path'] . '`';
                }
            }
        }

        $lines[] = '';
        $lines[] = '## 必須パラメータ';
        $lines[] = '';

        foreach (($analysis['required_parameters'] ?? []) as $operation => $params) {
            $lines[] = '- ' . $operation . ': ' . ($params === [] ? 'なし' : implode(', ', $params));
        }

        $lines[] = '';
        $lines[] = '## ステータス指定方法';
        $lines[] = '';

        foreach (($analysis['status_actions'] ?? []) as $status => $action) {
            $lines[] = '- `' . $status . '` => `' . $action . '`';
        }

        $lines[] = '';
        $lines[] = '## プロジェクト / ボード / リスト指定';
        $lines[] = '';

        foreach (($analysis['scope_rules'] ?? []) as $rule) {
            $lines[] = '- ' . $rule;
        }

        $lines[] = '';
        $lines[] = '## レスポンス形式';
        $lines[] = '';

        foreach (($analysis['response_examples'] ?? []) as $label => $format) {
            $lines[] = '- ' . $label . ': ' . $format;
        }

        $lines[] = '';
        $lines[] = '## エラー形式';
        $lines[] = '';
        $lines[] = '- ' . (($analysis['error_format'] ?? null) ?: '未確認');

        $lines[] = '';
        $lines[] = '## 補足';
        $lines[] = '';

        foreach (($analysis['notes'] ?? []) as $note) {
            $lines[] = '- ' . $note;
        }

        if (!$summary['success']) {
            $lines[] = '- Agent Docs の取得に失敗したため、内容は直近成功時点のものではありません。';
        }

        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        file_put_contents(base_path('docs/quickwbs-api-notes.md'), $content);

        return base_path('docs/quickwbs-api-notes.md');
    }

    private function analyzeDocsPayload(array $payload): array
    {
        $documents = $payload['documents'] ?? [];
        $agent = $payload['agent'] ?? [];
        $apiDoc = $this->findDocumentContent($documents, 'api');
        $guideDoc = $this->findDocumentContent($documents, 'agent-guide');

        $analysis = [
            'format' => 'agent-docs-json',
            'api_name' => 'QuickWBS Agent API',
            'version' => $this->matchFirst('/version[:\s]+([0-9A-Za-z.\-_]+)/i', $apiDoc) ?? '不明',
            'agent' => is_array($agent) ? $agent : [],
            'documents' => array_map(
                static fn (array $document): array => [
                    'id' => (string) ($document['id'] ?? ''),
                    'title' => (string) ($document['title'] ?? ''),
                ],
                array_filter($documents, 'is_array')
            ),
            'endpoints' => [
                'agent_docs' => $this->confirmEndpoint($apiDoc, 'GET', '/api/agent/docs'),
                'agent_me' => $this->confirmEndpoint($apiDoc, 'GET', '/api/agent/me'),
                'task_list' => $this->confirmEndpoint($apiDoc, 'GET', '/api/agent/tasks/available'),
                'task_context' => $this->confirmEndpoint($apiDoc, 'GET', '/api/agent/tasks/{task_id}/context'),
                'task_create' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/children'),
                'task_update' => [
                    'claim' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/claim'),
                    'start' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/start'),
                    'block' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/block'),
                    'complete' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/complete'),
                    'report' => $this->confirmEndpoint($apiDoc, 'POST', '/api/agent/tasks/{task_id}/report'),
                ],
            ],
            'required_parameters' => [
                'task_list' => [],
                'task_create.path' => ['task_id'],
                'task_create.body.required' => ['title'],
                'task_create.body.optional' => ['description', 'priority', 'estimate_hours', 'acceptance_criteria'],
                'task_update.path' => ['task_id', 'action'],
                'task_update.body.optional' => ['message', 'summary', 'work_notes', 'artifacts', 'next_actions', 'result_url', 'blockers', 'progress'],
            ],
            'status_actions' => [
                'ready' => 'claim',
                'in_progress' => 'start',
                'blocked' => 'block',
                'done' => 'complete',
                'report-only' => 'report',
            ],
            'scope_rules' => [
                'Agent API では project_id / board_id / list_id の直接指定は不要です。',
                'タスク作成はトップレベル作成ではなく、親タスクID配下の child task 作成です。',
                'プロジェクト情報は GET /api/agent/tasks/{task_id}/context で取得します。',
            ],
            'response_examples' => [
                'GET /api/agent/docs' => '{agent, documents}',
                'GET /api/agent/me' => '{agent}',
                'GET /api/agent/tasks/available' => '{tasks: []}',
            ],
            'error_format' => '404 時の実測: {"error":{"message":"Task not found.","details":[]}}',
            'notes' => [
                '通常の /api/projects や /api/tasks 系は X-User-Token を要求し、Agent Bearer Token では操作対象が異なります。',
                'Agent ガイドでは docs -> me -> available -> context -> claim/start -> report/complete の順での操作を推奨しています。',
                'QuickWBS Agent API だけではトップレベルタスクの新規作成は確認できませんでした。',
            ],
        ];

        if ($guideDoc !== '' && str_contains($guideDoc, 'Read the task context before changing a task.')) {
            $analysis['notes'][] = '更新前に task context を読む運用が推奨されています。';
        }

        return $analysis;
    }

    /**
     * @param array<int, mixed> $documents
     */
    private function findDocumentContent(array $documents, string $id): string
    {
        foreach ($documents as $document) {
            if (!is_array($document)) {
                continue;
            }

            if (($document['id'] ?? null) === $id) {
                return (string) ($document['content'] ?? '');
            }
        }

        return '';
    }

    private function confirmEndpoint(string $document, string $method, string $path): ?array
    {
        $needle = $method . ' ' . $path;

        if (!str_contains($document, $needle)) {
            return null;
        }

        return [
            'method' => $method,
            'path' => $path,
        ];
    }

    private function normalizeTaskAction(string $statusOrAction): string
    {
        $normalized = strtolower(trim($statusOrAction));
        $action = $this->statusActionMap[$normalized] ?? $this->statusActionMap[trim($statusOrAction)] ?? null;

        if ($action === null) {
            throw new QuickWbsApiException('未対応のステータスです。claim/start/block/complete/report を指定してください。');
        }

        return $action;
    }

    private function normalizeUpdatePayload(array $payload): array
    {
        $normalized = [];

        foreach (['message', 'summary', 'work_notes', 'result_url', 'blockers'] as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                $normalized[$key] = (string) $payload[$key];
            }
        }

        if (isset($payload['progress']) && $payload['progress'] !== '') {
            $normalized['progress'] = (int) $payload['progress'];
        }

        foreach (['artifacts', 'next_actions'] as $key) {
            if (!isset($payload[$key])) {
                continue;
            }

            $value = $payload[$key];

            if (is_string($value)) {
                $items = array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
                if ($items !== []) {
                    $normalized[$key] = $items;
                }
                continue;
            }

            if (is_array($value) && $value !== []) {
                $normalized[$key] = array_values(array_map(static fn (mixed $item): string => (string) $item, $value));
            }
        }

        return $normalized;
    }

    private function normalizeNullableNumeric(mixed $value): float|int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_contains((string) $value, '.') ? (float) $value : (int) $value;
    }

    private function extractTaskId(array $response): string|int|null
    {
        return $response['task']['id']
            ?? $response['id']
            ?? $response['task_id']
            ?? null;
    }

    private function extractTaskUrl(array $response): ?string
    {
        $value = $response['task']['url']
            ?? $response['url']
            ?? $response['task_url']
            ?? null;

        return is_string($value) ? $value : null;
    }

    private function matchFirst(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $matches) === 1
            ? (string) $matches[1]
            : null;
    }
}
