<?php
session_start();

// ----------------------------------------------------
// 🌟 JavaScript Alert/Redirect 함수 정의 (재사용) 🌟
// ----------------------------------------------------
function redirect_with_alert($message, $location = 'mypage.html')
{
    // DB 연결이 있다면 안전하게 닫습니다.
    global $conn;
    if (isset($conn) && $conn !== null) {
        @$conn->close();
    }

    // HTML/JavaScript 출력 시작
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>";
    echo "<script>";
    echo "alert('" . addslashes($message) . "');";
    echo "window.location.href = '" . $location . "';";
    echo "</script>";
    echo "</body></html>";
    exit;
}

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

// 3. 데이터 유효성 검사 및 정리
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 모든 필드 입력 확인
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    redirect_with_alert("현재 비밀번호, 새 비밀번호, 확인 비밀번호를 모두 입력해 주세요.");
}

// 새 비밀번호와 확인 비밀번호 일치 확인
if ($new_password !== $confirm_password) {
    redirect_with_alert("새 비밀번호와 확인 비밀번호가 일치하지 않습니다.");
}

// 비밀번호 길이 확인 (예시: 최소 8자 이상)
if (strlen($new_password) < 8) {
    redirect_with_alert("새 비밀번호는 최소 8자 이상이어야 합니다.");
}

// 4. 데이터베이스 연결
$conn = connectDB();

if ($conn === null) {
    redirect_with_alert("데이터베이스 연결에 실패했습니다.");
}

// 5. 현재 비밀번호 확인을 위해 해시된 비밀번호를 DB에서 가져옴
$sql_fetch = "SELECT password FROM users WHERE user_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch === false) {
    redirect_with_alert("오류: 쿼리 준비 실패.");
}

$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows === 0) {
    // 사용자 ID를 찾을 수 없는 경우 (비정상적인 접근)
    redirect_with_alert("사용자 정보를 찾을 수 없습니다. 다시 로그인해 주세요.", 'index.html');
}

$user = $result_fetch->fetch_assoc();
$stored_hash = $user['password'];
$stmt_fetch->close();

// 6. 현재 비밀번호 일치 여부 확인
if (!password_verify($current_password, $stored_hash)) {
    // 현재 비밀번호가 틀린 경우
    redirect_with_alert("현재 비밀번호가 일치하지 않습니다.");
}

// 7. 새 비밀번호 해싱 및 DB 업데이트
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$sql_update = "UPDATE users SET password = ? WHERE user_id = ?";
$stmt_update = $conn->prepare($sql_update);

if ($stmt_update === false) {
    redirect_with_alert("오류: 비밀번호 업데이트 쿼리 준비 실패.");
}

// s = string (해시된 비밀번호), i = integer (user_id)
$stmt_update->bind_param("si", $new_password_hash, $user_id);

if ($stmt_update->execute()) {
    // 업데이트 성공
    $message = "비밀번호가 성공적으로 변경되었습니다.";
} else {
    // 업데이트 실패
    $message = "비밀번호 변경 중 오류가 발생했습니다. 다시 시도해 주세요.";
}

$stmt_update->close();

// 8. 최종 알림 및 리디렉션
redirect_with_alert($message);
?>