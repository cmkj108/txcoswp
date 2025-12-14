<?php
// view.php - 简化版
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';

session_start();

// 验证参数
$cos_id = $_GET['cos'] ?? 0;
$key = $_GET['key'] ?? '';

if (!$cos_id || !$key) {
    die('参数错误');
}

// 验证登录
if (!isset($_SESSION['user_id'])) {
    die('请先登录');
}

$user_id = $_SESSION['user_id'];

// 获取COS配置
try {
    $stmt = $pdo->prepare("SELECT * FROM cos_configs WHERE id = ? AND user_id = ?");
    $stmt->execute([$cos_id, $user_id]);
    $cos_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cos_info) {
        die('文件不存在或无权访问');
    }

    // 初始化COS客户端
    $cosClient = new Qcloud\Cos\Client([
        'region' => $cos_info['region'],
        'credentials' => [
            'secretId' => $cos_info['secret_id'],
            'secretKey' => $cos_info['secret_key']
        ]
    ]);

    // 生成预览URL
    $url = $cosClient->getObjectUrl($cos_info['bucket'], $key, '+2 hours');
    $filename = basename($key);

    // 判断文件类型
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    $isVideo = in_array($ext, ['mp4', 'avi', 'mov', 'mkv', 'webm']);
    $isPdf = $ext === 'pdf';

} catch (Exception $e) {
    die('加载文件失败: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>预览: <?php echo htmlspecialchars($filename); ?></title>
    <style>
        body { margin: 0; padding: 20px; background: #f5f5f5; font-family: sans-serif; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .preview { text-align: center; margin: 20px 0; min-height: 400px; }
        img, video { max-width: 100%; max-height: 70vh; }
        .actions { text-align: center; margin-top: 24px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 0 10px; background: #1890ff; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><?php echo htmlspecialchars($filename); ?></h2>
            <a href="files.php" class="btn">返回</a>
        </div>

        <div class="preview">
            <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($filename); ?>">
            <?php elseif ($isVideo): ?>
                <video controls>
                    <source src="<?php echo htmlspecialchars($url); ?>" type="video/<?php echo $ext; ?>">
                    您的浏览器不支持视频播放
                </video>
            <?php elseif ($isPdf): ?>
                <iframe src="<?php echo htmlspecialchars($url); ?>" style="width: 100%; height: 70vh; border: none;"></iframe>
            <?php else: ?>
                <div style="padding: 80px 20px; text-align: center; color: #666;">
                    <i class="fas fa-file" style="font-size: 60px; color: #ccc; margin-bottom: 20px;"></i>
                    <p>该文件类型不支持在线预览</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="<?php echo htmlspecialchars($url); ?>" download="<?php echo htmlspecialchars($filename); ?>" class="btn">
                下载文件
            </a>
            <button onclick="copyLink()" class="btn" style="background: #52c41a;">
                复制分享链接
            </button>
        </div>
    </div>

    <script>
    function copyLink() {
        const url = window.location.href;

        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                alert('链接已复制到剪贴板');
            }).catch(() => {
                showCopyPrompt(url);
            });
        } else {
            showCopyPrompt(url);
        }
    }

    function showCopyPrompt(url) {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('链接已复制到剪贴板');
    }
    </script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>