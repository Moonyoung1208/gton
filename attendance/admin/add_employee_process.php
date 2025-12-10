<?php
session_start();
require_once '../config.php';

// 1. 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    header("Location: admin-login.html");
    exit;
}

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add-employee.html"); // POST 요청이 아니면 페이지 이동
    exit;
}

// 2. 데이터 유효성 검사 및 변수 정리
// 2.1. users 테이블용 데이터
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? ''); // 로그인 ID
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$department = trim($_POST['department'] ?? '');
$position = trim($_POST['position'] ?? '');
$join_date = trim($_POST['join_date'] ?? '');

// 2.2. settings 테이블용 데이터 (개인별 기준 시간)
$check_in_time = trim($_POST['standard_check_in_time'] ?? '');
$check_out_time = trim($_POST['standard_check_out'] ?? '');

// 필수 항목 검증 (간단하게)
if (empty($name) || empty($username) || empty($email) || empty($password) || empty($department) || empty($join_date)) {
    // 세션에 오류 메시지 저장 후 리디렉션
    $_SESSION['employee_message'] = "필수 항목을 모두 입력해주세요.";
    header("Location: add-employee.php");
    exit;
}

// 비밀번호 일치 확인
if ($password !== $password_confirm) {
    $_SESSION['employee_message'] = "비밀번호와 비밀번호 확인이 일치하지 않습니다.";
    header("Location: add-employee.php");
    exit;
}

// 비밀번호 해싱
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 3. 트랜잭션 시작 (users와 settings 두 테이블에 모두 성공적으로 저장해야 함)
$conn->begin_transaction();
$success = true;
$new_user_id = null; // 새로 생성될 user_id를 저장할 변수

try {
    // =======================================================
    // 3.1. users 테이블에 직원 정보 삽입
    // =======================================================
    $sql_user = "INSERT INTO users 
                 (username, name, email, password, phone, department, position, join_date, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param(
        "ssssssss",
        $username,
        $name,
        $email,
        $hashed_password,
        $phone,
        $department,
        $position,
        $join_date
    );

    if (!$stmt_user->execute()) {
        $success = false;
        throw new Exception("Users 테이블 삽입 실패: " . $stmt_user->error);
    }

    // 새로 삽입된 직원의 user_id를 가져옴
    $new_user_id = $conn->insert_id;
    $stmt_user->close();

    // =======================================================
    // 3.2. settings 테이블에 개인별 기준 시간 삽입
    // =======================================================

    // 시간 형식 (HH:MM:SS)으로 변환
    $standard_check_in_full = $check_in_time . ':00';
    $standard_check_out_full = $check_out_time . ':00';

    // settings 테이블의 late_threshold는 전사 설정을 따르거나 (여기서는 기본값 10분 설정)
    $late_threshold_default = 10;

    $sql_settings = "INSERT INTO settings 
                     (user_id, standard_check_in_time, standard_check_out, late_threshold, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, NOW(), NOW())";

    $stmt_settings = $conn->prepare($sql_settings);
    // 바인딩: i (user_id), s (check_in), s (check_out), i (late_threshold)
    $stmt_settings->bind_param(
        "issi",
        $new_user_id,
        $standard_check_in_full,
        $standard_check_out_full,
        $late_threshold_default
    );

    if (!$stmt_settings->execute()) {
        $success = false;
        throw new Exception("Settings 테이블 삽입 실패: " . $stmt_settings->error);
    }
    $stmt_settings->close();

    // 모든 쿼리가 성공하면 최종 커밋
    $conn->commit();
    $_SESSION['employee_message'] = "✅ 직원 (" . htmlspecialchars($name) . ") 계정이 성공적으로 생성되었습니다.";

} catch (Exception $e) {
    // 오류 발생 시 롤백 (users에만 저장되거나 settings에만 저장되는 것을 방지)
    $conn->rollback();
    $_SESSION['employee_message'] = "직원 계정 생성 중 오류 발생: " . $e->getMessage();
    error_log("Add Employee Error: " . $e->getMessage());
} finally {
    $conn->close();
}

// 최종 리디렉션
header("Location: admin-employees.html"); // 직원 목록 페이지로 이동
exit;
?>