<?php
// config.php

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

// define('DB_SERVER', $_ENV['DB_SERVER']);
// define('DB_USERNAME', $_ENV['DB_USERNAME']);
// define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
// define('DB_NAME', $_ENV['DB_NAME']);


// MySQLi 연결 객체 생성 함수
function connectDB()
{
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // 연결 확인
    if ($conn->connect_error) {
        die("데이터베이스 연결 실패: " . $conn->connect_error);
    }
    return $conn;
}
?>