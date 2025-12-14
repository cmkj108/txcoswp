<?php
session_start();

// 如果已登录，跳首页
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 如果未安装，跳安装页
if (!file_exists(__DIR__ . '/installed.lock')) {
    header('Location: install.php');
    exit;
}

// 安全加载 db_config.php
$db_config_file = __DIR__ . '/db_config.php';
if (!file_exists($db_config_file)) {
    die('错误：数据库配置文件缺失，请重新安装。');
}

$db_config = include $db_config_file;

if (!is_array($db_config) || empty($db_config['host'])) {
    die('错误：数据库配置无效，请检查 db_config.php 文件。');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8",
            $db_config['user'],
            $db_config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $message = '<div class="alert alert-error">用户名或密码错误</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-error">数据库连接失败</div>';
    }
}

// 检查主题设置
$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : 'light';
$is_dark = $theme === 'dark';
?>

<!DOCTYPE html>
<html lang="zh-CN" class="<?php echo $is_dark ? 'dark-mode' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登录 - COS管理器</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-color);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-icon {
            width: 64px;
            height: 64px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 24px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin: 0;
        }

        .login-form {
            margin-top: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text-primary);
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            transition: all 0.2s;
            -webkit-appearance: none;
            appearance: none;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
        }

        /* 桌面端的登录按钮 */
        .btn-login-desktop {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .btn-login-desktop:hover {
            background: var(--primary-hover);
        }

        .btn-login-desktop:active {
            transform: scale(0.98);
        }

        /* 移动端固定登录按钮 */
        .btn-login-mobile {
            display: none; /* 默认隐藏，只在移动端显示 */
        }

        .input-wrapper {
            position: relative;
        }

        /* 密码显示/隐藏按钮 */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            font-size: 16px;
        }

        /* 移动端优化 */
        @media (max-width: 768px) {
            .login-page {
                padding: 0;
                align-items: flex-start;
                padding-top: 40px;
            }

            .login-container {
                max-width: 100%;
                padding: 0 20px;
            }

            .login-card {
                padding: 30px 20px;
                border-radius: 0;
                border: none;
                box-shadow: none;
                background: transparent;
                width: 100%;
            }

            /* 移动端显示固定登录按钮，隐藏桌面按钮 */
            .btn-login-desktop {
                display: none;
            }

            .btn-login-mobile {
                display: block;
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                z-index: 1000;
                padding: 16px;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
                box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
            }

            .btn-login-mobile:active {
                background: var(--primary-hover);
                transform: scale(0.98);
            }

            /* 为固定按钮留出空间 */
            .login-form {
                margin-bottom: 80px; /* 给固定按钮留出空间 */
            }

            /* 移动端触摸优化 */
            .form-input,
            .password-toggle {
                -webkit-tap-highlight-color: transparent;
                min-height: 44px; /* 最小触摸目标尺寸 */
            }

            .form-input {
                font-size: 16px !important;
            }
        }

        /* 超小屏幕优化 */
        @media (max-width: 480px) {
            .login-header {
                margin-bottom: 24px;
            }

            .login-icon {
                width: 56px;
                height: 56px;
                font-size: 20px;
            }

            .login-title {
                font-size: 20px;
            }

            .login-subtitle {
                font-size: 13px;
            }

            .btn-login-mobile {
                left: 16px;
                right: 16px;
                bottom: 16px;
                padding: 14px;
            }

            .login-form {
                margin-bottom: 70px;
            }
        }

        /* 横屏模式优化 */
        @media (max-height: 500px) and (orientation: landscape) {
            .login-page {
                padding-top: 20px;
                align-items: flex-start;
            }

            .login-card {
                padding: 20px;
            }

            .login-header {
                margin-bottom: 20px;
            }

            .login-icon {
                width: 48px;
                height: 48px;
                font-size: 18px;
                margin-bottom: 12px;
            }

            .login-title {
                font-size: 18px;
            }

            .login-subtitle {
                font-size: 12px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .btn-login-mobile {
                padding: 12px;
                font-size: 15px;
            }

            .login-form {
                margin-bottom: 60px;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="login-title">COS管理器</h1>
                <p class="login-subtitle">请输入用户名和密码</p>
            </div>

            <?php echo $message; ?>

            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">用户名</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-input"
                           placeholder="用户名"
                           required
                           autofocus
                           autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="密码"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="显示/隐藏密码">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login-desktop">登录</button>
            </form>
        </div>
    </div>

    <button type="button" class="btn-login-mobile" id="mobileLoginBtn">登录</button>

    <script>
        // 密码显示/隐藏切换
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // 检测移动端
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // 移动端按钮点击事件
        document.addEventListener('DOMContentLoaded', function() {
            const mobileLoginBtn = document.getElementById('mobileLoginBtn');
            const loginForm = document.getElementById('loginForm');

            // 移动端固定按钮点击事件
            mobileLoginBtn.addEventListener('click', function() {
                // 验证表单
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;

                if (!username) {
                    document.getElementById('username').focus();
                    return;
                }

                if (!password) {
                    document.getElementById('password').focus();
                    return;
                }

                // 显示加载状态
                mobileLoginBtn.disabled = true;
                mobileLoginBtn.innerHTML = '登录中...';
                mobileLoginBtn.style.opacity = '0.7';

                // 提交表单
                loginForm.submit();
            });

            // 监听输入框变化，实时验证
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            function validateForm() {
                const usernameValid = usernameInput.value.trim().length > 0;
                const passwordValid = passwordInput.value.length > 0;

                if (usernameValid && passwordValid) {
                    mobileLoginBtn.style.opacity = '1';
                } else {
                    mobileLoginBtn.style.opacity = '0.7';
                }
            }

            usernameInput.addEventListener('input', validateForm);
            passwordInput.addEventListener('input', validateForm);

            // 移动端键盘的"前往/完成"按钮
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (isMobile()) {
                        mobileLoginBtn.click();
                    } else {
                        loginForm.submit();
                    }
                }
            });

            // 移动端触摸反馈
            mobileLoginBtn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });

            mobileLoginBtn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });

            // 移动端长按显示密码
            const passwordToggle = document.querySelector('.password-toggle');
            let holdTimer;

            passwordToggle.addEventListener('touchstart', function(e) {
                e.preventDefault();
                holdTimer = setTimeout(() => {
                    const passwordInput = document.getElementById('password');
                    passwordInput.type = 'text';
                    this.querySelector('i').className = 'fas fa-eye-slash';

                    // 3秒后自动隐藏
                    setTimeout(() => {
                        if (passwordInput.type === 'text') {
                            passwordInput.type = 'password';
                            this.querySelector('i').className = 'fas fa-eye';
                        }
                    }, 3000);
                }, 500);
            });

            passwordToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                clearTimeout(holdTimer);
            });

            // 初始验证
            validateForm();

            // 移动端自动聚焦用户名输入框（但小心虚拟键盘）
            if (isMobile() && window.innerHeight > 400) {
                setTimeout(() => {
                    usernameInput.focus();
                }, 300);
            }
        });

        // 防止页面缩放
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, { passive: false });

        // 虚拟键盘处理
        let originalHeight = window.innerHeight;

        window.addEventListener('resize', function() {
            if (window.innerHeight < originalHeight) {
                // 键盘弹出，调整布局
                document.querySelector('.btn-login-mobile').style.bottom = '10px';
            } else {
                // 键盘收起，恢复布局
                document.querySelector('.btn-login-mobile').style.bottom = '20px';
            }
        });
    </script>
</body>
</html>