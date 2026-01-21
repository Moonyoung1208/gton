<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$conn = connectDB();
$record_id = $_POST['id'] ?? null;
$in_time = $_POST['check_in_time'] ?? '';
$out_time = $_POST['check_out_time'] ?? '';
$reason = $_POST['edit_reason'] ?? '';

if (!$record_id) {
    echo json_encode(['success' => false, 'message' => 'ID가 누락되었습니다.']);
    exit;
}

// 근무시간계산
$work_hours = "00:00";

if ($in_time && $out_time) {
    $start = new DateTime($in_time);
    $end = new DateTime($out_time);

    // 퇴근이 출근보다 빠를 경우 (자정 너머 퇴근) 처리
    if ($end < $start) {
        $end->modify('+1 day');
    }

    $diff = $start->diff($end);

    // %H:%I 형식을 사용하여 08:30 형태로 포맷팅
    $work_hours = $diff->format('%H:%I');
}

// 1. 해당 사용자의 지각 기준 정보 가져오기
$sql_settings = "SELECT s.standard_check_in, s.late_threshold 
                 FROM records r 
                 JOIN settings s ON r.user_id = s.user_id 
                 WHERE r.record_id = ?";
$stmt_set = $conn->prepare($sql_settings);
$stmt_set->bind_param("i", $record_id);
$stmt_set->execute();
$settings = $stmt_set->get_result()->fetch_assoc();

// 2. 지각 여부 판별 (자동 상태 결정)
$new_status = 'normal';
if ($settings && $in_time) {
    $std_time = strtotime($settings['standard_check_in']);
    $threshold_min = (int) $settings['late_threshold'];
    $actual_time = strtotime($in_time);

    // 기준시간 + 허용분보다 늦게 출근하면 late
    if ($actual_time > ($std_time + ($threshold_min * 60))) {
        $new_status = 'late';
    }
}

// 3. 데이터 업데이트
$sql_update = "UPDATE records SET 
                check_in_time = ?, 
                check_out_time = ?, 
                work_hours = ?, 
                status = ?, 
                is_edited = 1, 
                edit_reason = ? 
               WHERE record_id = ?";

$stmt = $conn->prepare($sql_update);
// $work_hours가 이제 "08:30" 같은 문자열이므로 's'로 바인딩
$stmt->bind_param("sssssi", $in_time, $out_time, $work_hours, $new_status, $reason, $record_id);

$success = $stmt->execute();
echo json_encode(['success' => $success, 'new_status' => $new_status, 'work_hours' => $work_hours]);