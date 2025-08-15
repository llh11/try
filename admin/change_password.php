<?php
// admin/change_password.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $username = $_SESSION['admin_username'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['password_change_message'] = '所有字段均为必填项。';
        $_SESSION['password_change_error'] = true;
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['password_change_message'] = '新密码和确认密码不匹配。';
        $_SESSION['password_change_error'] = true;
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current_password, $admin['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET password_hash = :hash WHERE username = :username");
            if ($updateStmt->execute([':hash' => $new_hash, ':username' => $username])) {
                $_SESSION['password_change_message'] = '密码已成功更新。';
                $_SESSION['password_change_error'] = false;
            } else {
                $_SESSION['password_change_message'] = '数据库错误，无法更新密码。';
                $_SESSION['password_change_error'] = true;
            }
        } else {
            $_SESSION['password_change_message'] = '当前密码不正确。';
            $_SESSION['password_change_error'] = true;
        }
    }
}

header("Location: index.php");
exit;
?>
