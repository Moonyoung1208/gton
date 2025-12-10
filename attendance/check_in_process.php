<?php
session_start();
require_once 'config.php';

// 1. 로그인 상태 확인 (필수)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    // 비로그인 사용자는 로그인 페이지로 리다이렉트
    header("Location: index.html");
    exit;
}

// 2. DB 연결 및 데이터 준비
$conn = connectDB();

// settings 테이블에서 출퇴근 기준시간 불러오기
$sql_settings = "SELECT standard_check_in, late_threshold FROM settings WHERE setting_id = 1";
$result_settings = $conn->query($sql_settings);
$settings = $result_settings->fetch_assoc();

// 설정값 저장 및 기본값 지정
$STANDARD_CHECK_IN_TIME = $settings['standard_check_in'] ?? '09:00:00'; // HH:MM:SS
$LATE_THRESHOLD_MINUTES = (int) ($settings['late_threshold'] ?? 10);     // 분

// 지각 기준 시간 계산
$standard_check_in_timestamp = strtotime($STANDARD_CHECK_IN_TIME);
// 지각이 아닌 최종 출근 허용 시각 (기준 출근 시각 + 지각 허용 분)
$late_check_in_limit_timestamp = strtotime("+{$LATE_THRESHOLD_MINUTES} minutes", $standard_check_in_timestamp);

// 출근시간 및 사용자 정보 가져오기
$user_id = $_SESSION['user_id'];
$work_date = date("Y-m-d");
$check_in_time = date("H:i:s");
$check_in_timestamp = strtotime($check_in_time);
$check_in_location = "대구 수성구 동대구로 390"; // GPS 로 위치정보 가져오기 구현 필요
$status = 'normal';

if ($check_in_timestamp > $late_check_in_limit_timestamp) {
    $status = 'late';
} else {
    // 지각 기준 시각을 초과하지 않았다면 (정시 출근 또는 허용 범위 내 지각) 'normal'
    $status = 'normal';
}

// 3. 중복 출근 방지 확인 (오늘 이미 출근 기록이 있는지 검사)
$check_sql = "SELECT record_id FROM records WHERE user_id = ? AND work_date = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $work_date);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo "<script>alert('이미 오늘 출근 처리가 완료되었습니다. 중복 출근은 기록할 수 없습니다.'); window.location.href='dashboard.html';</script>";
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// 4. 출근 기록 INSERT
$insert_sql = "INSERT INTO records (user_id, work_date, check_in_time, check_in_location, status) 
               VALUES (?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);

// 파라미터 바인딩: i=integer, s=string
$insert_stmt->bind_param("issss", $user_id, $work_date, $check_in_time, $check_in_location, $status);

if ($insert_stmt->execute()) {
    // 성공 메시지
    echo "<script>alert('출근이 성공적으로 기록되었습니다. 출근 시각: {$check_in_time}'); window.location.href='dashboard.html';</script>";
} else {
    // 실패 메시지
    error_log("출근 기록 실패: " . $insert_stmt->error);
    echo "<script>alert('출근 기록 중 오류가 발생했습니다.'); window.location.href='dashboard.html';</script>";
}

$insert_stmt->close();
$conn->close();
?>