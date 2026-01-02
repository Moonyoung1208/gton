<?php
session_start();
require_once '../config.php';
$conn = connectDB();

$id = $_GET['id'] ?? '';

if ($id) {
    // 1. 소속 직원 확인
    $check_user = "SELECT user_id FROM users WHERE dept_id = ? LIMIT 1";
    $stmt_check = $conn->prepare($check_user);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        // 소속 직원이 있으면 삭제 불가
        $_SESSION['status'] = 'error';
        $_SESSION['msg'] = '해당 부서에 소속된 직원이 있어 삭제할 수 없습니다. 직원의 부서를 먼저 변경해주세요.';
    } else {
        // 2. 삭제 실행
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['status'] = 'success';
            $_SESSION['msg'] = '부서가 삭제되었습니다.';
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['msg'] = '삭제 실패';
        }
    }
}
header("Location: add-department.html");