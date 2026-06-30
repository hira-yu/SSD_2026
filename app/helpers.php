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

function asset_url(string $path): string
{
    return '/' . ltrim($path, '/');
}

function product_image_url(?string $path): string
{
    $normalized = trim((string) $path);

    if ($normalized === '') {
        return asset_url('assets/img/products/placeholder.svg');
    }

    return asset_url($normalized);
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

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return $path === '' ? '/' : $path;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function session_get(string $key, mixed $default = null): mixed
{
    return $_SESSION[$key] ?? $default;
}

function session_forget(string ...$keys): void
{
    foreach ($keys as $key) {
        unset($_SESSION[$key]);
    }
}

function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function get_flash(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old_input(string $key, mixed $default = ''): mixed
{
    $old = $_SESSION['_old_input'][$key] ?? $default;

    return $old;
}

function store_old_input(array $input): void
{
    $_SESSION['_old_input'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? null;

    return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
}
