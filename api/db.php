<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $file = dirname(__DIR__) . '/private/config.php';
    $config = file_exists($file) ? require $file : [];
    $host = getenv('DB_HOST') ?: ($config['host'] ?? 'localhost');
    $name = getenv('DB_NAME') ?: ($config['name'] ?? '');
    $user = getenv('DB_USER') ?: ($config['user'] ?? '');
    $pass = getenv('DB_PASS') ?: ($config['pass'] ?? '');
    if ($name === '' || $user === '') throw new RuntimeException('Database is not configured.');
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
