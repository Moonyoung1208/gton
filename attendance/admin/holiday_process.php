<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']))
    exit(json_encode(['success' => false]));

$conn = connectDB();
$action = $_POST['action'] ?? '';
$date = $_POST['date'] ?? '';
$name = $_POST['name'] ?? '';

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, holiday_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name)");
    $stmt->bind_param("ss", $date, $name);
    echo json_encode(['success' => $stmt->execute()]);
} else if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM holidays WHERE holiday_date = ?");
    $stmt->bind_param("s", $date);
    echo json_encode(['success' => $stmt->execute()]);
}