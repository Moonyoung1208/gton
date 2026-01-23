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

// 클라이언트에서 보낸 위도, 경도 값 받기
$user_lat = $_POST['latitude'] ?? null;
$user_lng = $_POST['longitude'] ?? null;
$check_in_location = $_POST['location_addr'] ?? "위치 정보 없음";

if (!$user_lat || !$user_lng) {
    $_SESSION['status'] = 'error';
    $_SESSION['msg'] = '위치 정보를 확인할 수 없습니다. GPS를 켜주세요.';
    header("Location: dashboard.html");
    exit;
}

// DB에서 설정된 회사 위치 및 허용 반경 가져오기
$sql_settings = "SELECT company_lat, company_lng, allowed_radius, standard_check_in, late_threshold FROM settings WHERE setting_id = 1";
$result_set = $conn->query($sql_settings);
$settings = $result_set->fetch_assoc();

$company_lat = $settings['company_lat'];
$company_lng = $settings['company_lng'];
$allowed_radius = $settings['allowed_radius']; // 미터(m) 단위

// 거리 계산 함수 (Haversine 공식)
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earth_radius = 6371000; // 지구 반지름 (미터)
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

$distance = getDistance($company_lat, $company_lng, $user_lat, $user_lng);

// 반경 체크: 허용 거리보다 멀리 있다면 차단
if ($distance > $allowed_radius) {
    $_SESSION['status'] = 'error';
    $_SESSION['msg'] = "회사와 너무 멉니다. (현재 거리: " . round($distance) . "m)";
    header("Location: dashboard.html");
    exit;
}

// 지각 여부 판별
$status = 'normal';
if ($check_in_timestamp > $late_check_in_limit_timestamp) {
    $status = 'late';
} else {
    // 지각 기준 시각을 초과하지 않았다면 (정시 출근 또는 허용 범위 내 지각) 'normal'
    $status = 'normal';
}

// 중복 출근 방지 확인 (오늘 이미 출근 기록이 있는지 검사)
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

// 출근 기록 INSERT
$sql_insert = "INSERT INTO records (user_id, work_date, check_in_time, status) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("isss", $user_id, $work_date, $check_in_time, $status);

if ($insert_stmt->execute()) {
    // 성공 시 세션에 정보 저장
    $_SESSION['status'] = "success";
    $_SESSION['msg'] = "출근이 성공적으로 기록되었습니다.";
    header("Location: dashboard.html");
} else {
    // 실패 시 세션에 에러 정보 저장
    error_log("출근 기록 실패: " . $insert_stmt->error);
    $_SESSION['status'] = "error";
    $_SESSION['msg'] = "출근 기록 중 오류가 발생했습니다.";
    header("Location: dashboard.html");
}

$insert_stmt->close();
$conn->close();
?>