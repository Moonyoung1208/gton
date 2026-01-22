<?php
session_start();
require_once '../config.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("권한이 없습니다.");
}

$conn = connectDB();
$id = $_GET['id'] ?? '';

if ($id) {
    // 1. 소속 직원 확인 (직원이 있는 부서는 삭제 불가)
    $check_user = "SELECT user_id FROM users WHERE dept_id = ? LIMIT 1";
    $stmt_check = $conn->prepare($check_user);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        // 소속 직원이 있는 경우
        header("Location: admin-departments.php?status=delete_fail_has_users");
    } else {
        // 2. 삭제 실행
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: admin-departments.php?status=delete_success");
        } else {
            header("Location: admin-departments.php?status=error");
        }
    }
} else {
    header("Location: admin-departments.php");
}
exit;