<?php
session_start();
require_once '../config.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("권한이 없습니다.");
}

$conn = connectDB();

// 폼 데이터 가져오기
$current_pw = $_POST['current_pw'] ?? '';
$new_pw = $_POST['new_pw'] ?? '';
$admin_id = $_SESSION['admin_id'];

// 1. 현재 비밀번호 확인
$sql = "SELECT password_hash FROM admin_users WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if ($admin && password_verify($current_pw, $admin['password_hash'])) {
    // 2. 일치하면 새 비밀번호 암호화 후 업데이트
    $new_hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
    $update_sql = "UPDATE admin_users SET password_hash = ? WHERE admin_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_hashed_pw, $admin_id);

    if ($update_stmt->execute()) {
        header("Location: admin-settings.html?pw_status=success");
    } else {
        header("Location: admin-settings.html?pw_status=error");
    }
    $update_stmt->close();
} else {
    // 현재 비밀번호가 일치하지 않음
    header("Location: admin-settings.html?pw_status=wrong_pw");
}

$stmt->close();
$conn->close();
exit;