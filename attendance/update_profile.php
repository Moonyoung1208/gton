<?php
session_start();

// ----------------------------------------------------
// 🌟 1. JavaScript Alert/Redirect 함수 정의 (모든 종료 경로에서 사용) 🌟
// ----------------------------------------------------
function redirect_with_alert($message, $location = 'mypage.html')
{
    // DB 연결이 있다면 안전하게 닫습니다.
    global $conn;
    if (isset($conn) && $conn !== null) {
        @$conn->close(); // @를 사용하여 닫는 중 오류 발생 방지
    }

    // HTML/JavaScript 출력 시작
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'></head><body>";
    echo "<script>";
    // 메시지에 따옴표 등이 있을 경우를 대비하여 addslashes를 사용합니다.
    echo "alert('" . addslashes($message) . "');";
    echo "window.location.href = '" . $location . "';";
    echo "</script>";
    echo "</body></html>";
    exit;
}

// 1. 로그인 확인
if (!isset($_SESSION['user_id']) || $_SESSION['logged_in'] !== TRUE) {
    // 로그인 페이지로의 이동은 alert가 불필요하므로 기존 헤더 리디렉션 유지
    header("Location: ./index.html");
    exit;
}

require_once 'config.php';

// 사용자 ID 가져오기
$user_id = $_SESSION['user_id'];

// 2. POST 요청 확인
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // POST 요청이 아니면 마이페이지로 돌려보냅니다.
    header("Location: mypage.html");
    exit;
}

// 3. 데이터 유효성 검사 및 정리
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$position = trim($_POST['position'] ?? '');

// 필수 항목 확인 (이름과 이메일은 필수라고 가정)
if (empty($name) || empty($email)) {
    // 🌟 JavaScript Alert 사용 🌟
    redirect_with_alert("이름과 이메일은 필수 항목입니다.");
}

// 이메일 형식 확인
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // 🌟 JavaScript Alert 사용 🌟
    redirect_with_alert("올바른 이메일 형식이 아닙니다.");
}

// 4. 데이터베이스 연결
$conn = connectDB();

if ($conn === null) {
    // 🌟 JavaScript Alert 사용 🌟
    redirect_with_alert("데이터베이스 연결에 실패했습니다.");
}

// 5. SQL 업데이트 쿼리 준비
$sql = "UPDATE users SET 
            name = ?, 
            email = ?, 
            department = ?, 
            position = ? 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // 쿼리 준비 실패
    // 🌟 JavaScript Alert 사용 🌟
    redirect_with_alert("오류: 쿼리 준비 실패.");
}

// 6. 변수 바인딩 및 실행
$stmt->bind_param("ssssi", $name, $email, $department, $position, $user_id);

$message = "";

if ($stmt->execute()) {
    // 업데이트 성공
    $message = "정보가 성공적으로 수정되었습니다.";
    $_SESSION['user_name'] = $name; // 세션 이름 업데이트는 유지
} else {
    // 업데이트 실패
    $message = "정보 수정 중 오류가 발생했습니다. 다시 시도해 주세요.";
}

$stmt->close();
// $conn->close()는 redirect_with_alert 함수 내부에서 처리됩니다.

// 7. 최종 알림 및 리디렉션
redirect_with_alert($message);
// exit; 는 함수 내부에 포함되어 있습니다.
?>