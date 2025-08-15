    <?php
    // --- 配置 ---
    // define('UPLOAD_DIR', '../uploads/'); // 不再需要，路径在 handler 中处理

    // --- 包含数据库连接 ---
    try {
        require_once __DIR__ . '/../includes/db_config.php'; // $pdo 在此定义
    } catch (Throwable $e) {
        $error_message = "数据库配置错误，无法加载页面: " . $e->getMessage();
        error_log($error_message);
        // 可以显示一个错误页面或简单的 die()
        die("发生严重配置错误，请联系管理员。");
    }


    // --- 辅助函数 ---
    function formatBytes($bytes, $precision = 2) { /* ... 不变 ... */ }

    // --- 从数据库获取文件列表 ---
    $files = [];
    $error_message = null;
    try {
        $stmt = $pdo->query("SELECT id, filename, filesize, upload_timestamp, download_count FROM versions ORDER BY upload_timestamp DESC");
        $files = $stmt->fetchAll();
    } catch (\PDOException $e) {
        $error_message = "错误：无法从数据库获取文件列表。 " . $e->getMessage();
        error_log($error_message);
        $files = [];
    }

    // --- 处理上传/删除结果消息 ---
    session_start();
    // ... (Session 处理不变) ...

    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
       <title>服务器管理界面 (Restructured)</title>
    </head>
    <body class="bg-gray-100 font-sans leading-normal tracking-normal">
        <div class="container mx-auto p-6">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">服务器管理界面 (Restructured)</h1>
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-6">
                 <h2 class="text-xl font-semibold mb-4 text-gray-700">上传新程序版本</h2>
                 <form action="upload_handler.php" method="post" enctype="multipart/form-data" id="upload-form">
                    <div id="connection-status" class="flex items-center">
                        <span class="mr-2 text-sm text-gray-600">API 连接:</span>
                        <span class="h-3 w-3 rounded-full bg-gray-400 inline-block mr-1" title="正在检测..."></span>
                        <span class="text-sm text-gray-500" id="server-ip">检测中...</span>
                    </div>
                 </form>
            </div>

             <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-6">
                 <h2 class="text-xl font-semibold mb-4 text-gray-700">已上传文件列表 (数据库)</h2>
                 <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal">
                        <tbody>
                            <?php if (empty($files)): ?>
                                <?php else: ?>
                                <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($file['filename']); ?></p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo formatBytes($file['filesize']); ?></p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo $file['upload_timestamp']; ?></p>
                                        </td>
                                         <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo $file['download_count']; ?></p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <a href="download_handler.php?file=<?php echo urlencode($file['filename']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">下载</a>
                                            <a href="delete_handler.php?file=<?php echo urlencode($file['filename']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('确定要删除文件 \'<?php echo htmlspecialchars($file['filename']); ?>\' 及其数据库记录吗？');">删除</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
             </div>
             </div>

        <script>
            // ... (拖拽逻辑不变) ...

            // *** 修改连接状态检测的目标 URL ***
            const statusIndicator = document.querySelector('#connection-status span:first-child');
            const serverIpDisplay = document.getElementById('server-ip');
            function checkConnection() {
                statusIndicator.className = 'h-3 w-3 rounded-full bg-yellow-400 inline-block mr-1';
                serverIpDisplay.textContent = '检测中...';
                serverIpDisplay.classList.add('text-gray-500');
                serverIpDisplay.classList.remove('text-green-600', 'text-red-600');

                // *** 指向新的 API ping 路径 ***
                fetch('../api/upload.php?action=ping', { method: 'GET', cache: 'no-cache' })
                    .then(response => { /* ... (处理逻辑不变) ... */ })
                    .catch(error => { /* ... (处理逻辑不变) ... */ });
            }
            checkConnection();
            setInterval(checkConnection, 30000);

            // ... (图表和实时监控占位符不变) ...
        </script>
    </body>
    </html>
    