<?php
// config.php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'gton'); 
define('DB_PASSWORD', 'gt1103!@');
define('DB_NAME', 'gton');

// MySQLi 연결 객체 생성 함수
function connectDB() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // 연결 확인
    if ($conn->connect_error) {
        die("데이터베이스 연결 실패: " . $conn->connect_error);
    }
    return $conn;
}
?>