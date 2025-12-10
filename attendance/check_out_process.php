<?php
session_start();
require_once 'config.php';

// 1. 로그인 상태 확인
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: index.html");
    exit;
}

// 2. DB 연결 및 데이터 준비
$conn = connectDB();

$user_id = $_SESSION['user_id'];
$work_date = date("Y-m-d");
$check_out_time = date("H:i:s"); // 현재 시각을 퇴근 시각으로 사용
$check_out_location = "대구 수성구 동대구로 390";
$standard_work_end_time = '18:00:00'; // 기준 퇴근 시간 (필요에 따라 설정)

// 3. 오늘 기록 조회 및 유효성 검사
$record_sql = "SELECT record_id, check_in_time, check_out_time, status 
               FROM records 
               WHERE user_id = ? AND work_date = ?";
$record_stmt = $conn->prepare($record_sql);
$record_stmt->bind_param("is", $user_id, $work_date);
$record_stmt->execute();
$record_result = $record_stmt->get_result();

if ($record_result->num_rows === 0) {
    // 출근 기록이 없는 경우
    echo "<script>alert('출근 기록이 없습니다. 먼저 출근 처리를 해주세요.'); window.location.href='dashboard.html';</script>";
    $record_stmt->close();
    $conn->close();
    exit;
}

$record = $record_result->fetch_assoc();
$record_id = $record['record_id'];
$check_in_time = $record['check_in_time'];

if ($record['check_out_time'] !== NULL) {
    // 이미 퇴근 처리된 경우
    echo "<script>alert('이미 퇴근 처리가 완료되었습니다. (퇴근 시각: {$record['check_out_time']})'); window.location.href='dashboard.html';</script>";
    $record_stmt->close();
    $conn->close();
    exit;
}
$record_stmt->close();

// 4. 총 근무 시간 계산
// 근무 시간 계산 함수 (시간 문자열을 받아서 차이를 시간 단위(float)로 반환)
function calculate_work_hours($in_time, $out_time)
{
    $t1 = strtotime($in_time);
    $t2 = strtotime($out_time);
    $diff_seconds = $t2 - $t1;
    // 시(hour) 단위로 변환 (예: 8.5)
    return round($diff_seconds / 3600, 2);
}

$work_hours = calculate_work_hours($check_in_time, $check_out_time);

// 5. 최종 근무 상태 결정 (예: 조퇴, 정상)
// 여기서는 간단히 'working' 상태를 'normal'로 최종 확정한다고 가정.
// 더 복잡한 로직(퇴근 시간이 기준 시간보다 빠르면 'early_leave' 등)은 필요에 따라 추가
$final_status = ($record['status'] === 'working') ? 'normal' : $record['status']; // 지각('late')이면 그대로 유지

// 6. DB 업데이트 (퇴근 시각, 위치, 근무 시간, 상태)
$update_sql = "UPDATE records 
               SET check_out_time = ?, 
                   check_out_location = ?, 
                   work_hours = ?, 
                   status = ? 
               WHERE record_id = ?";
$update_stmt = $conn->prepare($update_sql);

// 바인딩: s=퇴근시각, s=퇴근위치, d=근무시간(Decimal/Double), s=상태, i=record_id
$update_stmt->bind_param("ssdsi", $check_out_time, $check_out_location, $work_hours, $final_status, $record_id);

if ($update_stmt->execute()) {
    $work_hours_display = number_format($work_hours, 2); // 8.50 시간 형태로 표시
    echo "<script>alert('퇴근이 성공적으로 처리되었습니다. 총 근무시간: {$work_hours_display}시간'); window.location.href='dashboard.html';</script>";
} else {
    error_log("퇴근 기록 업데이트 실패: " . $update_stmt->error);
    echo "<script>alert('퇴근 기록 업데이트 중 오류가 발생했습니다.'); window.location.href='dashboard.html';</script>";
}

$update_stmt->close();
$conn->close();
?>