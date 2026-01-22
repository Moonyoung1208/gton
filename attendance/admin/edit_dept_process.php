<?php
session_start();
require_once '../config.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("권한이 없습니다.");
}

$conn = connectDB();
$id = $_GET['id'] ?? '';
$new_name = trim($_GET['new_name'] ?? '');

if ($id && $new_name) {
    $sql = "UPDATE departments SET dept_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_name, $id);

    if ($stmt->execute()) {
        header("Location: admin-departments.php?status=edit_success");
    } else {
        header("Location: admin-departments.php?status=error");
    }
} else {
    header("Location: admin-departments.php");
}
exit;