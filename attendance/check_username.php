<?php
// config.php 파일 포함
require_once 'config.php';

// JSON 응답을 위한 헤더 설정
header('Content-Type: application/json');

// POST 방식으로 데이터가 전송되었는지 확인
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['username'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 접근입니다.']);
    exit;
}

$conn = connectDB();

// 1. 입력된 아이디 가져오기
$username = $conn->real_escape_string(trim($_POST['username']));

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => '아이디를 입력해주세요.']);
    $conn->close();
    exit;
}

// 2. 데이터베이스에서 중복 아이디 조회
// SQL 인젝션 방지를 위해 prepared statement 사용
$sql = "SELECT user_id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result(); // 결과 저장

// 3. 결과 확인 및 응답
if ($stmt->num_rows > 0) {
    // 중복된 아이디가 존재함
    echo json_encode(['success' => false, 'message' => '이미 사용 중인 아이디입니다.']);
} else {
    // 사용 가능한 아이디임
    echo json_encode(['success' => true, 'message' => '사용 가능한 아이디입니다.']);
}

$stmt->close();
$conn->close();
?>