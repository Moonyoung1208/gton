<?php
session_start();
require_once '../config.php'; // 경로가 정확한지 다시 확인하세요.

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("접근 권한이 없습니다.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['work-start'])) {
    $conn = connectDB();

    // 데이터 정리
    $check_in = trim($_POST['work-start']) . ':00';
    $check_out = trim($_POST['work-end']) . ':00';
    $late_time = (int) trim($_POST['late-time']);

    // 쿼리 작성
    $sql = "INSERT INTO settings (setting_id, standard_check_in, standard_check_out, late_threshold, updated_at)
            VALUES (1, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            standard_check_in = VALUES(standard_check_in),
            standard_check_out = VALUES(standard_check_out),
            late_threshold = VALUES(late_threshold),
            updated_at = NOW()";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssi", $check_in, $check_out, $late_time);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            // 성공 시 리디렉션
            header("Location: admin-settings.html?status=success");
            exit;
        } else {
            // 실행 실패 시 에러 확인용
            die("DB 실행 오류: " . $stmt->error);
        }
    } else {
        die("쿼리 준비 오류: " . $conn->error);
    }
} else {
    // POST 데이터가 없을 경우 강제 이동
    header("Location: admin-settings.html");
    exit;
}
?>