<?php
// api/command.php v1.0
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

// 客户端GET请求：检查命令
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $deviceId = $_GET['device_id'] ?? null;
    if (!$deviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Device ID is required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, command, parameter FROM device_commands WHERE device_identifier = :device_id AND status = 'pending' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([':device_id' => $deviceId]);
    $command = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($command) {
        echo json_encode(['success' => true, 'command' => $command]);
    } else {
        echo json_encode(['success' => true, 'command' => null]);
    }
    exit;
}

// 管理员或客户端POST请求：创建或更新命令
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    // 管理员创建命令
    if ($action === 'create_command') {
        session_start();
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $deviceId = $input['device_id'] ?? null;
        $command = $input['command'] ?? null;
        $parameter = $input['parameter'] ?? null;

        if (!$deviceId || !$command) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Device ID and command are required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO device_commands (device_identifier, command, parameter) VALUES (:device_id, :command, :parameter)");
        if ($stmt->execute([':device_id' => $deviceId, ':command' => $command, ':parameter' => $parameter])) {
            echo json_encode(['success' => true, 'message' => 'Command queued successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to queue command.']);
        }
        exit;
    }

    // 客户端更新命令状态
    if ($action === 'update_status') {
        $commandId = $input['command_id'] ?? null;
        $status = $input['status'] ?? null; // 'executed' or 'failed'

        if (!$commandId || !in_array($status, ['executed', 'failed'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Command ID and valid status are required.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE device_commands SET status = :status, executed_at = NOW() WHERE id = :id");
        if ($stmt->execute([':status' => $status, ':id' => $commandId])) {
            echo json_encode(['success' => true, 'message' => 'Command status updated.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update command status.']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>
