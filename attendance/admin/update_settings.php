<?php
session_start();
require_once '../config.php'; // 경로가 정확한지 다시 확인하세요.

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("접근 권한이 없습니다.");
}

$conn = connectDB();

// --- 1. 전사 근무 기준 설정 (시간/지각) 처리 ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['work-start'])) {

    // 데이터 정리
    $check_in = trim($_POST['work-start']) . ':00';
    $check_out = trim($_POST['work-end']) . ':00';
    $late_time = (int) trim($_POST['late-time']);

    // 쿼리 작성 (근무 시간 관련 컬럼 업데이트)
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
            header("Location: admin-settings.html?status=success");
            exit;
        } else {
            die("DB 실행 오류: " . $stmt->error);
        }
    } else {
        die("쿼리 준비 오류: " . $conn->error);
    }
}

// --- 2. 출퇴근 위치 설정 (위도/경도/반경) 처리 ---
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['company_lat'])) {

    // 데이터 정리
    $lat = trim($_POST['company_lat']);
    $lng = trim($_POST['company_lng']);
    $radius = (int) trim($_POST['allowed_radius']);

    // 쿼리 작성 (위치 관련 컬럼 업데이트)
    $sql = "INSERT INTO settings (setting_id, company_lat, company_lng, allowed_radius, updated_at)
            VALUES (1, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            company_lat = VALUES(company_lat),
            company_lng = VALUES(company_lng),
            allowed_radius = VALUES(allowed_radius),
            updated_at = NOW()";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssi", $lat, $lng, $radius);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            // 위치 저장 성공 시 loc_status 파라미터 전달
            header("Location: admin-settings.html?loc_status=success");
            exit;
        } else {
            die("DB 실행 오류: " . $stmt->error);
        }
    } else {
        die("쿼리 준비 오류: " . $conn->error);
    }
}

// 아무 데이터도 없을 경우
else {
    header("Location: admin-settings.html");
    exit;
}
?>