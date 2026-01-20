<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$current_user_id = $_SESSION['user_id'];
$conn = connectDB();

$startDate = $_GET['startDate'] ?? date('Y-m-01');
$endDate = $_GET['endDate'] ?? date('Y-m-t');

// 1. 상태별 카운트
$statusCounts = ['normal' => 0, 'late' => 0, 'absent' => 0];
$sql = "SELECT status, COUNT(*) as cnt FROM records WHERE user_id = ? AND work_date BETWEEN ? AND ? GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $current_user_id, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($statusCounts[$row['status']]))
        $statusCounts[$row['status']] = (int) $row['cnt'];
}

// 2. 총 근무 시간
$sql_time = "SELECT SUM(TIME_TO_SEC(work_hours)) as total_seconds FROM records WHERE user_id = ? AND work_date BETWEEN ? AND ?";
$stmt_time = $conn->prepare($sql_time);
$stmt_time->bind_param("iss", $current_user_id, $startDate, $endDate);
$stmt_time->execute();
$res_time = $stmt_time->get_result()->fetch_assoc();
$total_seconds = $res_time['total_seconds'] ?? 0;

// 3. 평균 출근 시간
$sql_avg = "SELECT AVG(TIME_TO_SEC(check_in_time)) as avg_seconds FROM records WHERE user_id = ? AND work_date BETWEEN ? AND ? AND check_in_time IS NOT NULL";
$stmt_avg = $conn->prepare($sql_avg);
$stmt_avg->bind_param("iss", $current_user_id, $startDate, $endDate);
$stmt_avg->execute();
$res_avg = $stmt_avg->get_result()->fetch_assoc();

// 데이터 정리 후 전송
echo json_encode([
    'statusCounts' => $statusCounts,
    'totalHours' => floor($total_seconds / 3600),
    'totalMins' => floor(($total_seconds % 3600) / 60),
    'avgTime' => $res_avg['avg_seconds'] ? sprintf("%02d:%02d", floor($res_avg['avg_seconds'] / 3600), floor(($res_avg['avg_seconds'] % 3600) / 60)) : "--:--",
    'totalDays' => array_sum($statusCounts)
]);