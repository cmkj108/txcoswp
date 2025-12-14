<?php
if (file_exists('installed.lock')) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];

    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_pass)) {
        $message = '<div class="alert alert-danger">请填写所有必填项</div>';
    } else {
        try {

            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS cos_configs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    region VARCHAR(50) NOT NULL,
                    bucket VARCHAR(100) NOT NULL,
                    secret_id VARCHAR(200) NOT NULL,
                    secret_key VARCHAR(200) NOT NULL,
                    is_primary TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");

            $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$admin_user, $hashed_pass]);

            $config_content = "<?php\nreturn [\n";
            $config_content .= "    'host' => '$db_host',\n";
            $config_content .= "    'name' => '$db_name',\n";
            $config_content .= "    'user' => '$db_user',\n";
            $config_content .= "    'pass' => '" . addslashes($db_pass) . "',\n";
            $config_content .= "];\n";

            if (file_put_contents('db_config.php', $config_content) === false) {
                throw new Exception('无法写入配置文件，请检查目录权限');
            }

            file_put_contents('installed.lock', time());

            $message = '<div class="alert alert-success">安装成功！正在跳转...</div>';
            echo '<meta http-equiv="refresh" content="2;url=index.php">';
        } catch (PDOException $e) {

            $error_code = $e->getCode();

            if ($error_code == 1045) {
                $message = '<div class="alert alert-danger">数据库连接失败：用户名或密码错误</div>';
            } elseif ($error_code == 1049) {
                $message = '<div class="alert alert-danger">数据库不存在，请先在MySQL中创建数据库 "' . htmlspecialchars($db_name) . '"</div>';
            } elseif ($error_code == 2002) {
                $message = '<div class="alert alert-danger">无法连接到数据库服务器，请检查主机地址</div>';
            } else {
                $message = '<div class="alert alert-danger">数据库连接失败：' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">安装失败：' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COS管理器 - 安装</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .install-container {
            width: 100%;
            max-width: 500px;
        }

        .install-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: #1890ff;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 28px;
            color: white;
        }

        h1 {
            font-size: 24px;
            color: #333;
            text-align: center;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1890ff;
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #40a9ff;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #f6ffed;
            border: 1px solid #b7eb8f;
            color: #52c41a;
        }

        .alert-danger {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            color: #ff4d4f;
        }

        .requirements {
            background: #fafafa;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }

        .requirements h3 {
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
        }

        .requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .requirements li {
            padding: 4px 0;
            display: flex;
            align-items: center;
        }

        .requirements li i {
            margin-right: 8px;
            color: #52c41a;
        }

        .note {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
            display: block;
        }

        @media (max-width: 480px) {
            .install-card {
                padding: 20px;
            }

            .logo {
                width: 50px;
                height: 50px;
            }

            .logo i {
                font-size: 24px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h1>COS管理器</h1>
                <p class="subtitle">请填写数据库信息</p>
            </div>

            <?php echo $message; ?>

            <div class="requirements">
                <h3>安装前须知：</h3>
                <ul>
                    <li><i class="fas fa-check"></i> 请确保MySQL数据库已创建</li>
                    <li><i class="fas fa-check"></i> 请确保数据库用户有创建表权限</li>
                    <li><i class="fas fa-check"></i> 请确保目录有写入权限</li>
                </ul>
            </div>

            <form method="POST" class="install-form">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required class="form-control" placeholder="例如：localhost">
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名</label>
                    <input type="text" id="db_name" name="db_name" value="cos_manager" required class="form-control" placeholder="已存在的数据库名称">
                </div>
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" value="root" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control" placeholder="留空表示无密码">
                </div>
                <div class="form-group">
                    <label for="admin_user">管理员用户名</label>
                    <input type="text" id="admin_user" name="admin_user" value="admin" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="admin_pass">管理员密码</label>
                    <input type="password" id="admin_pass" name="admin_pass" required class="form-control">
                    <span class="note">请妥善保管此密码，用于登录系统</span>
                </div>
                <button type="submit" class="btn">
                    开始安装
                </button>
            </form>
        </div>
    </div>

    <script>

        document.getElementById('admin_pass').addEventListener('input', function(e) {
            const password = e.target.value;
            const note = this.nextElementSibling;

            if (password.length >= 8) {
                this.style.borderColor = '#52c41a';
                if (note) note.style.color = '#52c41a';
            } else if (password.length >= 4) {
                this.style.borderColor = '#faad14';
                if (note) note.style.color = '#faad14';
            } else {
                this.style.borderColor = '#ff4d4f';
                if (note) note.style.color = '#ff4d4f';
            }
        });

        document.getElementById('db_host').addEventListener('blur', function() {
            const host = this.value;
            const name = document.getElementById('db_name').value;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;

        });
    </script>
</body>
</html>