<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

// 에러 발생 시 JSON 응답을 위해 에러 출력 끔
error_reporting(0);

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$conn = connectDB();
$record_id = $_POST['id'] ?? null;
$in_time = $_POST['check_in_time'] ?? '';
$out_time = $_POST['check_out_time'] ?? '';
$reason = $_POST['edit_reason'] ?? '';

if (!$record_id) {
    echo json_encode(['success' => false, 'message' => '기록 ID가 누락되었습니다.']);
    exit;
}

$work_hours = null;
$db_out_time = null;

if ($in_time && $out_time) {
    // 둘 다 있을 때만 근무시간 계산
    $start = new DateTime($in_time);
    $end = new DateTime($out_time);
    if ($end < $start) $end->modify('+1 day');
    $diff = $start->diff($end);
    $work_hours = $diff->format('%H:%I');
    $db_out_time = $out_time;
}

// 2. 지각 기준 설정 가져오기
$sql_settings = "SELECT standard_check_in, late_threshold FROM settings WHERE setting_id = 1";
$res_set = $conn->query($sql_settings);
$settings = $res_set->fetch_assoc();

// 3. 지각 여부 판별
$new_status = 'normal';
if ($settings && $in_time) {
    $std_time = strtotime($settings['standard_check_in']);
    $threshold_min = (int) $settings['late_threshold'];
    $actual_time = strtotime($in_time);
    if ($actual_time > ($std_time + ($threshold_min * 60))) {
        $new_status = 'late';
    }
}

// 4. 데이터 업데이트 (records 테이블)
$sql_update = "UPDATE records SET 
                check_in_time = ?, 
                check_out_time = ?, 
                work_hours = ?, 
                status = ?, 
                is_edited = 1, 
                edit_reason = ? 
               WHERE record_id = ?";

$stmt = $conn->prepare($sql_update);

$stmt->bind_param("sssssi", $in_time, $db_out_time, $work_hours, $new_status, $reason, $record_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'new_status' => $new_status,
        'work_hours' => $work_hours ?? '-'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'DB 업데이트 실패: ' . $conn->error
    ]);
}
$conn->close();
exit;