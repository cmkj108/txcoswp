<?php
session_start();
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}
$db_config = include 'db_config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8",
        $db_config['user'],
        $db_config['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
$allowed_pages = ['index.php', 'install.php', 'logout.php'];
$current_page = basename($_SERVER['SCRIPT_NAME']);
if (!in_array($current_page, $allowed_pages) && !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>