<?php
// includes/header.php - 响应式修复版
require_once 'config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查登录状态
if (!isset($_SESSION['user_id']) && basename($_SERVER['SCRIPT_NAME']) !== 'login.php' && basename($_SERVER['SCRIPT_NAME']) !== 'install.php') {
    header('Location: login.php');
    exit;
}

// 获取用户信息（如果已登录）
if (isset($_SESSION['user_id'])) {
    $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
}

$is_dark = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark';
?>
<!DOCTYPE html>
<html lang="zh-CN" class="<?php echo $is_dark ? 'dark-mode' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'COS管理器'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 确保主题切换立即生效 */
        :root {
            --primary: #1890ff;
            --primary-hover: #40a9ff;
            --primary-active: #096dd9;
            --success: #52c41a;
            --warning: #faad14;
            --danger: #ff4d4f;
            --info: #13c2c2;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #333333;
            --border: #e8e8e8;
            --shadow: 0 2px 8px rgba(0,0,0,0.06);
            --radius: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .dark-mode {
            --primary: #177ddc;
            --primary-hover: #3c9ae8;
            --primary-active: #096dd9;
            --success: #49aa19;
            --warning: #d89614;
            --danger: #a61d24;
            --info: #13a8a8;
            --bg: #141414;
            --card-bg: #1f1f1f;
            --text: #e6e6e6;
            --border: #434343;
            --shadow: 0 2px 8px rgba(0,0,0,0.36);
        }
        
        body {
            background: var(--bg);
            color: var(--text);
            transition: background-color 0.3s, color 0.3s;
            margin: 0;
            padding: 0;
        }
        
        /* 导航栏样式 */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 64px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
        
        .nav-brand i {
            color: var(--primary);
            font-size: 24px;
        }
        
        /* 桌面端导航菜单 */
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 32px;
            margin: 0;
            padding: 0;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            opacity: 0.7;
        }
        
        .nav-menu a:hover {
            opacity: 1;
            background: rgba(24, 144, 255, 0.1);
        }
        
        .nav-menu a.active {
            opacity: 1;
            color: var(--primary);
            background: rgba(24, 144, 255, 0.1);
        }
        
        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 12px;
            right: 12px;
            height: 2px;
            background: var(--primary);
            border-radius: 1px;
        }
        
        /* 导航栏操作按钮 */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* 移动端汉堡菜单按钮 */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius);
            transition: var(--transition);
        }
        
        .menu-toggle:hover {
            background: rgba(24, 144, 255, 0.1);
            color: var(--primary);
        }
        
        /* 移动端侧边栏遮罩 */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            opacity: 1;
        }
        
        /* 移动端侧边栏 */
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: var(--card-bg);
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .mobile-sidebar.show {
            left: 0;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
        
        .sidebar-brand i {
            color: var(--primary);
            font-size: 24px;
        }
        
        .sidebar-close {
            background: none;
            border: none;
            color: var(--text);
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
            border-radius: var(--radius);
            transition: var(--transition);
        }
        
        .sidebar-close:hover {
            background: rgba(24, 144, 255, 0.1);
            color: var(--primary);
        }
        
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .user-info h4 {
            margin: 0;
            font-size: 16px;
            color: var(--text);
        }
        
        .user-info p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: var(--text);
            opacity: 0.7;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: var(--text);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            opacity: 0.7;
        }
        
        .sidebar-menu a:hover {
            opacity: 1;
            background: rgba(24, 144, 255, 0.1);
            border-left-color: var(--primary);
        }
        
        .sidebar-menu a.active {
            opacity: 1;
            color: var(--primary);
            background: rgba(24, 144, 255, 0.1);
            border-left-color: var(--primary);
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
        }
        
        .sidebar-actions {
            display: flex;
            gap: 12px;
        }
        
        .theme-toggle, .logout-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--bg);
            color: var(--text);
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .theme-toggle:hover, .logout-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .logout-btn:hover {
            border-color: var(--danger);
            color: var(--danger);
        }
        
        /* 主内容区域 */
        .main-content {
            min-height: calc(100vh - 64px);
            background: var(--bg);
            padding: 24px;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            /* 隐藏桌面端导航菜单 */
            .nav-menu, .nav-actions {
                display: none;
            }
            
            /* 显示汉堡菜单按钮 */
            .menu-toggle {
                display: block;
            }
            
            /* 调整导航栏 */
            .navbar {
                padding: 0 16px;
            }
            
            /* 调整主内容区域 */
            .main-content {
                padding: 16px;
                min-height: calc(100vh - 56px);
            }
        }
        
        @media (min-width: 769px) {
            /* 桌面端隐藏侧边栏相关元素 */
            .mobile-sidebar, .sidebar-overlay {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- 桌面端导航栏 -->
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>COS管理器</span>
        </div>
        
        <ul class="nav-menu">
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>首页</span>
                </a>
            </li>
            <li>
                <a href="files.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'files.php' ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i>
                    <span>文件管理</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>设置</span>
                </a>
            </li>
        </ul>
        
        <div class="nav-actions">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
        
        <!-- 移动端汉堡菜单按钮 -->
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
    
    <!-- 移动端侧边栏遮罩 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 移动端侧边栏 -->
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>COS管理器</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if (isset($user)): ?>
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                <p>已登录</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>首页</span>
            </a>
            <a href="files.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'files.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder"></i>
                <span>文件管理</span>
            </a>
            <a href="settings.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>设置</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
                </button>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>退出</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- 主内容区域 -->
    <main class="main-content">
    
    <script>
        // 移动端侧边栏控制
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileSidebar = document.getElementById('mobileSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            
            // 打开侧边栏
            function openSidebar() {
                mobileSidebar.classList.add('show');
                sidebarOverlay.style.display = 'block';
                setTimeout(() => {
                    sidebarOverlay.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden'; // 防止背景滚动
            }
            
            // 关闭侧边栏
            function closeSidebar() {
                mobileSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                setTimeout(() => {
                    sidebarOverlay.style.display = 'none';
                }, 300);
                document.body.style.overflow = '';
            }
            
            // 绑定事件
            if (menuToggle) {
                menuToggle.addEventListener('click', openSidebar);
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            // 点击侧边栏链接后自动关闭侧边栏
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.addEventListener('click', closeSidebar);
            });
            
            // 主题切换功能
            function setupThemeToggle(buttonId) {
                const button = document.getElementById(buttonId);
                if (button) {
                    button.addEventListener('click', function() {
                        const isDark = document.documentElement.classList.contains('dark-mode');
                        const newTheme = isDark ? 'light' : 'dark';
                        
                        // 切换类名
                        document.documentElement.classList.toggle('dark-mode');
                        
                        // 更新所有主题切换按钮的图标
                        const themeButtons = document.querySelectorAll('.theme-toggle i');
                        themeButtons.forEach(icon => {
                            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                        });
                        
                        // 保存到cookie
                        document.cookie = `theme=${newTheme}; path=/; max-age=${365 * 24 * 60 * 60}`;
                        
                        // 如果是移动端，关闭侧边栏
                        if (window.innerWidth <= 768) {
                            closeSidebar();
                        }
                    });
                }
            }
            
            // 设置桌面端和移动端的主题切换按钮
            setupThemeToggle('theme-toggle');
            setupThemeToggle('mobileThemeToggle');
            
            // 监听窗口大小变化
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    // 桌面端时确保侧边栏关闭
                    closeSidebar();
                }
            });
            
            // ESC键关闭侧边栏
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
        });
    </script>
</body>
</html>