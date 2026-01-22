<?php
session_start();

// 1. 로그인 확인
if (!isset($_SESSION['user_id']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: ./index.html");
    exit;
}

require_once 'config.php';
$user_id = $_SESSION['user_id'];

// 2. POST 요청 확인
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mypage.html");
    exit;
}

// 3. 데이터 수집
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 4. 유효성 검사 (입력값 확인)
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: mypage.html?status=pw_empty");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: mypage.html?status=pw_mismatch");
    exit;
}

if (strlen($new_password) < 8) {
    header("Location: mypage.html?status=pw_short");
    exit;
}

$conn = connectDB();

// 5. 현재 비밀번호 검증
$sql_fetch = "SELECT password FROM users WHERE user_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows === 0) {
    header("Location: index.html");
    exit;
}

$user = $result_fetch->fetch_assoc();
if (!password_verify($current_password, $user['password'])) {
    // 현재 비밀번호가 틀림
    header("Location: mypage.html?status=pw_current_wrong");
    exit;
}
$stmt_fetch->close();

// 6. 새 비밀번호 업데이트
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$sql_update = "UPDATE users SET password = ? WHERE user_id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $new_password_hash, $user_id);

if ($stmt_update->execute()) {
    $status = "pw_success";
} else {
    $status = "pw_error";
}

$stmt_update->close();
$conn->close();

// 결과 페이지로 이동
header("Location: mypage.html?status=" . $status);
exit;