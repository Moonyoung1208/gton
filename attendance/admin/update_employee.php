<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$conn = connectDB();

$user_id = $_POST['user_id'] ?? '';
$name = $_POST['name'] ?? '';
$dept_id = $_POST['dept_id'] ?? null;
$position = $_POST['position'] ?? '';
$email = $_POST['email'] ?? '';
$status = $_POST['status'] ?? 'active';

if (!$user_id || !$name) {
    echo json_encode(['success' => false, 'message' => '필수 값이 누락되었습니다.']);
    exit;
}

$sql = "UPDATE users SET name = ?, dept_id = ?, position = ?, email = ?, status = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sisssi", $name, $dept_id, $position, $email, $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}