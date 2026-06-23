<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $basePath = dirname(__DIR__);

    if ($path === '') {
        return $basePath;
    }

    return $basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function resolve_path(string $path): string
{
    if ($path === '') {
        return base_path();
    }

    $isAbsoluteUnix = str_starts_with($path, '/');
    $isAbsoluteWindows = (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);

    if ($isAbsoluteUnix || $isAbsoluteWindows) {
        return $path;
    }

    return base_path($path);
}

function load_env(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $normalized = strtolower((string) $value);

    return match ($normalized) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = $GLOBALS['config'] ?? [];

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_log(string $message, array $context = []): void
{
    $logLine = sprintf(
        "[%s] %s %s%s",
        date('Y-m-d H:i:s'),
        $message,
        $context === []
            ? ''
            : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        PHP_EOL
    );

    file_put_contents(base_path('logs/app.log'), $logLine, FILE_APPEND | LOCK_EX);
}
