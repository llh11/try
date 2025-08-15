<?php
// api/log.php (·���޸���)
// Endpoint for client application to send real-time log messages.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$log_dir = null;
try {
    // [FIXED] ʹ�� __DIR__ ������·���������� DOCUMENT_ROOT ���ɿ���
    // ����� /api �� /logs Ŀ¼λ��ͬһ��Ŀ¼�¡�
    // ����: /your_web_root/api/log.php �� /your_web_root/logs/
    $log_dir = dirname(__DIR__) . '/logs/';

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0755, true)) {
            // �������ʧ�ܣ������Ǹ�Ŀ¼Ȩ������
            throw new Exception("Log directory does not exist and could not be created at '{$log_dir}'. Check parent directory permissions.");
        }
    }
    if (!is_writable($log_dir)) {
        throw new Exception("Log directory '{$log_dir}' is not writable by the web server.");
    }
} catch (Exception $e) {
    error_log("Log API Configuration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error (Log Directory)']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON received.']);
        exit;
    }

    $deviceId = $input['device_id'] ?? null;
    $logMessage = $input['message'] ?? null;

    if (empty($deviceId) || !isset($logMessage)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or empty device_id or message']);
        exit;
    }

    // �����豸ID����ֹ·������������
    $safe_device_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $deviceId);
    if (empty($safe_device_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid device_id format']);
        exit;
    }

    $log_file_path = $log_dir . $safe_device_id . '.log';
    $formatted_message = $logMessage . PHP_EOL;

    // ʹ�� LOCK_EX ȷ��д�������ԭ����
    if (file_put_contents($log_file_path, $formatted_message, FILE_APPEND | LOCK_EX) !== false) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Log received']);
    } else {
        error_log("Log API: Failed to write to log file for device {$safe_device_id} at path {$log_file_path}");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: Could not write to log file']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is allowed.']);
}

exit;
?>
