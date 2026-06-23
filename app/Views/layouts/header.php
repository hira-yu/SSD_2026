<?php

declare(strict_types=1);

$applicationName = (string) config('app.name', '通信販売システム');
$documentTitle = isset($pageTitle) ? $pageTitle . ' | ' . $applicationName : $applicationName;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($documentTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="brand-block">
            <p class="eyebrow">Lightweight PHP MVC</p>
            <h1><?= e($applicationName) ?></h1>
        </div>
        <nav class="site-nav" aria-label="主要メニュー">
            <a href="/">トップ</a>
            <a href="/system/db-check">DB接続確認</a>
        </nav>
    </div>
</header>
<main class="container page-content">
