COS管理器
一个简洁高效的腾讯云COS文件管理工具

https://img.shields.io/badge/PHP-7.0+-777BB4?style=flat-square
https://img.shields.io/badge/MySQL-5.6+-4479A1?style=flat-square
https://img.shields.io/badge/License-MIT-blue?style=flat-square
https://img.shields.io/github/stars/yourusername/cos-manager?style=social

🚀 简介
COS管理器是一个基于PHP开发的Web应用，专门用于管理腾讯云COS对象存储中的文件。界面简洁美观，操作便捷，支持多用户管理，是你管理云存储文件的理想选择。

✨ 核心功能
📁 文件管理
文件上传：支持单文件/批量上传

文件预览：在线查看图片、视频等

文件分享：生成分享链接，设置有效期

文件操作：重命名、移动、删除、下载

📊 多存储管理
多个COS配置：支持配置多个存储桶

快速切换：在不同存储桶间无缝切换

权限隔离：用户只能访问自己的配置

🎨 用户体验
响应式设计：完美适配手机、平板、电脑

暗黑模式：支持亮色/暗色主题切换

拖拽上传：支持拖放文件批量上传

实时搜索：快速查找文件

🛠️ 技术栈
后端：PHP 7.0+、MySQL 5.6+、腾讯云COS SDK

前端：HTML5、CSS3、JavaScript、Font Awesome

安全：bcrypt加密、会话管理、输入过滤

架构：MVC模式、RESTful API设计

⚡ 快速安装
环境要求
PHP 7.0+

MySQL 5.6+

Composer

Web服务器（Apache/Nginx）

安装步骤
bash
# 1. 克隆项目
git clone https://github.com/yourusername/cos-manager.git
cd cos-manager

# 2. 安装依赖
composer install

# 3. 配置权限
chmod -R 755 uploads/
chmod 644 db_config.php

# 4. 访问安装向导
# 打开浏览器访问：http://your-domain.com/install.php
配置说明
安装时需要准备：

数据库信息（主机、名称、用户名、密码）

管理员账户信息

腾讯云COS配置（安装后添加）

📖 使用指南
1. 登录系统
使用安装时设置的管理员账户登录系统。

2. 添加COS配置
进入"设置"页面，添加你的腾讯云COS配置：

SecretId

SecretKey

存储桶名称

存储桶地域

3. 管理文件
上传文件：拖放或点击选择文件

创建文件夹：点击新建文件夹按钮

分享文件：点击分享图标生成链接

删除文件：点击删除图标移除文件


🔧 开发相关
项目结构
text
cos-manager/
├── assets/          # 静态资源
├── includes/        # 核心文件
├── vendor/          # 依赖包
├── *.php            # 主程序文件
└── README.md        # 说明文档

📞 联系与支持
官方网站
🌐 https://www.mcve.top

交流群组
💬 QQ群：318527519

📄 许可证
本项目采用 Apache License 2.0 许可证 - 查看 LICENSE 文件了解详情。

🙏 致谢
感谢以下开源项目的支持：

腾讯云COS SDK   Font Awesome   Composer

⭐ 支持项目
如果你觉得这个项目对你有帮助，请给个Star支持一下！

https://api.star-history.com/svg?repos=cmkj108/cos-manager&type=Date

注意：本项目为开源软件，作者不对使用本软件造成的任何损失负责。请合理使用，遵守相关法律法规。

📈 更新日志
v1.0.0 (2024-01-01)
✅ 初始版本发布
