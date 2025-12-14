<?php
// includes/footer.php
?>
        </div> <!-- 关闭 .container -->
    </main>
    
    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <p>COS管理器 © 创茂网络 <?php echo date('Y'); ?> - 基于腾讯云COS的文件管理系统</p>
                    <p class="text-muted">版本 1.0.0 | 更新时间: <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
                <div class="footer-links">
                    <a href="https://cloud.tencent.com/product/cos" target="_blank" class="footer-link">
                        <i class="fas fa-external-link-alt"></i> 腾讯云COS
                    </a>
                    <a href="www.mcve.top" class="footer-link">
                        <i class="fas fa-book"></i> 官网文档
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 20px 0;
            margin-top: 40px;
            color: var(--text-muted);
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-info p {
            margin: 4px 0;
        }
        
        .footer-links {
            display: flex;
            gap: 20px;
        }
        
        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
    
    <script>
        // 实时更新时间
        function updateTime() {
            const timeElement = document.querySelector('.footer-info .text-muted');
            if (timeElement) {
                const now = new Date();
                timeElement.textContent = `版本 1.0.0 | 更新时间: ${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            }
        }
        
        // 每60秒更新一次时间
        updateTime();
        setInterval(updateTime, 60000);
    </script>
</body>
</html>