<?php
// api/data_upload.php v1.0
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$deviceId = $_POST['device_id'] ?? null;
$dataType = $_POST['data_type'] ?? null; // e.g., 'screenshot', 'usb_backup', 'system_file'

if (empty($deviceId) || empty($dataType)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Device ID and data type are required.']);
    exit;
}

if (!isset($_FILES['uploaded_file']) || $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload error or no file provided.']);
    exit;
}

try {
    $safeDeviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $deviceId);
    $safeDataType = preg_replace('/[^a-zA-Z0-9_-]/', '', $dataType);

    if (empty($safeDeviceId) || empty($safeDataType)) {
        throw new Exception("Invalid device ID or data type format.");
    }
    
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    if (!$docRoot) { throw new Exception("Could not determine DOCUMENT_ROOT."); }
    
    $baseUploadDir = rtrim(str_replace('\\', '/', $docRoot), '/') . '/uploads/client_data/';
    $deviceDir = $baseUploadDir . $safeDeviceId . '/';
    $targetDir = $deviceDir . $safeDataType . '/';

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            throw new Exception("Failed to create target directory.");
        }
    }

    $originalFilename = basename($_FILES['uploaded_file']['name']);
    $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalFilename);
    $timestamp = date('Ymd-His');
    $destination = $targetDir . $timestamp . '_' . $safeFilename;

    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $destination)) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
    } else {
        throw new Exception("Failed to move uploaded file.");
    }

} catch (Exception $e) {
    error_log("Data Upload API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error during file processing.']);
}
?>
