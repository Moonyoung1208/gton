<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

// 에러 발생 시 JSON으로 응답하기 위한 설정
ini_set('display_errors', 0);

try {
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

    // SQL 쿼리 수정: settings 테이블을 user_id 없이 조인하거나 
    // 서브쿼리로 전사 기준 퇴근 시간을 가져옵니다.
    $sql = "SELECT 
                r.record_id, r.user_id, r.work_date, r.check_in_time, r.check_out_time, 
                r.status, r.is_edited, r.edit_reason, r.work_hours,
                u.name, 
                d.dept_name,
                (SELECT standard_check_out FROM settings WHERE setting_id = 1) as standard_check_out
            FROM records r 
            JOIN users u ON r.user_id = u.user_id 
            LEFT JOIN departments d ON u.dept_id = d.id 
            $where 
            ORDER BY (r.check_out_time IS NULL) DESC, r.check_in_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $list = $result->fetch_all(MYSQLI_ASSOC);

    $stats = ['total' => count($list), 'missing' => 0, 'edited' => 0];
    foreach ($list as $row) {
        if (!$row['check_out_time'])
            $stats['missing']++;
        if (isset($row['is_edited']) && $row['is_edited'] == 1)
            $stats['edited']++;
    }

    echo json_encode(['list' => $list, 'stats' => $stats]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;