<?php
// api/status.php v2.4 (Added Detailed Status)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = null;
$log_dir = null;
try {
    require_once __DIR__ . '/../includes/db_config.php';
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException("Database configuration did not initialize PDO object correctly.");
    }
    
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    if (!$docRoot) { throw new Exception("Could not determine DOCUMENT_ROOT."); }
    $log_dir = rtrim(str_replace('\\', '/', $docRoot), '/') . '/logs/';

} catch (Throwable $e) {
    error_log("Status API Config/Connection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

function formatBytesApiStatus($bytes, $precision = 2) {
    $bytes = (float)$bytes; if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing action parameter']);
    exit;
}

try {
    switch ($action) {
        case 'get_counts':
            // This action remains for the public page
            $stmtTotal = $pdo->query("SELECT COUNT(*) FROM device_status");
            $totalDevices = (int)$stmtTotal->fetchColumn();
            $response = ['success' => true, 'total' => $totalDevices];
            break;

        case 'get_detailed_status':
            // [NEW] This action is for the admin panel
            $onlineThresholdMinutes = 5;
            $onlineThresholdTimestamp = date('Y-m-d H:i:s', time() - ($onlineThresholdMinutes * 60));
            
            $stmt = $pdo->prepare("SELECT device_identifier, ip_address, app_version, last_seen FROM device_status ORDER BY last_seen DESC");
            $stmt->execute();
            $allDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $onlineDevices = [];
            $offlineDevices = [];

            foreach ($allDevices as $device) {
                if ($device['last_seen'] >= $onlineThresholdTimestamp) {
                    $onlineDevices[] = $device;
                } else {
                    $offlineDevices[] = $device;
                }
            }

            $stmtLogs = $pdo->query("SELECT ip_address, user_agent, login_time, status FROM login_logs ORDER BY login_time DESC LIMIT 20");
            $loginLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'online_devices' => $onlineDevices,
                'offline_devices' => $offlineDevices,
                'login_logs' => $loginLogs
            ];
            break;

        case 'get_files':
            $filesByType = [];
            $totalTraffic = 0;
            
            $stmtVersions = $pdo->query("SELECT id, version, filename, filesize, upload_timestamp, download_count, type, description, 'program' as item_type FROM versions ORDER BY type ASC, upload_timestamp DESC");
            $programFiles = $stmtVersions->fetchAll(PDO::FETCH_ASSOC);
            foreach ($programFiles as $file) {
                $type = $file['type'] ?? 'unknown_program';
                if (!isset($filesByType[$type])) { $filesByType[$type] = []; }
                $fileSize = (float)($file['filesize'] ?? 0);
                $downloadCount = (int)($file['download_count'] ?? 0);
                $fileTraffic = $fileSize * $downloadCount;
                $file['traffic_formatted'] = formatBytesApiStatus($fileTraffic);
                $file['filesize_formatted'] = formatBytesApiStatus($fileSize);
                $filesByType[$type][] = $file;
                $totalTraffic += $fileTraffic;
            }

            $stmtSupportTools = $pdo->query("SELECT id, filename, description, filesize, upload_timestamp, download_count, filetype, 'supporttool' as item_type FROM support_tools ORDER BY upload_timestamp DESC");
            $supportTools = $stmtSupportTools->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($supportTools)) {
                if (!isset($filesByType['supporttool'])) { $filesByType['supporttool'] = []; }
                foreach ($supportTools as $tool) {
                    $fileSize = (float)($tool['filesize'] ?? 0);
                    $downloadCount = (int)($tool['download_count'] ?? 0);
                    $fileTraffic = $fileSize * $downloadCount;
                    $tool['traffic_formatted'] = formatBytesApiStatus($fileTraffic);
                    $tool['filesize_formatted'] = formatBytesApiStatus($fileSize);
                    $filesByType['supporttool'][] = $tool;
                    $totalTraffic += $fileTraffic;
                }
            }
            
            $response = [
                'success' => true,
                'filesByType' => $filesByType,
                'totalTrafficFormatted' => formatBytesApiStatus($totalTraffic),
            ];
            break;

        case 'get_logs':
            $logs = [];
            if (is_dir($log_dir)) {
                $files = scandir($log_dir);
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                        $logs[] = $file;
                    }
                }
            }
            rsort($logs);
            $response = ['success' => true, 'logs' => $logs];
            break;

        case 'get_log_content':
            $logFile = $_GET['file'] ?? null;
            if (empty($logFile)) {
                throw new Exception("Missing 'file' parameter for get_log_content");
            }

            $safeFile = basename($logFile);
            if ($safeFile !== $logFile || pathinfo($safeFile, PATHINFO_EXTENSION) !== 'log') {
                 throw new Exception("Invalid log file name specified.");
            }

            $filePath = $log_dir . $safeFile;
            if (file_exists($filePath) && is_readable($filePath)) {
                $content = file_get_contents($filePath);
                $response = ['success' => true, 'content' => $content];
            } else {
                throw new Exception("Log file not found or is not readable.");
            }
            break;

        default:
            throw new Exception("Unknown action requested: " . htmlspecialchars($action));
    }

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    error_log("Status API Action Error (Action: " . htmlspecialchars($action) . "): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error processing request.']);
}

exit;
