<?php
// includes/db.php
static $pdo;
if ($pdo) return $pdo;

$db_config = require __DIR__ . '/../db_config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    return $pdo;
} catch (PDOException $e) {
    error_log("DB Connect Error: " . $e->getMessage());
    throw new Exception('数据库连接失败');
}