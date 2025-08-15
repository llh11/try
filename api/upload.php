<?php
// api/upload.php (v2.0 - Added Description to API response)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = null;
try {
    require_once __DIR__ . '/../includes/db_config.php';
    require_once __DIR__ . '/../includes/upload_logic.php';
} catch (Throwable $e) {
    error_log("在 api/upload.php 中包含或配置路径失败: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '服务器内部配置错误']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    if ($action === 'ping') {
        echo json_encode(['success' => true, 'message' => 'pong']);
        exit;
    }
    if ($action === 'latest') {
        $typeFilter = $_GET['type'] ?? null;
        if (!$typeFilter) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => '缺少类别参数']); exit;
        }
        // [MODIFIED] SQL查询现在也会选择 `description` 字段。
        $sql = "SELECT version, filename, description FROM versions WHERE type = :type ORDER BY upload_timestamp DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':type' => $typeFilter]);
        $latestVersion = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($latestVersion) {
            http_response_code(200);
            // [MODIFIED] JSON响应现在包含描述。
            echo json_encode($latestVersion, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404); echo json_encode(['success' => false, 'message' => '未找到版本信息']);
        }
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的 GET action']);
    exit;
}

// POST请求处理保持不变，因为客户端API不处理上传。
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '此端点不支持POST上传。请使用管理面板。']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
exit;
?>
