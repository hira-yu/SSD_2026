<?php

declare(strict_types=1);

$publicPath = __DIR__ . '/public';
$requestPath = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($requestPath !== '/' && file_exists($publicPath . $requestPath)) {
    return false;
}

require $publicPath . '/index.php';
