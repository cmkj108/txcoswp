# COS管理器

> 一个简洁高效的腾讯云COS文件管理工具

![PHP](https://img.shields.io/badge/PHP-7.0+-777BB4?style=flat-square)
![MySQL](https://img.shields.io/badge/MySQL-5.6+-4479A1?style=flat-square)
![腾讯云COS](https://img.shields.io/badge/腾讯云-COS-1E6FFF?style=flat-square)
![License](https://img.shields.io/badge/License-Apache%202.0-brightgreen.svg?style=flat-square)

## ✨ 核心功能

### 📁 文件管理
- **文件上传**：支持单文件/批量上传、拖拽上传
- **文件预览**：在线查看图片、视频、文档等
- **文件分享**：生成分享链接，可设置有效期
- **文件操作**：重命名、移动、删除、下载

### 📊 多存储管理
- **多个COS配置**：支持配置多个存储桶
- **快速切换**：在不同存储桶间无缝切换
- **权限隔离**：用户只能访问自己的配置

### 🎨 用户体验
- **响应式设计**：完美适配手机、平板、电脑
- **暗黑模式**：支持亮色/暗色主题切换
- **实时搜索**：快速查找文件
- **操作记录**：记录用户操作历史

### 🛠️ 技术栈

- **后端**：PHP 7.0+、MySQL 5.6+、腾讯云COS SDK
- **前端**：HTML5、CSS3、JavaScript、Font Awesome
- **安全**：bcrypt加密、会话管理、输入过滤

## ⚡ 快速安装

### 环境要求
- PHP 7.0+
- MySQL 5.6+
- Composer
- Web服务器（Apache/Nginx）

## 安装步骤

### 1. 克隆项目
git clone https://github.com/cmkj108/txcoswp.git
cd cos-manager

### 2. 安装依赖
composer install

### 3. 配置权限
chmod -R 755 uploads/
chmod 644 db_config.php

### 4. 访问安装向导
打开浏览器访问：http://你的域名/install.php

##📄 许可证
本项目采用 Apache License 2.0 许可证 - 查看 LICENSE 文件了解详情。

## 联系与支持
### 官方网站
### https://www.mcve.top

## 社区交流
### QQ群:318527519
