<?php
// files.php - 增强版（增加分页、搜索、刷新功能）
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';

session_start();

// 检查登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = '文件管理';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// 获取用户COS配置
try {
    $stmt = $pdo->prepare("SELECT * FROM cos_configs WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $cos_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cos_configs = [];
    $error = "加载配置失败: " . $e->getMessage();
}

$current_cos = $_GET['cos'] ?? ($cos_configs[0]['id'] ?? 0);
$prefix = $_GET['prefix'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1)); // 当前页码
$per_page = 50; // 每页显示数量
$search_keyword = trim($_GET['search'] ?? ''); // 搜索关键词
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1890ff;
            --success: #52c41a;
            --warning: #faad14;
            --danger: #ff4d4f;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #333;
            --border: #e8e8e8;
        }
        
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .card { background: var(--card-bg); border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.2s; text-decoration: none; white-space: nowrap; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn:hover { opacity: 0.9; }
        
        .breadcrumb { padding: 12px; background: #fafafa; border-radius: 4px; margin-bottom: 16px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .breadcrumb a { color: var(--primary); text-decoration: none; }
        .breadcrumb .separator { color: #999; }
        
        /* 文件列表容器 - 移动端优化 */
        .file-list-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .file-list { width: 100%; border-collapse: collapse; min-width: 600px; }
        .file-list th, .file-list td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: top; }
        .file-list th { background: #fafafa; font-weight: 600; }
        .file-list tr:hover { background: #fafafa; }
        
        /* 移动端文件名过长处理 */
        .file-name-cell { max-width: 200px; min-width: 150px; }
        @media (max-width: 768px) {
            .file-name-cell { max-width: 150px; }
        }
        .file-name { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .file-name-text { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-name a { color: var(--primary); text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        /* 操作按钮组 - 移动端优化 */
        .file-actions { display: flex; gap: 4px; flex-wrap: nowrap; white-space: nowrap; }
        .file-actions .btn { padding: 4px 8px; font-size: 12px; }
        
        /* 上传区域 */
        .upload-area { margin: 20px 0; }
        .progress-bar { height: 4px; background: #e8e8e8; border-radius: 2px; overflow: hidden; margin-top: 8px; }
        .progress-fill { height: 100%; background: var(--success); transition: width 0.3s; }
        
        /* 搜索框样式 */
        .search-box { position: relative; flex-grow: 1; max-width: 400px; min-width: 200px; }
        .search-input { width: 100%; padding: 8px 40px 8px 16px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; }
        .search-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #999; cursor: pointer; }
        
        /* 分页样式 */
        .pagination { display: flex; justify-content: center; align-items: center; margin-top: 24px; gap: 4px; flex-wrap: wrap; }
        .pagination-btn { padding: 6px 12px; border: 1px solid var(--border); background: white; border-radius: 4px; cursor: pointer; min-width: 36px; text-align: center; }
        .pagination-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .pagination-btn.disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination-info { margin: 0 12px; font-size: 14px; color: #666; }
        
        /* 操作栏样式 */
        .action-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        
        /* 刷新按钮样式 */
        .refresh-btn { padding: 8px; border: none; background: none; cursor: pointer; color: #666; border-radius: 4px; }
        .refresh-btn:hover { background: #f5f5f5; color: var(--primary); }
        
        /* 批量上传区域 */
        .batch-upload-area { border: 2px dashed var(--border); border-radius: 8px; padding: 40px 20px; text-align: center; margin: 20px 0; transition: all 0.3s; cursor: pointer; }
        .batch-upload-area:hover, .batch-upload-area.dragover { border-color: var(--primary); background: rgba(24, 144, 255, 0.05); }
        .batch-upload-area i { font-size: 48px; color: #999; margin-bottom: 16px; }
        .batch-upload-text { font-size: 16px; color: #666; margin-bottom: 8px; }
        .batch-upload-hint { font-size: 14px; color: #999; }
        
        /* 批量上传文件列表 */
        .batch-file-list { margin-top: 20px; max-height: 300px; overflow-y: auto; }
        .batch-file-item { display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--border); border-radius: 4px; margin-bottom: 8px; background: white; }
        .batch-file-icon { color: var(--primary); }
        .batch-file-info { flex: 1; min-width: 0; }
        .batch-file-name { font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .batch-file-size { font-size: 12px; color: #999; margin-top: 2px; }
        .batch-file-progress { width: 100%; height: 4px; background: #e8e8e8; border-radius: 2px; overflow: hidden; margin-top: 4px; }
        .batch-file-progress-bar { height: 100%; background: var(--success); transition: width 0.3s; }
        .batch-file-status { font-size: 12px; white-space: nowrap; }
        .batch-file-status.success { color: var(--success); }
        .batch-file-status.error { color: var(--danger); }
        .batch-file-status.uploading { color: var(--primary); }
        
        /* 批量上传控制栏 */
        .batch-controls { display: flex; gap: 10px; margin-top: 16px; justify-content: flex-end; }
        
        /* 模态框 */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 400px; overflow: hidden; max-height: 90vh; display: flex; flex-direction: column; }
        .modal-header { padding: 16px; border-bottom: 1px solid var(--border); font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; flex: 1; overflow-y: auto; }
        .modal-footer { padding: 16px; border-top: 1px solid var(--border); display: flex; gap: 12px; justify-content: flex-end; }
        .modal-input { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; margin-top: 8px; }
        
        /* 加载动画 */
        .loading-spinner { 
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            .container { padding: 12px; }
            .card { padding: 16px; }
            .header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .action-bar { gap: 8px; }
            .search-box { max-width: 100%; }
            .file-name-cell { max-width: 120px; }
            .pagination { gap: 2px; }
            .pagination-btn { padding: 4px 8px; min-width: 32px; }
            .modal-content { width: 100%; max-height: 80vh; }
        }
        
        /* 文件类型图标颜色 */
        .fa-folder { color: #faad14; }
        .fa-file-image { color: #52c41a; }
        .fa-file-pdf { color: #ff4d4f; }
        .fa-file-word { color: #1890ff; }
        .fa-file-excel { color: #52c41a; }
        .fa-file-powerpoint { color: #faad14; }
        .fa-file-archive { color: #722ed1; }
        .fa-file-audio, .fa-file-video { color: #13c2c2; }
        .fa-file-code { color: #722ed1; }
        .fa-file { color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-cloud"></i> 文件管理</h1>
                <?php if (!empty($cos_configs)): ?>
                    <select id="cos-select" class="btn btn-outline">
                        <?php foreach ($cos_configs as $config): ?>
                            <option value="<?php echo $config['id']; ?>" <?php echo $config['id'] == $current_cos ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($config['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <?php if (empty($cos_configs)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-cloud-slash" style="font-size: 48px; margin-bottom: 16px;"></i>
                    <h3>未配置存储</h3>
                    <p>请先添加腾讯云COS配置</p>
                    <a href="settings.php" class="btn btn-primary">前往设置</a>
                </div>
            <?php else: ?>
                <!-- 路径导航 -->
                <?php if ($prefix || $search_keyword): ?>
                    <div class="breadcrumb">
                        <a href="?cos=<?php echo $current_cos; ?>&page=1">根目录</a>
                        <?php if ($search_keyword): ?>
                            <span class="separator">/</span>
                            <span>搜索: "<?php echo htmlspecialchars($search_keyword); ?>"</span>
                        <?php elseif ($prefix): ?>
                            <?php
                            $parts = explode('/', trim($prefix, '/'));
                            $current_path = '';
                            foreach ($parts as $part) {
                                if ($part) {
                                    $current_path .= $part . '/';
                                    echo '<span class="separator">/</span>';
                                    echo '<a href="?cos=' . $current_cos . '&prefix=' . urlencode($current_path) . '&page=1">' . htmlspecialchars($part) . '</a>';
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 批量上传区域 -->
                <div class="batch-upload-area" id="batch-upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="batch-upload-text">拖放文件到此处或点击选择文件</div>
                    <div class="batch-upload-hint">支持多选，单文件最大支持 5GB</div>
                    <input type="file" id="batch-file-input" multiple style="display: none;">
                </div>
                
                <!-- 批量上传文件列表 -->
                <div id="batch-file-list" class="batch-file-list" style="display: none;"></div>
                
                <!-- 批量上传控制栏 -->
                <div id="batch-controls" class="batch-controls" style="display: none;">
                    <button id="batch-upload-cancel" class="btn btn-outline">取消</button>
                    <button id="batch-upload-start" class="btn btn-primary">开始上传 (0)</button>
                </div>
                
                <!-- 操作栏 -->
                <div class="action-bar">
                    <button id="new-folder-btn" class="btn btn-primary">
                        <i class="fas fa-folder-plus"></i> 新建文件夹
                    </button>
                    
                    <!-- 搜索框 -->
                    <div class="search-box">
                        <input type="text" 
                               id="search-input" 
                               class="search-input" 
                               placeholder="搜索文件/文件夹..." 
                               value="<?php echo htmlspecialchars($search_keyword); ?>">
                        <button class="search-btn" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- 刷新按钮 -->
                    <button id="refresh-btn" class="refresh-btn" title="刷新">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                </div>
                
                <!-- 文件列表 -->
                <div id="file-list-container" class="file-list-container">
                    <div id="loading-indicator" style="text-align: center; padding: 10px; display: none;">
                        <p style="margin-top: 8px; color: #666;">感谢你的使用!</p>
                    </div>
                    <table class="file-list" id="file-table" style="display: none;">
                        <thead>
                            <tr>
                                <th class="file-name-cell">名称</th>
                                <th>大小</th>
                                <th>修改时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="file-list-body">
                            <!-- 通过JS动态加载 -->
                        </tbody>
                    </table>
                    <div id="empty-message" style="text-align: center; padding: 60px; color: #999; display: none;">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 16px;"></i>
                        <h3>当前文件夹为空</h3>
                        <?php if ($search_keyword): ?>
                            <p>没有找到匹配 "<?php echo htmlspecialchars($search_keyword); ?>" 的文件或文件夹</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 分页控件 -->
                <div id="pagination-container" class="pagination" style="display: none;">
                    <!-- 分页按钮将通过JS动态生成 -->
                </div>
                
                <!-- 上传进度 -->
                <div id="upload-progress" class="upload-area" style="display: none;">
                    <div>上传进度: <span id="progress-text">0%</span></div>
                    <div class="progress-bar">
                        <div id="progress-fill" class="progress-fill" style="width: 0%;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 模态框 -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modal-title">提示</span>
                <button id="modal-close" class="btn btn-outline" style="padding: 4px 8px;">×</button>
            </div>
            <div class="modal-body">
                <p id="modal-message"></p>
                <input type="text" id="modal-input" class="modal-input" placeholder="请输入..." style="display: none;">
            </div>
            <div class="modal-footer">
                <button id="modal-cancel" class="btn btn-outline">取消</button>
                <button id="modal-confirm" class="btn btn-primary">确认</button>
            </div>
        </div>
    </div>
    
    <script>
    const Modal = {
        show({ title = '提示', message = '', input = false, defaultValue = '' }) {
            return new Promise((resolve) => {
                const modal = document.getElementById('modal');
                const titleEl = document.getElementById('modal-title');
                const messageEl = document.getElementById('modal-message');
                const inputEl = document.getElementById('modal-input');
                const confirmBtn = document.getElementById('modal-confirm');
                const cancelBtn = document.getElementById('modal-cancel');
                const closeBtn = document.getElementById('modal-close');
                
                titleEl.textContent = title;
                messageEl.textContent = message;
                inputEl.style.display = input ? 'block' : 'none';
                inputEl.value = defaultValue;
                modal.style.display = 'flex';
                
                if (input) setTimeout(() => inputEl.focus(), 100);
                
                const cleanup = () => {
                    modal.style.display = 'none';
                    confirmBtn.onclick = null;
                    cancelBtn.onclick = null;
                    closeBtn.onclick = null;
                };
                
                confirmBtn.onclick = () => {
                    cleanup();
                    resolve(input ? inputEl.value.trim() : true);
                };
                
                cancelBtn.onclick = () => {
                    cleanup();
                    resolve(input ? null : false);
                };
                
                closeBtn.onclick = () => {
                    cleanup();
                    resolve(input ? null : false);
                };
            });
        },
        
        alert(message) {
            return this.show({ message });
        },
        
        confirm(message) {
            return this.show({ title: '确认', message });
        },
        
        prompt(message, defaultValue = '') {
            return this.show({ 
                title: '输入', 
                message, 
                input: true, 
                defaultValue 
            });
        }
    };
    
    const FileManager = {
        cosId: <?php echo $current_cos; ?>,
        prefix: '<?php echo $prefix; ?>',
        currentPage: <?php echo $page; ?>,
        perPage: <?php echo $per_page; ?>,
        searchKeyword: '<?php echo addslashes($search_keyword); ?>',
        totalItems: 0,
        totalPages: 1,
        isLoading: false,
        batchFiles: [], 
        showLoading(show) {
            this.isLoading = show;
            const loadingEl = document.getElementById('loading-indicator');
            const tableEl = document.getElementById('file-table');
            const emptyEl = document.getElementById('empty-message');
            const paginationEl = document.getElementById('pagination-container');
            
            if (show) {
                loadingEl.style.display = 'block';
                tableEl.style.display = 'none';
                emptyEl.style.display = 'none';
                paginationEl.style.display = 'none';
            }
        },
        updateUrl(params = {}) {
            const baseParams = {
                cos: this.cosId,
                prefix: this.prefix,
                page: this.currentPage,
                search: this.searchKeyword
            };
            const newParams = { ...baseParams, ...params };
            Object.keys(newParams).forEach(key => {
                if (newParams[key] === '' || newParams[key] === null || newParams[key] === undefined) {
                    delete newParams[key];
                }
            });
            const queryString = Object.keys(newParams)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(newParams[key])}`)
                .join('&');
            
            const newUrl = queryString ? `?${queryString}` : '';
            window.history.pushState(null, '', newUrl);
        },
        getFileIcon(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            const pdfExts = ['pdf'];
            const wordExts = ['doc', 'docx'];
            const excelExts = ['xls', 'xlsx', 'csv'];
            const pptExts = ['ppt', 'pptx'];
            const archiveExts = ['zip', 'rar', '7z', 'tar', 'gz'];
            const audioExts = ['mp3', 'wav', 'ogg', 'flac'];
            const videoExts = ['mp4', 'avi', 'mov', 'wmv', 'flv'];
            const codeExts = ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml'];
            
            if (imageExts.includes(ext)) return 'fa-file-image';
            if (pdfExts.includes(ext)) return 'fa-file-pdf';
            if (wordExts.includes(ext)) return 'fa-file-word';
            if (excelExts.includes(ext)) return 'fa-file-excel';
            if (pptExts.includes(ext)) return 'fa-file-powerpoint';
            if (archiveExts.includes(ext)) return 'fa-file-archive';
            if (audioExts.includes(ext)) return 'fa-file-audio';
            if (videoExts.includes(ext)) return 'fa-file-video';
            if (codeExts.includes(ext)) return 'fa-file-code';
            
            return 'fa-file';
        },
        async loadFiles() {
            if (this.isLoading) return;
            
            this.showLoading(true);
            
            try {
                let apiUrl = `api.php?action=list&cos_id=${this.cosId}&page=${this.currentPage}&per_page=${this.perPage}`;
                
                if (this.prefix) {
                    apiUrl += `&prefix=${encodeURIComponent(this.prefix)}`;
                }
                
                if (this.searchKeyword) {
                    apiUrl += `&keyword=${encodeURIComponent(this.searchKeyword)}`;
                }
                
                const response = await fetch(apiUrl);
                const result = await response.json();
                
                const tbody = document.getElementById('file-list-body');
                const tableEl = document.getElementById('file-table');
                const emptyEl = document.getElementById('empty-message');
                const paginationEl = document.getElementById('pagination-container');
                const loadingEl = document.getElementById('loading-indicator');
                
                if (!result.success) {
                    this.showLoading(false);
                    loadingEl.style.display = 'none';
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--danger);">加载失败: ${result.message}</td></tr>`;
                    tableEl.style.display = 'table';
                    return;
                }
                this.totalItems = result.data.total || 0;
                this.totalPages = result.data.total_pages || 1;
                if (!result.data.items || result.data.items.length === 0) {
                    this.showLoading(false);
                    tableEl.style.display = 'none';
                    paginationEl.style.display = 'none';
                    emptyEl.style.display = 'block';
                    return;
                }
                let html = '';
                result.data.items.forEach(item => {
                    const isFolder = item.type === 'folder';
                    const name = item.name;
                    const iconClass = isFolder ? 'fa-folder' : this.getFileIcon(name);
                    
                    html += `
                        <tr>
                            <td class="file-name-cell">
                                <div class="file-name">
                                    <i class="fas ${iconClass}"></i>
                                    <div class="file-name-text">
                                        ${isFolder ? 
                                            `<a href="?cos=${this.cosId}&prefix=${encodeURIComponent(item.key)}&page=1" title="${name}">${name}</a>` :
                                            `<span title="${name}">${name}</span>`
                                        }
                                    </div>
                                </div>
                            </td>
                            <td>${isFolder ? '-' : this.formatSize(item.size)}</td>
                            <td>${item.last_modified ? new Date(item.last_modified).toLocaleString() : '-'}</td>
                            <td>
                                <div class="file-actions">
                                    ${!isFolder ? `
                                        <button class="btn btn-outline" onclick="FileManager.download('${item.key}')" title="下载">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-outline" onclick="FileManager.share('${item.key}')" title="分享">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    ` : ''}
                                    <button class="btn btn-outline" onclick="FileManager.deleteItem('${item.key}', '${name}', ${isFolder})" title="删除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = html;
                this.showLoading(false);
                tableEl.style.display = 'table';
                emptyEl.style.display = 'none';
                this.renderPagination();
                
            } catch (error) {
                console.error('加载文件列表失败:', error);
                this.showLoading(false);
                Modal.alert('加载失败: ' + error.message);
            }
        },
        renderPagination() {
            const paginationEl = document.getElementById('pagination-container');
            
            if (this.totalPages <= 1) {
                paginationEl.style.display = 'none';
                return;
            }
            
            paginationEl.style.display = 'flex';
            let startPage = Math.max(1, this.currentPage - 2);
            let endPage = Math.min(this.totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            let html = '';
            html += `
                <button class="pagination-btn ${this.currentPage <= 1 ? 'disabled' : ''}" 
                        ${this.currentPage <= 1 ? 'disabled' : ''}
                        onclick="FileManager.goToPage(${this.currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
            if (startPage > 1) {
                html += `<button class="pagination-btn" onclick="FileManager.goToPage(1)">1</button>`;
                if (startPage > 2) {
                    html += `<span class="pagination-info">...</span>`;
                }
            }
            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                            onclick="FileManager.goToPage(${i})">
                        ${i}
                    </button>
                `;
            }
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    html += `<span class="pagination-info">...</span>`;
                }
                html += `<button class="pagination-btn" onclick="FileManager.goToPage(${this.totalPages})">${this.totalPages}</button>`;
            }
            html += `
                <button class="pagination-btn ${this.currentPage >= this.totalPages ? 'disabled' : ''}" 
                        ${this.currentPage >= this.totalPages ? 'disabled' : ''}
                        onclick="FileManager.goToPage(${this.currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            const startItem = Math.min(this.totalItems, (this.currentPage - 1) * this.perPage + 1);
            const endItem = Math.min(this.totalItems, this.currentPage * this.perPage);
            
            html += `
                <div class="pagination-info">
                    第 ${startItem}-${endItem} 条，共 ${this.totalItems} 条
                </div>
            `;
            
            paginationEl.innerHTML = html;
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages || page === this.currentPage) return;
            
            this.currentPage = page;
            this.updateUrl({ page: this.currentPage });
            this.loadFiles();
        },
        search() {
            const searchInput = document.getElementById('search-input');
            const keyword = searchInput.value.trim();
            
            if (keyword === this.searchKeyword) return;
            
            this.searchKeyword = keyword;
            this.currentPage = 1; 
            this.updateUrl({ 
                page: 1,
                search: keyword
            });
            this.loadFiles();
        },
        refresh() {
            this.currentPage = 1;
            this.updateUrl({ page: 1 });
            this.loadFiles();
        },
        clearSearch() {
            document.getElementById('search-input').value = '';
            this.searchKeyword = '';
            this.currentPage = 1;
            this.updateUrl({ 
                page: 1,
                search: ''
            });
            this.loadFiles();
        },
        async createFolder() {
            const folderName = await Modal.prompt('请输入文件夹名称:', '新建文件夹');
            if (!folderName) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_folder',
                        cos_id: this.cosId,
                        prefix: this.prefix,
                        folder_name: folderName
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    await Modal.alert('文件夹创建成功');
                    this.refresh(); 
                } else {
                    Modal.alert('创建失败: ' + result.message);
                }
            } catch (error) {
                Modal.alert('请求失败: ' + error.message);
            }
        },
        async deleteItem(key, name, isFolder) {
            const confirmed = await Modal.confirm(`确定要删除${isFolder ? '文件夹' : '文件'} "${name}" 吗？`);
            if (!confirmed) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        cos_id: this.cosId,
                        key: key
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    await Modal.alert('删除成功');
                    this.refresh(); 
                } else {
                    Modal.alert('删除失败: ' + result.message);
                }
            } catch (error) {
                Modal.alert('请求失败: ' + error.message);
            }
        },
        async download(key) {
            try {
                const response = await fetch(`api.php?action=get_download_url&cos_id=${this.cosId}&key=${encodeURIComponent(key)}`);
                const result = await response.json();
                
                if (result.success) {
                    const link = document.createElement('a');
                    link.href = result.data.url;
                    link.download = key.split('/').pop();
                    link.click();
                } else {
                    Modal.alert('获取下载链接失败: ' + result.message);
                }
            } catch (error) {
                Modal.alert('下载失败: ' + error.message);
            }
        },
        share(key) {
            const shareUrl = `${window.location.origin}/view.php?cos=${this.cosId}&key=${encodeURIComponent(key)}`;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    Modal.alert('分享链接已复制到剪贴板');
                }).catch(() => {
                    this.showShareDialog(shareUrl);
                });
            } else {
                this.showShareDialog(shareUrl);
            }
        },
        
        showShareDialog(url) {
            Modal.show({
                title: '分享链接',
                message: '请复制以下链接：',
                input: true,
                defaultValue: url
            }).then(() => {
                const input = document.getElementById('modal-input');
                input.select();
                document.execCommand('copy');
                Modal.alert('链接已复制到剪贴板');
            });
        },
        addBatchFiles(files) {
            const fileList = this.batchFiles;
            
            for (const file of files) {
                const exists = fileList.some(f => f.name === file.name && f.size === file.size);
                if (!exists) {
                    fileList.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        progress: 0,
                        status: 'pending', 
                        error: null
                    });
                }
            }
            
            this.renderBatchFileList();
            this.updateBatchControls();
            if (fileList.length > 0) {
                document.getElementById('batch-file-list').style.display = 'block';
                document.getElementById('batch-controls').style.display = 'flex';
            }
        },
        renderBatchFileList() {
            const container = document.getElementById('batch-file-list');
            const files = this.batchFiles;
            
            if (files.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            let html = '';
            files.forEach((file, index) => {
                const iconClass = this.getFileIcon(file.name);
                const statusText = {
                    pending: '等待上传',
                    uploading: '上传中...',
                    success: '上传成功',
                    error: '上传失败'
                }[file.status];
                
                const statusClass = file.status;
                
                html += `
                    <div class="batch-file-item" data-index="${index}">
                        <i class="fas ${iconClass} batch-file-icon"></i>
                        <div class="batch-file-info">
                            <div class="batch-file-name" title="${file.name}">${file.name}</div>
                            <div class="batch-file-size">${this.formatSize(file.size)}</div>
                            <div class="batch-file-progress">
                                <div class="batch-file-progress-bar" style="width: ${file.progress}%"></div>
                            </div>
                        </div>
                        <div class="batch-file-status ${statusClass}">
                            ${file.status === 'uploading' ? `${file.progress}%` : statusText}
                        </div>
                        <button class="btn btn-outline" onclick="FileManager.removeBatchFile(${index})" style="padding: 2px 6px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        },
        removeBatchFile(index) {
            this.batchFiles.splice(index, 1);
            this.renderBatchFileList();
            this.updateBatchControls();
            
            if (this.batchFiles.length === 0) {
                document.getElementById('batch-file-list').style.display = 'none';
                document.getElementById('batch-controls').style.display = 'none';
            }
        },
        clearBatchFiles() {
            this.batchFiles = [];
            this.renderBatchFileList();
            document.getElementById('batch-file-list').style.display = 'none';
            document.getElementById('batch-controls').style.display = 'none';
        },
        updateBatchControls() {
            const pendingCount = this.batchFiles.filter(f => f.status === 'pending').length;
            const btn = document.getElementById('batch-upload-start');
            btn.textContent = `开始上传 (${pendingCount})`;
            btn.disabled = pendingCount === 0;
        },
        async startBatchUpload() {
            const files = this.batchFiles.filter(f => f.status === 'pending');
            if (files.length === 0) return;
            
            for (const fileInfo of files) {
                if (fileInfo.status !== 'pending') continue;
                
                fileInfo.status = 'uploading';
                fileInfo.progress = 0;
                this.renderBatchFileList();
                
                const formData = new FormData();
                formData.append('action', 'upload');
                formData.append('cos_id', this.cosId);
                formData.append('prefix', this.prefix);
                formData.append('file', fileInfo.file);
                
                try {
                    const xhr = new XMLHttpRequest();
                    
                    await new Promise((resolve, reject) => {
                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) {
                                fileInfo.progress = Math.round((e.loaded / e.total) * 100);
                                this.renderBatchFileList();
                            }
                        };
                        
                        xhr.onload = () => {
                            try {
                                const result = JSON.parse(xhr.responseText);
                                if (result.success) {
                                    fileInfo.status = 'success';
                                    fileInfo.progress = 100;
                                    this.renderBatchFileList();
                                    resolve();
                                } else {
                                    fileInfo.status = 'error';
                                    fileInfo.error = result.message;
                                    this.renderBatchFileList();
                                    reject(new Error(result.message));
                                }
                            } catch (e) {
                                fileInfo.status = 'error';
                                fileInfo.error = '解析响应失败';
                                this.renderBatchFileList();
                                reject(e);
                            }
                        };
                        
                        xhr.onerror = () => {
                            fileInfo.status = 'error';
                            fileInfo.error = '网络错误';
                            this.renderBatchFileList();
                            reject(new Error('网络错误'));
                        };
                        
                        xhr.open('POST', 'api.php');
                        xhr.send(formData);
                    });
                } catch (error) {
                    console.error('上传失败:', error);
                }
            }
            
            this.updateBatchControls();
            const allDone = this.batchFiles.every(f => f.status === 'success' || f.status === 'error');
            if (allDone) {
                const successCount = this.batchFiles.filter(f => f.status === 'success').length;
                const errorCount = this.batchFiles.filter(f => f.status === 'error').length;
                
                let message = `上传完成！`;
                if (successCount > 0) message += ` 成功: ${successCount} 个文件`;
                if (errorCount > 0) message += ` 失败: ${errorCount} 个文件`;
                
                Modal.alert(message).then(() => {
                    if (successCount > 0) {
                        this.refresh();
                    }
                });
            }
        },
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };
    document.addEventListener('DOMContentLoaded', () => {
        FileManager.loadFiles();
        const batchUploadArea = document.getElementById('batch-upload-area');
        const batchFileInput = document.getElementById('batch-file-input');
        batchUploadArea.addEventListener('click', () => {
            batchFileInput.click();
        });
        batchUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            batchUploadArea.classList.add('dragover');
        });
        
        batchUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            batchUploadArea.classList.remove('dragover');
        });
        
        batchUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            batchUploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                FileManager.addBatchFiles(e.dataTransfer.files);
            }
        });
        batchFileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                FileManager.addBatchFiles(e.target.files);
                e.target.value = '';
            }
        });
        document.getElementById('batch-upload-start').addEventListener('click', () => {
            FileManager.startBatchUpload();
        });
        
        document.getElementById('batch-upload-cancel').addEventListener('click', () => {
            FileManager.clearBatchFiles();
        });
        document.getElementById('new-folder-btn').addEventListener('click', () => {
            FileManager.createFolder();
        });
        document.getElementById('cos-select').addEventListener('change', (e) => {
            window.location.href = `?cos=${e.target.value}&page=1`;
        });
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                FileManager.search();
            }
        });
        
        searchBtn.addEventListener('click', () => {
            FileManager.search();
        });
        
        document.getElementById('refresh-btn').addEventListener('click', () => {
            FileManager.refresh();
            const icon = document.querySelector('#refresh-btn i');
            icon.style.transition = 'transform 0.5s';
            icon.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                icon.style.transform = 'rotate(0deg)';
            }, 500);
        });
        window.addEventListener('popstate', () => {
            const urlParams = new URLSearchParams(window.location.search);
            FileManager.cosId = urlParams.get('cos') || FileManager.cosId;
            FileManager.prefix = urlParams.get('prefix') || '';
            FileManager.currentPage = parseInt(urlParams.get('page')) || 1;
            FileManager.searchKeyword = urlParams.get('search') || '';
            searchInput.value = FileManager.searchKeyword;
            FileManager.loadFiles();
        });
    });
    </script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>