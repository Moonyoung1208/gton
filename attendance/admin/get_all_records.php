<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

$conn = connectDB();
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$dept_id = $_GET['dept_id'] ?? '';

// 1. 기본 조건 (날짜 기준)
$where = "WHERE r.work_date = ?";
$params = [$startDate];
$types = "s";

if ($dept_id) {
    $where .= " AND u.dept_id = ?";
    $params[] = $dept_id;
    $types .= "i";
}

// SQL 쿼리 수정 (u.user_id와 s.user_id 중복 방지 위해 명시)
$sql = "SELECT 
            r.record_id, r.user_id, r.work_date, r.check_in_time, r.check_out_time, 
            r.status, r.is_edited, r.edit_reason, r.work_hours,
            u.name, 
            d.dept_name, 
            s.standard_check_out
        FROM records r 
        JOIN users u ON r.user_id = u.user_id 
        LEFT JOIN departments d ON u.dept_id = d.id 
        LEFT JOIN settings s ON u.user_id = s.user_id
        $where 
        ORDER BY (r.check_out_time IS NULL) DESC, r.check_in_time DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // 가변 인자 사용 시 타입을 포함하여 배열을 구성하지 않고 
    // 타입 문자열을 첫 번째 인자로 명시해야 합니다.
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    echo json_encode(['list' => [], 'stats' => ['total' => 0, 'missing' => 0, 'edited' => 0], 'error' => $conn->error]);
    exit;
}
$list = $result->fetch_all(MYSQLI_ASSOC);

$stats = ['total' => count($list), 'missing' => 0, 'edited' => 0];
foreach ($list as $row) {
    if (!$row['check_out_time'])
        $stats['missing']++;
    if (isset($row['is_edited']) && $row['is_edited'] == 1)
        $stats['edited']++;
}

echo json_encode([
    'list' => $list,
    'stats' => $stats
]);