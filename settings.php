<?php
$page_title = '设置';
require_once 'includes/header.php';

$message = '';

$user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);

        if (empty($new_username)) {
            $message = '<div class="alert alert-danger">用户名不能为空</div>';
        } else {

            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->execute([$new_username, $_SESSION['user_id']]);

            if ($check_stmt->rowCount() > 0) {
                $message = '<div class="alert alert-warning">用户名已存在</div>';
            } else {
                $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")
                    ->execute([$new_username, $_SESSION['user_id']]);
                $message = '<div class="alert alert-success">用户名修改成功</div>';
                $user_info['username'] = $new_username;
            }
        }
    }
    elseif (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            $message = '<div class="alert alert-danger">新密码与确认密码不一致</div>';
        } else {
            $user = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $user->execute([$_SESSION['user_id']]);
            $user = $user->fetch(PDO::FETCH_ASSOC);

            if (password_verify($old_pass, $user['password'])) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([$hashed, $_SESSION['user_id']]);
                $message = '<div class="alert alert-success">密码修改成功</div>';
            } else {
                $message = '<div class="alert alert-danger">原密码错误</div>';
            }
        }
    }
    elseif (isset($_POST['add_cos'])) {

        $check_stmt = $pdo->prepare("SELECT id FROM cos_configs WHERE user_id = ? AND name = ?");
        $check_stmt->execute([$_SESSION['user_id'], $_POST['cos_name']]);

        if ($check_stmt->rowCount() > 0) {
            $message = '<div class="alert alert-warning">配置名称已存在</div>';
        } else {

            $is_primary = isset($_POST['set_primary']) ? 1 : 0;

            $pdo->prepare("
                INSERT INTO cos_configs (user_id, name, region, bucket, secret_id, secret_key, is_primary)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $_SESSION['user_id'],
                $_POST['cos_name'],
                $_POST['cos_region'],
                $_POST['cos_bucket'],
                $_POST['cos_secret_id'],
                $_POST['cos_secret_key'],
                $is_primary
            ]);
            $message = '<div class="alert alert-success">COS配置添加成功</div>';
        }
    }
    elseif (isset($_POST['delete_cos'])) {
        $pdo->prepare("DELETE FROM cos_configs WHERE id = ? AND user_id = ?")
            ->execute([$_POST['cos_id'], $_SESSION['user_id']]);
        $message = '<div class="alert alert-success">COS配置已删除</div>';
    }
}

$cos_configs = $pdo->prepare("SELECT * FROM cos_configs WHERE user_id = ? ORDER BY created_at DESC");
$cos_configs->execute([$_SESSION['user_id']]);
$cos_configs = $cos_configs->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .settings-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
    }

    .settings-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .settings-title i {
        color: var(--primary);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: var(--text);
        font-size: 14px;
    }

    .form-input {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--text);
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 500;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
    }

    .btn-danger {
        background: var(--danger);
        color: white;
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-success {
        background: var(--success);
        color: white;
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text);
    }

    .btn-outline:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .cos-configs {
        margin-top: 20px;
    }

    .cos-config {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
    }

    .cos-config-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .cos-config-name {
        font-weight: 600;
        color: var(--text);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cos-config-name i {
        color: var(--primary);
    }

    .cos-config-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 13px;
        color: var(--text);
    }

    .cos-config-meta span {
        background: rgba(24, 144, 255, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .cos-config-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .cos-config-info {
        font-size: 13px;
        color: var(--text);
        margin-top: 8px;
    }

    .cos-config-info code {
        background: rgba(0, 0, 0, 0.05);
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text);
        opacity: 0.7;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 20px;
    }

    .modal-content {
        background: var(--card-bg);
        border-radius: 8px;
        padding: 24px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        color: var(--text);
    }

    .modal-close {
        background: none;
        border: none;
        color: var(--text);
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
        opacity: 0.7;
    }

    .modal-close:hover {
        opacity: 1;
    }

    .modal-footer {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .modal-footer .btn {
        flex: 1;
        padding: 10px;
    }

    @media (max-width: 768px) {
        .settings-container {
            padding: 16px;
        }

        .settings-section {
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .cos-config-header {
            flex-direction: column;
            gap: 12px;
        }

        .cos-config-actions {
            flex-wrap: wrap;
        }

        .modal-content {
            padding: 20px;
        }

        .btn {
            padding: 12px;
            font-size: 15px;
        }
    }

    @media (max-width: 480px) {
        .settings-container {
            padding: 12px;
        }

        .settings-section {
            padding: 16px;
        }

        .cos-config-meta {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<div class="settings-container">
    <h2 style="margin-bottom: 24px; color: var(--text);">设置</h2>

    <?= $message ?>

    <!-- 个人信息设置 -->
    <div class="settings-section">
        <h3 class="settings-title"><i class="fas fa-user"></i> 个人信息</h3>
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input type="text"
                       name="username"
                       class="form-input"
                       value="<?= htmlspecialchars($user_info['username']) ?>"
                       required>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> 保存
            </button>
        </form>
    </div>

    <!-- 密码修改 -->
    <div class="settings-section">
        <h3 class="settings-title"><i class="fas fa-lock"></i> 修改密码</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>原密码</label>
                    <input type="password" name="old_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>新密码</label>
                    <input type="password" name="new_password" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" name="confirm_password" class="form-input" required>
            </div>
            <button type="submit" name="update_password" class="btn btn-primary">
                <i class="fas fa-save"></i> 修改密码
            </button>
        </form>
    </div>

    <!-- COS存储配置 -->
    <div class="settings-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="settings-title" style="margin: 0; padding: 0; border: none;"><i class="fas fa-cloud"></i> COS存储配置</h3>
            <button type="button" class="btn btn-primary" onclick="showAddModal()">
                <i class="fas fa-plus"></i> 添加配置
            </button>
        </div>

        <?php if (empty($cos_configs)): ?>
            <div class="empty-state">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>暂无存储配置</p>
            </div>
        <?php else: ?>
            <div class="cos-configs">
                <?php foreach ($cos_configs as $cos): ?>
                    <div class="cos-config">
                        <div class="cos-config-header">
                            <div class="cos-config-name">
                                <i class="fas fa-cloud"></i>
                                <?= htmlspecialchars($cos['name']) ?>
                            </div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定删除此配置吗？')">
                                <input type="hidden" name="cos_id" value="<?= $cos['id'] ?>">
                                <button type="submit" name="delete_cos" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <div class="cos-config-meta">
                            <span><i class="fas fa-globe"></i> <?= htmlspecialchars($cos['region']) ?></span>
                            <span><i class="fas fa-database"></i> <?= htmlspecialchars($cos['bucket']) ?></span>
                        </div>

                        <div class="cos-config-info">
                            <p><strong>SecretId:</strong> <code><?= substr(htmlspecialchars($cos['secret_id']), 0, 16) ?>...</code></p>
                            <p><strong>SecretKey:</strong> <code>****************</code></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加COS配置模态框 -->
<div id="addCosModal" class="modal-overlay" onclick="hideAddModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> 添加COS配置</h3>
            <button type="button" class="modal-close" onclick="hideAddModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>配置名称</label>
                <input type="text" name="cos_name" class="form-input" required placeholder="例如：腾讯云北京存储">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>区域</label>
                    <input type="text" name="cos_region" class="form-input" required placeholder="如：ap-beijing">
                </div>
                <div class="form-group">
                    <label>存储桶</label>
                    <input type="text" name="cos_bucket" class="form-input" required placeholder="如：mybucket">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>SecretId</label>
                    <input type="text" name="cos_secret_id" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>SecretKey</label>
                    <input type="password" name="cos_secret_key" class="form-input" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideAddModal()">取消</button>
                <button type="submit" name="add_cos" class="btn btn-primary">保存配置</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showAddModal() {
        document.getElementById('addCosModal').style.display = 'flex';
    }

    function hideAddModal() {
        document.getElementById('addCosModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('addCosModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    hideAddModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideAddModal();
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>