<?php
session_start();
require_once '../config.php';
$conn = connectDB();

$id = $_GET['id'] ?? '';
$new_name = trim($_GET['new_name'] ?? '');

if ($id && $new_name) {
    $sql = "UPDATE departments SET dept_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_name, $id);

    if ($stmt->execute()) {
        $_SESSION['status'] = 'success';
        $_SESSION['msg'] = '부서명이 수정되었습니다.';
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['msg'] = '수정 실패: ' . $conn->error;
    }
}
header("Location: add-department.html");