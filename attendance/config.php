<?php
// .env 파일 로드
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

function connectDB()
{
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("데이터베이스 연결 실패: " . $conn->connect_error);
    }
    return $conn;
}

// 특정 날짜가 근무일인지 확인
function isWorkDay($date, $conn)
{
    // 1. 주말 체크 (토:6, 일:7)
    $dayOfWeek = date('N', strtotime($date));
    if ($dayOfWeek >= 6)
        return false;

    // 2. DB(수동+동기화된 구글 휴일) 체크
    $stmt = $conn->prepare("SELECT 1 FROM holidays WHERE holiday_date = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0)
        return false;

    return true;
}
?>