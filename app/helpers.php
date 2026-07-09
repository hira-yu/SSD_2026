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
    return app_path($path);
}

function app_base_path(): string
{
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $directory = str_replace('\\', '/', dirname($scriptName));

    if ($directory === '/' || $directory === '.' || $directory === '') {
        return '';
    }

    return '/' . trim($directory, '/');
}

function app_path(string $path): string
{
    if (preg_match('/^(https?:)?\/\//i', $path) === 1) {
        return $path;
    }

    $path = '/' . ltrim($path, '/');
    $basePath = app_base_path();

    if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
        return $path;
    }

    return $basePath === '' ? $path : $basePath . $path;
}

function product_image_url(?string $path): string
{
    $normalized = trim((string) $path);

    if ($normalized === '') {
        return asset_url('assets/img/products/placeholder.svg');
    }

    return asset_url($normalized);
}

function product_period_active(?string $startsAt, ?string $endsAt, ?DateTimeImmutable $now = null): bool
{
    $now ??= new DateTimeImmutable('now');
    $startsAt = trim((string) $startsAt);
    $endsAt = trim((string) $endsAt);

    if ($startsAt !== '' && $now < new DateTimeImmutable($startsAt)) {
        return false;
    }

    if ($endsAt !== '' && $now > new DateTimeImmutable($endsAt)) {
        return false;
    }

    return true;
}

function product_sale_active(array $product, ?DateTimeImmutable $now = null): bool
{
    $salePrice = (int) ($product['sale_price'] ?? 0);
    $regularPrice = (int) ($product['price'] ?? 0);

    return $salePrice > 0
        && $salePrice < $regularPrice
        && product_period_active(
            (string) ($product['sale_starts_at'] ?? ''),
            (string) ($product['sale_ends_at'] ?? ''),
            $now
        );
}

function product_effective_price(array $product): int
{
    return product_sale_active($product) ? (int) $product['sale_price'] : (int) ($product['price'] ?? 0);
}

/**
 * @return array{is_orderable: bool, label: string, class: string, period_label: string}
 */
function product_availability(array $product): array
{
    $stockQuantity = (int) ($product['stock_quantity_2'] ?? 0);
    $availableFrom = trim((string) ($product['available_from'] ?? ''));
    $availableUntil = trim((string) ($product['available_until'] ?? ''));
    $periodActive = product_period_active($availableFrom, $availableUntil);
    $periodLabel = '';

    if ($availableFrom !== '' || $availableUntil !== '') {
        $periodLabel = trim(sprintf(
            '%s%s%s',
            $availableFrom === '' ? '' : date('Y/m/d', strtotime($availableFrom)) . 'から',
            $availableFrom !== '' && $availableUntil !== '' ? ' ' : '',
            $availableUntil === '' ? '' : date('Y/m/d', strtotime($availableUntil)) . 'まで'
        ));
    }

    if (!$periodActive) {
        $now = new DateTimeImmutable('now');

        return [
            'is_orderable' => false,
            'label' => $availableFrom !== '' && $now < new DateTimeImmutable($availableFrom) ? '販売開始前' : '販売期間終了',
            'class' => 'status-ng',
            'period_label' => $periodLabel,
        ];
    }

    if ($stockQuantity < 1) {
        return [
            'is_orderable' => false,
            'label' => '在庫なし',
            'class' => 'status-ng',
            'period_label' => $periodLabel,
        ];
    }

    if ($stockQuantity <= 5) {
        return [
            'is_orderable' => true,
            'label' => '在庫残少 ご注文はお早めに！',
            'class' => 'status-low',
            'period_label' => $periodLabel,
        ];
    }

    return [
        'is_orderable' => true,
        'label' => '在庫あり',
        'class' => 'status-ok',
        'period_label' => $periodLabel,
    ];
}

/**
 * @param array<string, mixed> $product
 * @return array<string, mixed>
 */
function product_delivery_schedule(array $product): array
{
    if (empty($product['is_orderable'])) {
        $availability = product_availability($product);

        return [
            'supports_same_day' => false,
            'summary_type' => 'unavailable',
            'summary_text' => !$availability['is_orderable']
                ? '現在在庫がないため、入荷までお時間をいただく場合があります。'
                : '',
            'deadline_hours' => 0,
            'deadline_minutes' => 0,
            'arrival_date_label' => '',
        ];
    }

    $timezone = new DateTimeZone('Asia/Tokyo');
    $now = new DateTimeImmutable('now', $timezone);
    $cutoffToday = $now->setTime(15, 0);
    $supportsSameDay = $now <= $cutoffToday;
    $deadlineAt = $supportsSameDay ? $cutoffToday : $cutoffToday->modify('+1 day');
    $arrivalAt = $supportsSameDay ? $now : $now->modify('+1 day');
    $remainingSeconds = max(0, $deadlineAt->getTimestamp() - $now->getTimestamp());
    $deadlineHours = intdiv($remainingSeconds, 3600);
    $deadlineMinutes = intdiv($remainingSeconds % 3600, 60);
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    $weekday = $weekdays[(int) $arrivalAt->format('w')] ?? '';

    return [
        'supports_same_day' => $supportsSameDay,
        'summary_type' => 'orderable',
        'summary_text' => '',
        'deadline_hours' => $deadlineHours,
        'deadline_minutes' => $deadlineMinutes,
        'arrival_date_label' => $arrivalAt->format('Y年m月d日') . $weekday . '曜日',
    ];
}

function product_delivery_deadline_label(array $deliverySchedule): string
{
    $deadlineHours = (int) ($deliverySchedule['deadline_hours'] ?? 0);
    $deadlineMinutes = (int) ($deliverySchedule['deadline_minutes'] ?? 0);
    $deadlineParts = [];

    if ($deadlineHours > 0) {
        $deadlineParts[] = sprintf('%d時間', $deadlineHours);
    }

    if ($deadlineMinutes > 0) {
        $deadlineParts[] = sprintf('%d分', $deadlineMinutes);
    }

    $deadlineLabel = implode('と', $deadlineParts);

    if ($deadlineLabel !== '') {
        $deadlineLabel .= '以内';
    }

    return $deadlineLabel;
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
    $path = '/' . trim($path, '/');
    $basePath = app_base_path();

    if ($path === '//') {
        return '/';
    }

    if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
        $path = substr($path, strlen($basePath));
        return $path === '' ? '/' : $path;
    }

    return $path === '' ? '/' : $path;
}

function redirect(string $path): never
{
    header('Location: ' . app_path($path), true, 302);
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
