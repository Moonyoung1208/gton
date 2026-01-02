<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    exit;
}

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = trim($_POST['dept_name']);

    if (empty($dept_name)) {
        $_SESSION['status'] = 'error';
        $_SESSION['msg'] = '부서명을 입력해주세요.';
        header("Location: add-department.php");
        exit;
    }

    // 중복 체크
    $check_sql = "SELECT id FROM departments WHERE dept_name = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $dept_name);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['status'] = 'error';
        $_SESSION['msg'] = '이미 존재하는 부서명입니다.';
        header("Location: add-department.php");
        exit;
    }

    // 삽입
    $sql = "INSERT INTO departments (dept_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dept_name);

    if ($stmt->execute()) {
        $_SESSION['status'] = 'success';
        $_SESSION['msg'] = '부서가 추가되었습니다.';
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['msg'] = '저장 실패: ' . $conn->error;
    }

    header("Location: add-department.html");
    exit;
}