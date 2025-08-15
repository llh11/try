<?php
// admin/index.php v1.2 (Added Download Charts)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 登录逻辑
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    require_once __DIR__ . '/../includes/db_config.php';
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();
    
    $status = '失败';
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $is_logged_in = true;
        $status = '成功';
    } else {
        $login_error = '用户名或密码错误。';
    }

    // 记录登录尝试
    $logStmt = $pdo->prepare("INSERT INTO login_logs (ip_address, user_agent, status) VALUES (?, ?, ?)");
    $logStmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', $status]);
}

// 如果已登录，则加载所有管理功能所需的文件
if ($is_logged_in) {
    $db_error_message = null;
    $pdo = null;
    try {
        require_once __DIR__ . '/../includes/db_config.php';
    } catch (Throwable $e) {
        $db_error_message = "数据库配置错误，无法加载页面。";
        error_log("DB Config Error in admin/index.php: " . $e->getMessage());
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

    $feedback_message = ''; $is_error = false;
    if (isset($_SESSION['upload_message'])) {
        $feedback_message = $_SESSION['upload_message'];
        $is_error = isset($_SESSION['upload_error']) && $_SESSION['upload_error'];
        unset($_SESSION['upload_message']); unset($_SESSION['upload_error']);
    }
    if (isset($_SESSION['password_change_message'])) {
        $feedback_message = $_SESSION['password_change_message'];
        $is_error = isset($_SESSION['password_change_error']) && $_SESSION['password_change_error'];
        unset($_SESSION['password_change_message']); unset($_SESSION['password_change_error']);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_logged_in ? '管理员后台' : '管理员登录'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', '微软雅黑', sans-serif; }
        .card { background-color: #ffffff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
        .btn-primary { background-color: #3b82f6; color: white; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: background-color 0.2s ease; }
        .btn-primary:hover { background-color: #2563eb; }
        #drop-zone { border: 2px dashed #e5e7eb; border-radius: 0.5rem; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.2s ease; }
        #drop-zone.dragover { border-color: #3b82f6; background-color: #eff6ff; }
        #file-input { display: none; }
        .table-container { max-height: 400px; overflow-y: auto; }
        .table-container thead th { position: sticky; top: 0; z-index: 10; background-color: #f9fafb; }
        #log-content { 
            background-color: #111827; 
            color: #d1d5db; 
            font-family: 'Courier New', Courier, monospace; 
            height: 400px; 
            overflow-y: scroll; 
            border-radius: 0.5rem; 
            padding: 1rem; 
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="antialiased">
    <?php if (!$is_logged_in): ?>
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md">
            <form class="card p-8" method="post">
                <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">管理员登录</h1>
                <?php if ($login_error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($login_error); ?></span>
                </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">用户名</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" name="username" type="text" placeholder="admin" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">密码</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" placeholder="******************" required>
                </div>
                <div class="flex items-center justify-between">
                    <button class="btn-primary w-full" type="submit">登 录</button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="container mx-auto p-4 md:p-8">
        <header class="mb-8 flex flex-col md:flex-row justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">管理员后台</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">欢迎, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="text-sm text-red-500 hover:underline">退出登录</a>
            </div>
        </header>

        <!-- Feedback Message -->
        <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="transition-opacity duration-300 <?php echo $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700'; ?> px-4 py-3 rounded-lg relative mb-6" role="alert">
            <strong class="font-bold"><?php echo $is_error ? '错误' : '成功'; ?>!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($feedback_message); ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="document.getElementById('feedback-alert').style.display='none';"><i class="fa-solid fa-xmark cursor-pointer"></i></span>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Grid -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-1 flex flex-col gap-8">
                <!-- Upload Panel -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-3"><i class="fas fa-upload mr-2"></i>上传新文件</h2>
                    <form action="upload_handler.php" method="post" enctype="multipart/form-data" id="upload-form" class="space-y-4 pt-4">
                        <div>
                             <label class="block text-gray-700 text-sm font-bold mb-2" for="category">文件类别 <span class="text-red-500">*</span></label>
                             <select class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" id="category" name="category" required>
                                 <option value="" disabled selected>-- 请选择类别 --</option>
                                 <option value="newsapp">新闻程序 (newsapp)</option>
                                 <option value="configurator">配置工具 (configurator)</option>
                                 <option value="updater">安装程序 (Updater)</option>
                                 <option value="supporttool">支持工具</option>
                             </select>
                        </div>
                        <div id="program-version-group">
                             <label class="block text-gray-700 text-sm font-bold mb-2" for="version">版本号 <span class="text-red-500">*</span></label>
                             <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="version" name="version" type="text" placeholder="例如: 1.4.0" pattern="\d+\.\d+(\.\d+)?">
                        </div>
                        <div id="description-group">
                             <label class="block text-gray-700 text-sm font-bold mb-2" for="description">描述 <span class="text-red-500">*</span></label>
                             <textarea class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="description" name="description" placeholder="输入更新描述或工具说明" rows="3" required></textarea>
                        </div>
                        <div>
                             <label class="block text-gray-700 text-sm font-bold mb-2" for="program">选择文件或拖拽 <span class="text-red-500">*</span></label>
                             <div id="drop-zone">
                                 <i class="fas fa-cloud-arrow-up text-3xl text-gray-400"></i>
                                 <p class="mt-2">将文件拖拽到这里</p>
                                 <p class="text-xs text-gray-500 mt-1" id="file-type-hint">仅允许 .exe 文件</p>
                                 <input type="file" id="file-input" name="program_file" required>
                                 <p id="file-name" class="mt-2 text-sm text-blue-600 font-medium"></p>
                             </div>
                        </div>
                        <button class="btn-primary w-full" type="submit"><i class="fas fa-paper-plane mr-2"></i>上传文件</button>
                    </form>
                </div>

                <!-- Change Password Panel -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-3"><i class="fas fa-key mr-2"></i>修改密码</h2>
                    <form action="change_password.php" method="post" class="space-y-4 pt-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">当前密码</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="current_password" name="current_password" type="password" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">新密码</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="new_password" name="new_password" type="password" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" id="confirm_password" name="confirm_password" type="password" required>
                        </div>
                        <button class="btn-primary w-full" type="submit">确认修改</button>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-2 flex flex-col gap-8">
                <!-- Download Statistics Chart -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-3"><i class="fas fa-chart-bar mr-2"></i>下载统计</h2>
                    <div class="h-80">
                        <canvas id="downloadChart"></canvas>
                    </div>
                </div>

                <!-- File List -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-3"><i class="fas fa-list-check mr-2"></i>已上传文件列表</h2>
                    <div class="table-container">
                        <table class="min-w-full leading-normal table-auto">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">文件名 & 统计</th>
                                    <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">版本/描述</th>
                                    <th class="px-5 py-3 border-b-2 text-center text-xs font-semibold uppercase">操作</th>
                                </tr>
                            </thead>
                            <tbody id="file-list-body"></tbody>
                        </table>
                    </div>
                </div>
                <!-- Device Status -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-3"><i class="fas fa-server mr-2"></i>设备状态</h2>
                    <div id="device-status-tables"></div>
                </div>
                <!-- Log Viewer -->
                 <div class="card p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-3"><i class="fas fa-receipt mr-2"></i>实时日志</h2>
                    <div class="flex items-center space-x-4 mb-4 pt-4">
                        <label for="log-selector" class="text-sm font-medium">选择设备日志:</label>
                        <select id="log-selector" class="flex-grow shadow-sm border rounded py-2 px-3"></select>
                        <button id="refresh-logs-btn" class="text-gray-500 hover:text-blue-500 p-2 rounded-full" title="刷新日志列表"><i class="fas fa-sync-alt"></i></button>
                    </div>
                    <div id="log-content"></div>
                </div>
                <!-- Login History -->
                <div class="card p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-3"><i class="fas fa-history mr-2"></i>登录历史</h2>
                    <div class="table-container" id="login-history-container"></div>
                </div>
            </div>
        </main>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        let downloadChartInstance = null;

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

        const updateDownloadChart = () => {
            apiFetch('../api/status.php?action=get_files')
                .then(data => {
                    if (!data?.success) return;

                    const allFiles = Object.values(data.filesByType).flat();
                    if (allFiles.length === 0) return;

                    const labels = allFiles.map(f => f.filename);
                    const downloadCounts = allFiles.map(f => f.download_count || 0);
                    const trafficMB = allFiles.map(f => {
                        const trafficBytes = (f.filesize || 0) * (f.download_count || 0);
                        return (trafficBytes / (1024 * 1024)).toFixed(2);
                    });

                    const ctx = document.getElementById('downloadChart').getContext('2d');
                    
                    if (downloadChartInstance) {
                        downloadChartInstance.destroy();
                    }

                    downloadChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: '下载量 (次)',
                                data: downloadCounts,
                                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                yAxisID: 'y',
                            }, {
                                label: '使用流量 (MB)',
                                data: trafficMB,
                                type: 'line',
                                borderColor: 'rgba(139, 92, 246, 1)',
                                backgroundColor: 'rgba(139, 92, 246, 0.5)',
                                tension: 0.1,
                                yAxisID: 'y1',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: { display: true, text: '下载次数' },
                                    beginAtZero: true
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: { display: true, text: '流量 (MB)' },
                                    grid: { drawOnChartArea: false },
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        };

        const updateFileList = () => {
            const fileListBody = document.getElementById('file-list-body');
            apiFetch('../api/status.php?action=get_files')
                .then(data => {
                    if (!data?.success) return;
                    fileListBody.innerHTML = '';
                    const displayOrder = ['newsapp', 'configurator', 'updater', 'supporttool'];
                    displayOrder.forEach(typeKey => {
                        if (data.filesByType[typeKey]?.length > 0) {
                            data.filesByType[typeKey].forEach(file => {
                                const deleteConfirmMsg = `确定要删除文件 '${(file.filename || '').replace(/'/g, "\\'")}' 吗？`;
                                const descriptionHtml = file.item_type === 'supporttool'
                                    ? `<p class="text-xs text-gray-600">${file.description || 'N/A'}</p>`
                                    : `<p class="font-semibold text-gray-800">${file.version || 'N/A'}</p><p class="text-xs text-gray-600">${file.description || '(无描述)'}</p>`;
                                const fileRow = `
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 border-b text-sm">
                                            <p class="font-medium">${file.filename || 'N/A'}</p>
                                            <div class="text-xs text-gray-500 flex items-center space-x-3 mt-1">
                                                <span><i class="fas fa-download text-green-500"></i> ${file.download_count || '0'}</span>
                                                <span><i class="fas fa-chart-line text-purple-500"></i> ${file.traffic_formatted || '0 B'}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 border-b text-sm">${descriptionHtml}</td>
                                        <td class="px-5 py-3 border-b text-sm text-center">
                                            <a href="download_handler.php?file=${encodeURIComponent(file.filename)}&type=${file.item_type}" class="text-blue-600 hover:text-blue-900 mr-3" title="下载"><i class="fas fa-download"></i></a>
                                            <a href="delete_handler.php?file=${encodeURIComponent(file.filename)}&type=${file.item_type}" class="text-red-600 hover:text-red-900" title="删除" onclick="return confirm('${deleteConfirmMsg}');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>`;
                                fileListBody.insertAdjacentHTML('beforeend', fileRow);
                            });
                        }
                    });
                });
        };

        const updateDeviceStatus = () => {
            const container = document.getElementById('device-status-tables');
            apiFetch('../api/status.php?action=get_detailed_status')
                .then(data => {
                    if (!data?.success) return;
                    container.innerHTML = createDeviceTable('在线设备', data.online_devices, 'bg-green-100 text-green-800') + createDeviceTable('离线设备', data.offline_devices, 'bg-red-100 text-red-800');
                });
        };

        const createDeviceTable = (title, devices, headerClass) => {
            let rows = devices.map(d => `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 border-b text-xs">${d.device_identifier}</td>
                    <td class="px-3 py-2 border-b text-xs">${d.app_version}</td>
                    <td class="px-3 py-2 border-b text-xs">${d.ip_address}</td>
                    <td class="px-3 py-2 border-b text-xs">${d.last_seen}</td>
                </tr>`).join('');
            if (devices.length === 0) rows = '<tr><td colspan="4" class="text-center text-gray-500 py-3">无设备</td></tr>';
            return `
                <h3 class="font-semibold mt-4 mb-2 ${headerClass} px-3 py-1 rounded-t-lg">${title} (${devices.length})</h3>
                <div class="table-container">
                    <table class="min-w-full">
                        <thead><tr>
                            <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">ID</th>
                            <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">版本</th>
                            <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">IP</th>
                            <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">最后在线</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
        };
        
        const updateLoginHistory = () => {
            const container = document.getElementById('login-history-container');
             apiFetch('../api/status.php?action=get_detailed_status')
                .then(data => {
                    if (!data?.success || !data.login_logs) return;
                    let rows = data.login_logs.map(log => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 border-b text-xs">${log.login_time}</td>
                            <td class="px-3 py-2 border-b text-xs">${log.ip_address}</td>
                            <td class="px-3 py-2 border-b text-xs ${log.status === '成功' ? 'text-green-600' : 'text-red-600'}">${log.status}</td>
                        </tr>`).join('');
                    container.innerHTML = `
                        <table class="min-w-full">
                            <thead><tr>
                                <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">时间</th>
                                <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">IP地址</th>
                                <th class="px-3 py-2 border-b text-left text-xs font-semibold uppercase">状态</th>
                            </tr></thead>
                            <tbody>${rows}</tbody>
                        </table>`;
                });
        };

        const updateLogList = async () => {
            const logSelector = document.getElementById('log-selector');
            try {
                const data = await apiFetch('../api/status.php?action=get_logs');
                logSelector.innerHTML = '';
                if (data?.success && data.logs?.length > 0) {
                    data.logs.forEach(logFile => {
                        const option = document.createElement('option');
                        option.value = logFile;
                        option.textContent = logFile;
                        logSelector.appendChild(option);
                    });
                    updateLogContent();
                } else {
                    logSelector.innerHTML = '<option>暂无日志文件</option>';
                    document.getElementById('log-content').innerHTML = '<span class="text-gray-500">请选择一个日志文件查看内容。</span>';
                }
            } catch (error) {
                logSelector.innerHTML = '<option>加载日志列表失败</option>';
            }
        };

        const updateLogContent = async () => {
            const logSelector = document.getElementById('log-selector');
            const logContent = document.getElementById('log-content');
            const selectedLog = logSelector.value;
            if (!selectedLog || selectedLog.includes('失败') || selectedLog.includes('暂无')) {
                logContent.innerHTML = '<span class="text-gray-500">请选择一个有效的日志文件。</span>';
                return;
            }
            logContent.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-gray-400"></i>正在加载日志内容...';
            try {
                const data = await apiFetch(`../api/status.php?action=get_log_content&file=${encodeURIComponent(selectedLog)}`);
                if (data?.success) {
                    logContent.textContent = data.content || '(日志文件为空)';
                    logContent.scrollTop = logContent.scrollHeight;
                } else {
                    logContent.textContent = `加载日志失败: ${data.message || '未知错误'}`;
                }
            } catch (error) {
                logContent.textContent = `加载日志时发生网络错误: ${error.message}`;
            }
        };

        // Form logic for upload
        const categorySelect = document.getElementById('category');
        const versionGroup = document.getElementById('program-version-group');
        const versionInput = document.getElementById('version');
        const fileInput = document.getElementById('file-input');
        const fileTypeHint = document.getElementById('file-type-hint');
        const dropZone = document.getElementById('drop-zone');
        const fileNameDisplay = document.getElementById('file-name');

        categorySelect?.addEventListener('change', function() {
            const isSupportTool = this.value === 'supporttool';
            versionGroup.style.display = isSupportTool ? 'none' : 'block';
            versionInput.required = !isSupportTool;
            fileInput.accept = isSupportTool ? '' : '.exe,application/octet-stream,application/x-msdownload,application/vnd.microsoft.portable-executable';
            fileTypeHint.textContent = isSupportTool ? '允许任何文件类型' : '仅允许 .exe 文件';
        });
        categorySelect?.dispatchEvent(new Event('change'));

        dropZone?.addEventListener('click', () => fileInput.click());
        fileInput?.addEventListener('change', () => { fileNameDisplay.textContent = fileInput.files.length > 0 ? `已选择: ${fileInput.files[0].name}` : ''; });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evName => {
            dropZone?.addEventListener(evName, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });
        ['dragenter', 'dragover'].forEach(evName => dropZone?.addEventListener(evName, () => dropZone.classList.add('dragover'), false));
        ['dragleave', 'drop'].forEach(evName => dropZone?.addEventListener(evName, () => dropZone.classList.remove('dragover'), false));
        dropZone?.addEventListener('drop', e => {
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileNameDisplay.textContent = `已选择: ${fileInput.files[0].name}`;
            }
        }, false);
        
        document.getElementById('log-selector')?.addEventListener('change', updateLogContent);
        document.getElementById('refresh-logs-btn')?.addEventListener('click', updateLogList);
        
        // Initial data load and interval setup
        updateFileList();
        updateDeviceStatus();
        updateLoginHistory();
        updateLogList();
        updateDownloadChart();
        setInterval(updateFileList, 60000);
        setInterval(updateDeviceStatus, 15000);
        setInterval(updateLoginHistory, 60000);
        setInterval(updateDownloadChart, 60000);
    });
    </script>
    <?php endif; ?>
</body>
</html>
