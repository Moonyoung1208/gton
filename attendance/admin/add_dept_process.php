<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    exit("권한이 없습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['dept_name'])) {
    $conn = connectDB();
    $dept_name = $_POST['dept_name'];

    $sql = "INSERT INTO departments (dept_name, created_at) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dept_name);

    if ($stmt->execute()) {
        header("Location: admin-departments.php?status=dept_success");
    } else {
        header("Location: admin-departments.php?status=dept_error");
    }
    $stmt->close();
    $conn->close();
} else {
    header("Location: admin-departments.php");
}
exit;