<?php
$page_title = '首页';
require_once 'includes/header.php';

// 获取远程更新记录
$update_records = [];
$update_error = '';

try {
    $update_url = 'https://www.mcve.top/xm/json/txcos.json';
    $json_content = file_get_contents($update_url, false, stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
        'http' => [
            'timeout' => 10, // 10秒超时
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]));

    if ($json_content !== false) {
        $update_data = json_decode($json_content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($update_data['updates'])) {
            $update_records = $update_data['updates'];
        } else {
            $update_error = '更新记录解析失败';
        }
    } else {
        $update_error = '无法获取更新记录';
    }
} catch (Exception $e) {
    $update_error = '获取更新记录出错: ' . $e->getMessage();
}

// 赞助链接和加群链接
$sponsor_url = 'https://gfpay.yfuyn.cn/paypage/?merchant=a5d3O3UfCpZydceC56eH2PwjOua2F4ipknNFmx1VIZzb';
$group_url = 'https://qun.qq.com/universal-share/share?ac=1&authKey=3g1b25d%2B%2FbDh9Q0o6jXGFgCdLKksxlcGpuvBGeiAPK5B8KaCEQ6dk1do8ASS1zjt&busi_data=eyJncm91cENvZGUiOiIzMTg1Mjc1MTkiLCJ0b2tlbiI6ImxLVVIrWnFaTm9IQnRVV0pDYTRBWlZXZUlLQkFrWHgrcnE3c3ZVY20ybmUrcWQwRWMzcEFXNkM3WlhjS0xBQ00iLCJ1aW4iOiIyMDMwNTY4Mzc1In0%3D&data=ikkMSD4S0agYI3-67g0a3e1DYVTMeVApTY09pmv8jOVbTZVbdaqXizNhVBJxw4jB-DEaqXOHSzbHNVdGwMV7Jw&svctype=4&tempid=h5_group_info';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - COS管理器</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1890ff;
            --success: #52c41a;
            --warning: #faad14;
            --danger: #ff4d4f;
            --info: #13c2c2;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #333;
            --border: #e8e8e8;
            --shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        /* 卡片样式 */
        .card { background: var(--card-bg); border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow); transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .card-title { margin: 0; font-size: 18px; font-weight: 600; color: var(--text); }

        /* 按钮样式 */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.2s; text-decoration: none; font-weight: 500; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-info { background: var(--info); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .btn-block { display: flex; width: 100%; justify-content: center; }

        /* 网格布局 */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }

        /* 提示框 */
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: flex-start; }
        .alert-info { background-color: #e6f7ff; border: 1px solid #91d5ff; color: #0050b3; }
        .alert-warning { background-color: #fff7e6; border: 1px solid #ffd591; color: #873800; }
        .alert-danger { background-color: #fff2f0; border: 1px solid #ffccc7; color: #a8071a; }
        .alert-icon { margin-right: 12px; font-size: 20px; flex-shrink: 0; }
        .alert-content { flex-grow: 1; }
        .alert-title { font-weight: 600; margin-bottom: 4px; }

        /* 特性列表 */
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li { padding: 12px 0; display: flex; align-items: center; border-bottom: 1px solid #f0f0f0; }
        .feature-list li:last-child { border-bottom: none; }
        .feature-icon { margin-right: 12px; color: var(--primary); font-size: 18px; width: 24px; text-align: center; }

        /* 更新记录 */
        .update-list { max-height: 400px; overflow-y: auto; }
        .update-item { padding: 16px; border-bottom: 1px solid var(--border); transition: background-color 0.2s; }
        .update-item:hover { background-color: #fafafa; }
        .update-item:last-child { border-bottom: none; }
        .update-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .update-version { font-weight: 600; color: var(--primary); }
        .update-date { font-size: 12px; color: #999; }
        .update-description { color: #666; line-height: 1.5; }
        .update-download { margin-top: 8px; }

        /* 快速操作 */
        .quick-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .quick-action { flex: 1; min-width: 200px; display: flex; flex-direction: column; align-items: center; padding: 20px; border-radius: 8px; background: #fafafa; transition: all 0.2s; text-decoration: none; color: var(--text); }
        .quick-action:hover { background: #f0f0f0; transform: translateY(-2px); }
        .quick-action-icon { font-size: 32px; margin-bottom: 12px; color: var(--primary); }
        .quick-action-title { font-weight: 600; margin-bottom: 4px; }
        .quick-action-desc { font-size: 12px; color: #999; text-align: center; }

        /* 页脚 */
        .footer { text-align: center; padding: 20px 0; color: #999; font-size: 14px; border-top: 1px solid var(--border); margin-top: 40px; }
        .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 12px; }
        .footer-link { color: var(--primary); text-decoration: none; }
        .footer-link:hover { text-decoration: underline; }

        /* 徽章 */
        .badge { display: inline-block; padding: 2px 8px; font-size: 12px; border-radius: 10px; font-weight: 500; margin-left: 8px; }
        .badge-new { background: var(--danger); color: white; }
        .badge-update { background: var(--warning); color: white; }

        /* 响应式 */
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .container { padding: 12px; }
            .quick-actions { flex-direction: column; }
            .quick-action { min-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="card">
            <div class="card-header">
                <h1 class="card-title"><i class="fas fa-cloud"></i> 欢迎使用 COS 管理器</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo $sponsor_url; ?>" target="_blank" class="btn btn-danger">
                        <i class="fas fa-heart"></i> 赞助作者
                    </a>
                    <a href="<?php echo $group_url; ?>" target="_blank" class="btn btn-info">
                        <i class="fas fa-users"></i> 加入群聊
                    </a>
                </div>
            </div>
            <p style="margin-bottom: 16px; color: #666; line-height: 1.6;">
                这是一个简约美观的腾讯云 COS 对象存储管理平台，支持多存储桶分类管理、文件上传下载、分享和权限管理等功能。
            </p>

            <div class="alert alert-info">
                <div class="alert-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">删除文件夹失败提示</div>
                    <div>如果删除文件夹时提示：<code>删除失败: 操作失败: Validation errors: [Objects] is a required array</code>，说明文件夹中还有文件无法直接删除。请先清空文件夹中的所有文件，然后再次尝试删除。</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-bolt"></i> 快速操作</h2>
            </div>
            <div class="quick-actions">
                <a href="files.php" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="quick-action-title">文件管理</div>
                    <div class="quick-action-desc">浏览、上传、下载和管理文件</div>
                </a>

                <a href="settings.php" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="quick-action-title">存储设置</div>
                    <div class="quick-action-desc">配置和管理COS存储桶</div>
                </a>

                <a href="<?php echo $group_url; ?>" target="_blank" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="quick-action-title">交流群</div>
                    <div class="quick-action-desc">获取帮助和交流反馈</div>
                </a>

                <a href="<?php echo $sponsor_url; ?>" target="_blank" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <div class="quick-action-title">支持项目</div>
                    <div class="quick-action-desc">赞助支持项目持续发展</div>
                </a>
            </div>
        </div>

        <div class="grid">

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-star"></i> 主要特性</h2>
                </div>
                <ul class="feature-list">
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <strong>多存储桶管理</strong>
                            <div style="font-size: 13px; color: #666; margin-top: 2px;">支持多个COS存储桶分类管理</div>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div>
                            <strong>文件上传下载</strong>
                            <div style="font-size: 13px; color: #666; margin-top: 2px;">支持上传下载</div>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div>
                            <strong>文件分享</strong>
                            <div style="font-size: 13px; color: #666; margin-top: 2px;">生成分享链接，设置有效期和密码</div>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <strong>智能搜索</strong>
                            <div style="font-size: 13px; color: #666; margin-top: 2px">支持文件和文件夹名称搜索</div>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <strong>响应式设计</strong>
                            <div style="font-size: 13px; color: #666; margin-top: 2px">适配电脑、平板和手机等设备</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-history"></i> 更新记录</h2>
                    <?php if (!empty($update_records)): ?>
                        <span class="badge badge-update"><?php echo count($update_records); ?> 条更新</span>
                    <?php endif; ?>
                </div>

                <div class="update-list">
                    <?php if (empty($update_records) && !empty($update_error)): ?>
                        <div class="alert alert-warning">
                            <div class="alert-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">更新记录加载失败</div>
                                <div><?php echo htmlspecialchars($update_error); ?></div>
                            </div>
                        </div>
                    <?php elseif (empty($update_records)): ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-sync fa-spin" style="font-size: 32px; margin-bottom: 12px;"></i>
                            <p>正在加载更新记录...</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($update_records as $index => $update): ?>
                            <div class="update-item">
                                <div class="update-header">
                                    <span class="update-version">
                                        <?php echo htmlspecialchars($update['version'] ?? '未知版本'); ?>
                                        <?php if ($index === 0): ?>
                                            <span class="badge badge-new">最新</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="update-date">
                                        <?php echo htmlspecialchars($update['date'] ?? '未知日期'); ?>
                                    </span>
                                </div>
                                <div class="update-description">
                                    <?php
                                    if (is_array($update['description'] ?? '')) {
                                        echo '<ul style="margin: 8px 0; padding-left: 20px;">';
                                        foreach ($update['description'] as $item) {
                                            echo '<li>' . htmlspecialchars($item) . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo htmlspecialchars($update['description'] ?? '');
                                    }
                                    ?>
                                </div>
                                <?php if (!empty($update['download'])): ?>
                                    <div class="update-download">
                                        <a href="<?php echo htmlspecialchars($update['download']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> 下载更新
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line"></i> 使用统计</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div style="text-align: center; padding: 16px; background: #f7f9fc; border-radius: 6px;">
                    <div style="font-size: 32px; color: var(--primary); margin-bottom: 8px;">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 4px;">COS存储桶</div>
                    <div style="color: #666; font-size: 14px;">多存储桶支持</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f7f9fc; border-radius: 6px;">
                    <div style="font-size: 32px; color: var(--success); margin-bottom: 8px;">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 4px;">文件上传</div>
                    <div style="color: #666; font-size: 14px;">上传支持</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f7f9fc; border-radius: 6px;">
                    <div style="font-size: 32px; color: var(--warning); margin-bottom: 8px;">
                        <i class="fas fa-share-square"></i>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 4px;">文件分享</div>
                    <div style="color: #666; font-size: 14px;">链接分享功能</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f7f9fc; border-radius: 6px;">
                    <div style="font-size: 32px; color: var(--info); margin-bottom: 8px;">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 4px;">多端适配</div>
                    <div style="color: #666; font-size: 14px;">响应式设计</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="files.php" class="footer-link">
                    <i class="fas fa-folder"></i> 文件管理
                </a>
                <a href="settings.php" class="footer-link">
                    <i class="fas fa-cog"></i> 系统设置
                </a>
                <a href="<?php echo $group_url; ?>" target="_blank" class="footer-link">
                    <i class="fas fa-question-circle"></i> 帮助支持
                </a>
                <a href="<?php echo $sponsor_url; ?>" target="_blank" class="footer-link">
                    <i class="fas fa-heart"></i> 赞助项目
                </a>
            </div>
            <div style="margin-bottom: 8px;">
                <span>COS 管理器</span>
                <span style="margin: 0 8px;">•</span>
                <span>基于腾讯云 COS 对象存储</span>
            </div>
            <div style="color: #aaa; font-size: 12px;">
                <?php if (!empty($update_records)): ?>
                    最新版本: <?php echo htmlspecialchars($update_records[0]['version'] ?? '未知'); ?>
                    <span style="margin: 0 8px;">•</span>
                    更新日期: <?php echo htmlspecialchars($update_records[0]['date'] ?? '未知'); ?>
                <?php else: ?>
                    版本信息加载中...
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // 页面交互效果
    document.addEventListener('DOMContentLoaded', function() {
        // 为更新记录添加点击展开效果
        const updateItems = document.querySelectorAll('.update-item');
        updateItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // 如果不是点击在链接上，则切换展开状态
                if (!e.target.closest('a')) {
                    this.classList.toggle('expanded');
                }
            });
        });

        // 自动检查更新记录是否为空，如果为空且没有错误，则重新尝试加载
        <?php if (empty($update_records) && empty($update_error)): ?>
        setTimeout(function() {
            // 显示加载提示
            const updateList = document.querySelector('.update-list');
            if (updateList) {
                updateList.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <div class="loading-spinner" style="width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 12px;"></div>
                        <p>正在获取更新记录...</p>
                    </div>
                `;
            }

            // 重新加载页面（简单方式）
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        }, 5000);
        <?php endif; ?>

        // 添加一些动画效果
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 + (index * 100));
        });
    });

    // 创建加载动画的CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .update-item.expanded .update-description {
            max-height: none;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>