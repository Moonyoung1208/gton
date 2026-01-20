<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conn = connectDB();

$statusFilter = $_GET['status'] ?? '전체';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');

// 기본 쿼리
$sql = "SELECT work_date, check_in_time, check_out_time, status, work_hours 
        FROM records 
        WHERE user_id = ? AND work_date BETWEEN ? AND ?";

$params = [$current_user_id, $startDate, $endDate];
$types = "iss";

// 상태 필터 추가
if ($statusFilter !== '전체') {
    $dbStatus = match ($statusFilter) {
        '정상' => 'normal',
        '지각' => 'late',
        '조퇴' => 'early',
        '결근' => 'absent',
        default => null
    };
    if ($dbStatus) {
        $sql .= " AND status = ?";
        $params[] = $dbStatus;
        $types .= "s";
    }
}

$sql .= " ORDER BY work_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$statusMap = ['normal' => '정상', 'late' => '지각', 'early' => '조퇴', 'early_leave' => '조퇴', 'absent' => '결근'];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => $row['work_date'],
        'onTime' => $row['check_in_time'] ? substr($row['check_in_time'], 0, 5) : '--:--',
        'offTime' => $row['check_out_time'] ? substr($row['check_out_time'], 0, 5) : '--:--',
        'workTime' => $row['work_hours'] ?: '0h 0m',
        'status' => $statusMap[$row['status']] ?? '기타'
    ];
}

echo json_encode($data);