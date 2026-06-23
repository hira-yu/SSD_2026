<?php

declare(strict_types=1);

class QuickWbsClient
{
    private string $baseUrl;
    private string $agentDocsUrl;
    private string $apiToken;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('quickwbs.base_url', ''), '/');
        $this->agentDocsUrl = (string) config('quickwbs.agent_docs_url', '');
        $this->apiToken = trim((string) config('quickwbs.api_token', ''));
        $this->timeout = (int) config('quickwbs.timeout', 15);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, query: $query);
    }

    public function getAbsolute(string $url, array $query = []): array
    {
        return $this->request('GET', $url, absolute: true, query: $query);
    }

    public function post(string $path, ?array $payload = null): array
    {
        return $this->request('POST', $path, payload: $payload);
    }

    public function put(string $path, ?array $payload = null): array
    {
        return $this->request('PUT', $path, payload: $payload);
    }

    public function patch(string $path, ?array $payload = null): array
    {
        return $this->request('PATCH', $path, payload: $payload);
    }

    public function delete(string $path, ?array $payload = null): array
    {
        return $this->request('DELETE', $path, payload: $payload);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getAgentDocsUrl(): string
    {
        return $this->agentDocsUrl;
    }

    private function request(
        string $method,
        string $pathOrUrl,
        ?array $payload = null,
        bool $absolute = false,
        array $query = []
    ): array {
        $this->assertConfigured();

        $url = $this->buildUrl($pathOrUrl, $absolute, $query);
        $headers = [
            'Accept: application/json, text/plain, text/html;q=0.9, */*;q=0.8',
            'Authorization: Bearer ' . $this->apiToken,
        ];

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $result = function_exists('curl_init')
            ? $this->requestWithCurl($method, $url, $headers, $payload)
            : $this->requestWithStream($method, $url, $headers, $payload);

        $result['json'] = $this->decodeJson($result['body'], $result['headers']);

        if ($result['status_code'] >= 400) {
            throw new QuickWbsApiException(
                $this->safeMessageForStatus($result['status_code']),
                $result['status_code'],
                $url
            );
        }

        return $result;
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '') {
            throw new QuickWbsApiException('QuickWBS API Base URL が設定されていません。');
        }

        if ($this->agentDocsUrl === '') {
            throw new QuickWbsApiException('QuickWBS Agent Docs URL が設定されていません。');
        }

        if ($this->apiToken === '' || $this->apiToken === 'your_ai_token_here') {
            throw new QuickWbsApiException('QuickWBS APIトークンが設定されていません。');
        }
    }

    private function buildUrl(string $pathOrUrl, bool $absolute, array $query): string
    {
        $url = $absolute || preg_match('/^https?:\/\//i', $pathOrUrl)
            ? $pathOrUrl
            : $this->baseUrl . '/' . ltrim($pathOrUrl, '/');

        if ($query === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query);
    }

    private function requestWithCurl(string $method, string $url, array $headers, ?array $payload): array
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new QuickWbsApiException('QuickWBS APIクライアントの初期化に失敗しました。', 0, $url);
        }

        $responseHeaders = [];

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $header = trim($headerLine);

                if ($header === '' || !str_contains($header, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $header, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);

                return $length;
            },
        ]);

        if ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                throw new QuickWbsApiException('QuickWBS APIへ送信するJSONの生成に失敗しました。', 0, $url);
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        }

        $body = curl_exec($curl);

        if ($body === false) {
            $errorCode = curl_errno($curl);

            throw new QuickWbsApiException(
                $errorCode === CURLE_OPERATION_TIMEDOUT
                    ? 'QuickWBS APIへ接続できませんでした。タイムアウトが発生しました。'
                    : 'QuickWBS APIへ接続できませんでした。',
                0,
                $url
            );
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        return [
            'status_code' => $statusCode,
            'headers' => $responseHeaders,
            'body' => $body,
        ];
    }

    private function requestWithStream(string $method, string $url, array $headers, ?array $payload): array
    {
        $content = null;

        if ($payload !== null) {
            $content = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($content === false) {
                throw new QuickWbsApiException('QuickWBS APIへ送信するJSONの生成に失敗しました。', 0, $url);
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $content,
                'ignore_errors' => true,
                'timeout' => $this->timeout,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new QuickWbsApiException('QuickWBS APIへ接続できませんでした。', 0, $url);
        }

        $responseHeaders = $this->parseStreamHeaders($http_response_header ?? []);

        return [
            'status_code' => $responseHeaders['status_code'],
            'headers' => $responseHeaders['headers'],
            'body' => $body,
        ];
    }

    private function parseStreamHeaders(array $rawHeaders): array
    {
        $headers = [];
        $statusCode = 0;

        foreach ($rawHeaders as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headerLine, $matches) === 1) {
                $statusCode = (int) $matches[1];
                continue;
            }

            if (!str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return [
            'status_code' => $statusCode,
            'headers' => $headers,
        ];
    }

    private function decodeJson(string $body, array $headers): ?array
    {
        $contentType = strtolower((string) ($headers['content-type'] ?? ''));

        if ($body === '') {
            return null;
        }

        if (!str_contains($contentType, 'json') && !in_array($body[0], ['{', '['], true)) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function safeMessageForStatus(int $statusCode): string
    {
        return match (true) {
            $statusCode === 401, $statusCode === 403 => '認証に失敗しました。',
            $statusCode === 404 => 'QuickWBS API の対象が見つかりません。',
            $statusCode >= 500 => 'QuickWBS API側または通信経路でエラーが発生しました。',
            default => 'QuickWBS APIへのリクエストに失敗しました。',
        };
    }
}
