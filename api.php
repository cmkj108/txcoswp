<?php

require_once __DIR__ . '/vendor/autoload.php';
session_start();
header('Content-Type: application/json');

function handleException($e) {
    error_log("API Error [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => '操作失败: ' . $e->getMessage()
    ]);
    exit;
}

set_exception_handler('handleException');

if (!isset($_SESSION['user_id'])) {
    throw new Exception('未登录或会话已过期');
}

require_once __DIR__ . '/includes/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new Exception('数据库连接失败');
}

$user_id = $_SESSION['user_id'];

$input = [];
$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
} else {

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON解析失败');
        }
        $action = $input['action'] ?? '';
    } else {
        $input = $_POST;
        $action = $_POST['action'] ?? '';
    }
}

function getCosConfigAndClient($cos_id, $user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM cos_configs WHERE id = ? AND user_id = ?");
    $stmt->execute([$cos_id, $user_id]);
    $cos_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cos_info) {
        throw new Exception('COS配置不存在或无访问权限');
    }

    $required = ['region', 'secret_id', 'secret_key', 'bucket'];
    foreach ($required as $field) {
        if (empty($cos_info[$field])) {
            throw new Exception('COS配置不完整: 缺少 ' . $field);
        }
    }

    $cosClient = new Qcloud\Cos\Client([
        'region' => $cos_info['region'],
        'credentials' => [
            'secretId' => $cos_info['secret_id'],
            'secretKey' => $cos_info['secret_key']
        ]
    ]);

    return ['client' => $cosClient, 'config' => $cos_info];
}

try {
    switch ($action) {

case 'get_preview_url':
    if (!isset($_GET['cos_id']) || !isset($_GET['key'])) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }

    try {
        $config = getCosConfig($pdo, $_GET['cos_id'], $_SESSION['user_id']);
        $cosClient = createCosClient($config);

        $command = $cosClient->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key' => $_GET['key']
        ]);

        $request = $cosClient->createPresignedRequest($command, '+1 hour');
        $url = (string)$request->getUri();

        $ext = strtolower(pathinfo($_GET['key'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
            $url .= '&imageMogr2/thumbnail/!500x500r/gravity/center/crop/500x500';
        }

        echo json_encode([
            'success' => true,
            'data' => ['url' => $url]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

case 'get_share_url':
    if (!isset($_GET['cos_id']) || !isset($_GET['key'])) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }

    try {
        $config = getCosConfig($pdo, $_GET['cos_id'], $_SESSION['user_id']);
        $cosClient = createCosClient($config);

        $command = $cosClient->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key' => $_GET['key']
        ]);

        $request = $cosClient->createPresignedRequest($command, '+7 days');
        $url = (string)$request->getUri();

        echo json_encode([
            'success' => true,
            'data' => ['url' => $url]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

case 'list':
    $cos_id = intval($_GET['cos_id']);
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = min(100, intval($_GET['per_page'] ?? 50));
    $prefix = $_GET['prefix'] ?? '';
    $keyword = $_GET['keyword'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM cos_configs WHERE id = ? AND user_id = ?");
    $stmt->execute([$cos_id, $_SESSION['user_id']]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        echo json_encode(['success' => false, 'message' => '配置不存在']);
        exit;
    }

    try {

        $cosClient = new Qcloud\Cos\Client([
            'region' => $config['region'],
            'credentials' => [
                'secretId' => $config['secret_id'],
                'secretKey' => $config['secret_key']
            ]
        ]);

        $result = $cosClient->listObjects([
            'Bucket' => $config['bucket'],
            'Prefix' => $prefix,
            'Delimiter' => '/',
            'MaxKeys' => $per_page,
            'Marker' => ''
        ]);

        $items = [];

        if (isset($result['CommonPrefixes'])) {
            foreach ($result['CommonPrefixes'] as $commonPrefix) {
                $folderKey = $commonPrefix['Prefix'];
                $folderName = basename(rtrim($folderKey, '/'));

                if ($keyword && stripos($folderName, $keyword) === false) {
                    continue;
                }

                $items[] = [
                    'key' => $folderKey,
                    'name' => $folderName,
                    'type' => 'folder',
                    'size' => 0,
                    'last_modified' => null
                ];
            }
        }

        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $content) {

                if ($content['Key'] === $prefix) continue;

                $fileName = basename($content['Key']);

                if ($keyword && stripos($fileName, $keyword) === false) {
                    continue;
                }

                $items[] = [
                    'key' => $content['Key'],
                    'name' => $fileName,
                    'type' => 'file',
                    'size' => $content['Size'] ?? 0,
                    'last_modified' => $content['LastModified'] ?? null
                ];
            }
        }

        $total = count($items);
        $offset = ($page - 1) * $per_page;
        $paged_items = array_slice($items, $offset, $per_page);

        echo json_encode([
            'success' => true,
            'data' => [
                'items' => $paged_items,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

        case 'upload':

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('文件上传失败或没有选择文件');
            }

            $cos_id = (int)$_POST['cos_id'];
            $prefix = $_POST['prefix'] ?? '';

            $cos_data = getCosConfigAndClient($cos_id, $user_id, $pdo);
            $cosClient = $cos_data['client'];
            $cos_info = $cos_data['config'];

            $filename = preg_replace('/[\/:*?"<>|]/', '_', basename($_FILES['file']['name']));
            if (empty($filename)) {
                throw new Exception('文件名无效');
            }

            $key = $prefix . $filename;

            $result = $cosClient->putObject([
                'Bucket' => $cos_info['bucket'],
                'Key' => $key,
                'Body' => fopen($_FILES['file']['tmp_name'], 'rb'),
                'ACL' => 'private'
            ]);

            echo json_encode([
                'success' => true,
                'message' => '上传成功',
                'data' => [
                    'key' => $key,
                    'filename' => $filename,
                    'size' => $_FILES['file']['size']
                ]
            ]);
            break;

        case 'create_folder':

            $cos_id = (int)($input['cos_id'] ?? 0);
            $folder_name = trim($input['folder_name'] ?? '');
            $prefix = $input['prefix'] ?? '';

            if (empty($folder_name)) {
                throw new Exception('文件夹名称不能为空');
            }

            $folder_name = preg_replace('/[\/:*?"<>|]/', '_', $folder_name);

            $cos_data = getCosConfigAndClient($cos_id, $user_id, $pdo);
            $cosClient = $cos_data['client'];
            $cos_info = $cos_data['config'];

            $key = $prefix . $folder_name . '/';

            $cosClient->putObject([
                'Bucket' => $cos_info['bucket'],
                'Key' => $key,
                'Body' => ''
            ]);

            echo json_encode([
                'success' => true,
                'message' => '文件夹创建成功',
                'data' => ['key' => $key]
            ]);
            break;

        case 'delete':

            $cos_id = (int)($input['cos_id'] ?? 0);
            $key = $input['key'] ?? '';

            if (empty($key)) {
                throw new Exception('未指定要删除的对象');
            }

            $cos_data = getCosConfigAndClient($cos_id, $user_id, $pdo);
            $cosClient = $cos_data['client'];
            $cos_info = $cos_data['config'];

            if (substr($key, -1) === '/') {

                $result = $cosClient->listObjects([
                    'Bucket' => $cos_info['bucket'],
                    'Prefix' => $key
                ]);

                $deleteItems = [];
                if (!empty($result['Contents'])) {
                    foreach ($result['Contents'] as $item) {

                        if ($item['Key'] !== $key) {
                            $deleteItems[] = ['Key' => $item['Key']];
                        }
                    }
                }

                if (!empty($deleteItems)) {
                    $cosClient->deleteObjects([
                        'Bucket' => $cos_info['bucket'],
                        'Delete' => [
                            'Objects' => $deleteItems,
                            'Quiet' => true
                        ]
                    ]);
                } else {

                    try {
                        $cosClient->deleteObject([
                            'Bucket' => $cos_info['bucket'],
                            'Key' => $key
                        ]);
                    } catch (Exception $e) {

                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => '文件夹删除成功',
                    'data' => ['deleted_count' => count($deleteItems)]
                ]);
            } else {

                $cosClient->deleteObject([
                    'Bucket' => $cos_info['bucket'],
                    'Key' => $key
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => '文件删除成功'
                ]);
            }
            break;

        case 'get_download_url':

            $cos_id = (int)($_GET['cos_id'] ?? 0);
            $key = $_GET['key'] ?? '';

            if (empty($key)) {
                throw new Exception('未指定文件');
            }

            $cos_data = getCosConfigAndClient($cos_id, $user_id, $pdo);
            $cosClient = $cos_data['client'];
            $cos_info = $cos_data['config'];

            $url = $cosClient->getObjectUrl($cos_info['bucket'], $key, '+2 hours');

            echo json_encode([
                'success' => true,
                'data' => ['url' => $url]
            ]);
            break;

        default:
            throw new Exception('未知的操作类型: ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    throw $e;
}
?>