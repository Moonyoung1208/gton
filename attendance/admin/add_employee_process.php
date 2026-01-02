<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    header("Location: admin-login.html");
    exit;
}

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add-employee.html");
    exit;
}

// 폼 데이터 정리
$form_data = $_POST; // 입력값 보존을 위해 전체 데이터를 복사
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$department = trim($_POST['department'] ?? '');
$position = trim($_POST['position'] ?? '');
$join_date = trim($_POST['join_date'] ?? '');

$dept_id = $_POST['dept_id'] ?? '';

// 
if (empty($dept_id)) {
    $_SESSION['status'] = "error";
    $_SESSION['msg'] = "부서를 선택해주세요.";
    header("Location: add-employee.html");
    exit;
}


// 필수 항목 검증
if (empty($name) || empty($username) || empty($email) || empty($password) || empty($department) || empty($join_date)) {
    $_SESSION['status'] = "error";
    $_SESSION['msg'] = "필수 항목을 모두 입력해주세요.";
    $_SESSION['form_data'] = $form_data; // 입력 데이터 보존
    header("Location: add-employee.html");
    exit;
}

// 비밀번호 일치 확인
if ($password !== $password_confirm) {
    $_SESSION['status'] = "error";
    $_SESSION['msg'] = "비밀번호가 서로 일치하지 않습니다.";
    $_SESSION['form_data'] = $form_data; // 입력 데이터 보존
    header("Location: add-employee.html");
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql_user = "INSERT INTO users 
                 (username, name, email, password, phone, dept_id, position, join_date, created_at, updated_at, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'active')";

    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param(
        "sssssis s", // s: string, i: integer
        $username,
        $name,
        $email,
        $hashed_password,
        $phone,
        $dept_id,
        $position,
        $join_date
    );

    if (!$stmt_user->execute()) {
        throw new Exception("이미 존재하는 아이디이거나 데이터 저장에 실패했습니다.");
    }

    $stmt_user->close();

    // 성공 시
    $_SESSION['status'] = "success";
    $_SESSION['msg'] = "계정이 성공적으로 생성되었습니다.";
    // 성공 시에는 입력 데이터를 삭제합니다.
    unset($_SESSION['form_data']);
    header("Location: add-employee.html");

} catch (Exception $e) {
    $_SESSION['status'] = "error";
    $_SESSION['msg'] = $e->getMessage();
    $_SESSION['form_data'] = $form_data; // 오류 시 입력 데이터 보존
    header("Location: add-employee.html");
} finally {
    $conn->close();
}
exit;