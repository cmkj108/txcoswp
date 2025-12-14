<?php
session_start();

// 检查分享 ID 是否存在
if (!isset($_GET['id']) || !preg_match('/^[a-f0-9]{32}$/', $_GET['id'])) {
    http_response_code(400);
    die('无效的分享链接');
}

$share_id = $_GET['id'];
$session_key = 'share_' . $share_id;

// 检查分享信息是否存在（简化：存于 session，实际建议用数据库 + 过期时间）
if (!isset($_SESSION[$session_key])) {
    http_response_code(404);
    die('分享链接已过期或不存在');
}

$share_data = $_SESSION[$session_key];
$cos_url = $share_data['url'] ?? '';
$hashed_password = $share_data['password'] ?? null;

// 如果设置了密码，需要验证
if ($hashed_password && !isset($_POST['password'])) {
    // 显示密码输入表单
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>请输入分享密码</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { padding-top: 0; }
            .share-card {
                max-width: 400px;
                margin: 100px auto;
                text-align: center;
            }
            .lock-icon {
                font-size: 3rem;
                color: #4361ee;
                margin-bottom: 24px;
            }
        </style>
    </head>
    <body class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark-mode' : ''; ?>">
        <div class="share-card card">
            <div class="lock-icon"><i class="fas fa-lock"></i></div>
            <h2>受保护的分享</h2>
            <p style="margin: 16px 0;">此文件设置了访问密码，请输入后查看。</p>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert error">密码错误，请重试</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="password" name="password" placeholder="请输入密码" required autofocus style="width:100%; padding:12px; border-radius:12px; border:1px solid #ddd;">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">确认</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 处理密码提交
if ($hashed_password) {
    if (!password_verify($_POST['password'], $hashed_password)) {
        header('Location: ?id=' . urlencode($share_id) . '&error=1');
        exit;
    }
}

// 密码正确或无需密码 → 跳转到 COS 临时 URL
header('Location: ' . $cos_url);
exit;
?>