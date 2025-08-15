<?php
// management/index.php v5.0 (Public Display Panel)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');

$db_error_message = null;
$pdo = null;
try {
    require_once __DIR__ . '/../includes/db_config.php';
} catch (Throwable $e) {
    $db_error_message = "数据库配置错误，无法加载页面。";
    error_log("DB Config Error in management/index.php: " . $e->getMessage());
}

function formatBytes($bytes, $precision = 2) {
    $bytes = (float)$bytes;
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新闻调度器 - 文件下载</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; }
        .card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        .table-container thead th { position: sticky; top: 0; z-index: 10; background-color: #f9fafb; }
        .table-container .category-header { 
            position: sticky; top: 60px; z-index: 9;
            background-color: #e5e7eb; font-weight: bold; text-align: center; padding: 1rem;
        }
    </style>
</head>
<body class="antialiased">
    <div class="container mx-auto p-4 md:p-8">
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">程序文件下载</h1>
                <p class="text-gray-500 mt-2">获取最新版本的客户端和服务工具。</p>
            </div>
            <div class="card p-4 mt-4 md:mt-0 text-center">
                <p class="text-sm text-gray-500">总设备数</p>
                <p class="text-3xl font-bold text-blue-600" id="total-devices">--</p>
            </div>
        </header>
        
        <?php if ($db_error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">
             <strong class="font-bold">数据库错误!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($db_error_message); ?></span>
        </div>
        <?php else: ?>
        <main class="card p-6">
            <div class="table-container">
                <table class="min-w-full leading-normal table-auto">
                    <thead>
                        <tr>
                            <th class="px-5 py-6 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">文件名</th>
                            <th class="px-5 py-6 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">版本/描述</th>
                            <th class="px-5 py-6 border-b-2 border-gray-200 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">文件大小</th>
                            <th class="px-5 py-6 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900" id="file-list-body">
                        <tr><td colspan="4" class="text-center p-5"><i class="fas fa-spinner fa-spin mr-2"></i>正在加载文件列表...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiFetch = async (url, options = {}) => {
            try {
                const response = await fetch(url, { cache: 'no-cache', ...options });
                if (!response.ok) throw new Error(`Network response was not ok (${response.status})`);
                return await response.json();
            } catch (error) {
                console.error(`API Fetch Error for ${url}:`, error);
                throw error;
            }
        };
        
        const updateTotalDevices = () => {
            apiFetch('../api/status.php?action=get_counts')
                .then(data => {
                    if (data?.success) {
                        document.getElementById('total-devices').textContent = data.total ?? 'N/A';
                    }
                });
        };

        const updateFileList = () => {
            const fileListBody = document.getElementById('file-list-body');
            const categoryNames = {
                'newsapp': '新闻程序 (newsapp)', 'configurator': '配置工具 (configurator)',
                'updater': '安装程序 (Updater)', 'supporttool': '支持工具',
            };

            apiFetch('../api/status.php?action=get_files')
                .then(data => {
                    if (data?.success) {
                        fileListBody.innerHTML = '';
                        let contentRendered = false;
                        const { filesByType = {} } = data;
                        const displayOrder = ['newsapp', 'configurator', 'updater', 'supporttool'];

                        displayOrder.forEach(typeKey => {
                            if (filesByType[typeKey]?.length > 0) {
                                contentRendered = true;
                                const categoryDisplayName = categoryNames[typeKey] || `未知类别 (${typeKey})`;
                                const headerRow = `<tr><td colspan="4" class="category-header text-gray-700 text-sm">${categoryDisplayName}</td></tr>`;
                                fileListBody.insertAdjacentHTML('beforeend', headerRow);

                                filesByType[typeKey].forEach(file => {
                                    const descriptionHtml = file.item_type === 'supporttool'
                                        ? `<p class="text-xs text-gray-600 whitespace-normal">${file.description || 'N/A'}</p>`
                                        : `<p class="font-semibold text-gray-800">${file.version || 'N/A'}</p><p class="text-xs text-gray-600 whitespace-normal">${file.description || '(无描述)'}</p>`;

                                    const fileRow = `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-3 border-b border-gray-200 bg-white text-sm">
                                                <p class="whitespace-no-wrap break-all font-medium" title="${file.filename || ''}">${file.filename || 'N/A'}</p>
                                            </td>
                                            <td class="px-5 py-3 border-b border-gray-200 bg-white text-sm max-w-xs">
                                                ${descriptionHtml}
                                            </td>
                                            <td class="px-5 py-3 border-b border-gray-200 bg-white text-sm text-right">
                                                <p class="whitespace-no-wrap">${file.filesize_formatted || 'N/A'}</p>
                                            </td>
                                            <td class="px-5 py-3 border-b border-gray-200 bg-white text-sm text-center whitespace-no-wrap">
                                                <a href="download_handler.php?file=${encodeURIComponent(file.filename)}&type=${file.item_type}" class="text-blue-600 hover:text-blue-900" title="下载"><i class="fas fa-download"></i> 下载</a>
                                            </td>
                                        </tr>`;
                                    fileListBody.insertAdjacentHTML('beforeend', fileRow);
                                });
                            }
                        });
                        if (!contentRendered) {
                            fileListBody.innerHTML = '<tr><td colspan="4" class="text-center p-5 text-gray-500">没有文件记录。</td></tr>';
                        }
                    }
                })
                .catch(err => {
                    fileListBody.innerHTML = `<tr><td colspan="4" class="text-center p-5 text-red-500">加载文件列表失败。</td></tr>`;
                });
        };
        
        updateTotalDevices();
        updateFileList();
        setInterval(updateTotalDevices, 30000);
        setInterval(updateFileList, 60000);
    });
    </script>
</body>
</html>
