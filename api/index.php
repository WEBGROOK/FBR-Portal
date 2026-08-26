<?php

// 1. Create writeable storage and cache directories in /tmp for Vercel serverless environment
$tmpStorage = '/tmp/storage';

$dirs = [
    $tmpStorage . '/app/public',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/testing',
    $tmpStorage . '/framework/views',
    $tmpStorage . '/logs',
    '/tmp/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// 2. Set environment variables for storage and logging
putenv('APP_STORAGE=' . $tmpStorage);
$_ENV['APP_STORAGE'] = $tmpStorage;

putenv('VIEW_COMPILED_PATH=' . $tmpStorage . '/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = $tmpStorage . '/framework/views';

putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';

// 3. Set fallback APP_KEY if not provided in Vercel Environment Variables
if (empty($_ENV['APP_KEY']) && empty(getenv('APP_KEY'))) {
    $fallbackKey = 'base64:duXVkPGa3MLAWcIECGr2Xwl82XGBttb/rz6d5QcaEJQ=';
    putenv('APP_KEY=' . $fallbackKey);
    $_ENV['APP_KEY'] = $fallbackKey;
}

// 4. Handle SQLite database fallback in /tmp if SQLite is used
$dbConn = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'sqlite';
if ($dbConn === 'sqlite') {
    $tmpSqlite = '/tmp/database.sqlite';
    $srcSqlite = __DIR__ . '/../database/database.sqlite';
    if (!file_exists($tmpSqlite) && file_exists($srcSqlite)) {
        @copy($srcSqlite, $tmpSqlite);
    }
    if (file_exists($tmpSqlite)) {
        putenv('DB_DATABASE=' . $tmpSqlite);
        $_ENV['DB_DATABASE'] = $tmpSqlite;
    }
}

// Forward request to Laravel's public/index.php
require __DIR__ . '/../public/index.php';
