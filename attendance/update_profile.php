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

// 3. 데이터 가져오기 및 정리
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$dept_id = $_POST['dept_id'] ?? null;
$position = trim($_POST['position'] ?? '');

// 4. 필수 값 검증
if (empty($name) || empty($email) || empty($dept_id)) {
    // 필수값이 누락된 경우 에러 파라미터와 함께 이동
    header("Location: mypage.html?status=error&msg=missing_fields");
    exit;
}

$conn = connectDB();

// 5. SQL 업데이트 쿼리 준비
$sql = "UPDATE users SET 
            name = ?, 
            email = ?, 
            dept_id = ?, 
            position = ? 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header("Location: mypage.html?status=error");
    exit;
}

// 6. 변수 바인딩 및 실행
$stmt->bind_param("ssisi", $name, $email, $dept_id, $position, $user_id);

// 단 한 번만 실행하고 결과에 따라 리디렉션
if ($stmt->execute()) {
    // 성공 시 세션 정보 갱신 및 성공 파라미터 전달
    $_SESSION['user_name'] = $name;
    $stmt->close();
    $conn->close();
    header("Location: mypage.html?status=success");
} else {
    // 실패 시 에러 파라미터 전달
    $stmt->close();
    $conn->close();
    header("Location: mypage.html?status=error");
}
exit;
?>