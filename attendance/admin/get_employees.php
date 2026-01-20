<?php
session_start();
require_once '../config.php';

// JSON 응답임을 명시 (에러가 나더라도 JSON으로 인식하지 않게 함)
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = connectDB();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// 1. 기본 SQL
$sql = "SELECT 
            u.user_id, u.name, u.email, d.dept_name, 
            u.position, u.join_date, u.status 
        FROM users u
        LEFT JOIN departments d ON u.dept_id = d.id";

// 2. 검색 조건 추가
if ($search !== '') {
    $sql .= " WHERE u.name LIKE ? OR u.email LIKE ? OR d.dept_name LIKE ?";
}

$sql .= " ORDER BY u.join_date DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// 3. 파라미터 바인딩 (이 부분에서 sssii 개수가 틀리면 에러 발생)
if ($search !== '') {
    $searchParam = "%$search%";
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

if (!$stmt->execute()) {
    // 쿼리 실행 실패 시 에러 출력
    echo json_encode(['error' => $conn->error]);
    exit;
}

$result = $stmt->get_result();
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
exit;