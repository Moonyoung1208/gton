<?php
require_once 'config.php';
$conn = connectDB();
$today = date("Y-m-d");

// 1. 오늘이 평일(월-금)인지 확인 (주말 결근 처리 방지)
$dayOfWeek = date('N');
if ($dayOfWeek > 5)
    exit("주말은 결근 처리하지 않습니다.");

// 2. 오늘 출근 기록이 없는 사용자 조회
$sql = "SELECT user_id FROM users 
        WHERE user_id NOT IN (
            SELECT user_id FROM records WHERE work_date = ?
        )";

// 오늘이 공휴일인지 확인
$check_holiday = "SELECT id FROM holidays WHERE holiday_date = ?";
$h_stmt = $conn->prepare($check_holiday);
$h_stmt->bind_param("s", $today);
$h_stmt->execute();
if ($h_stmt->get_result()->num_rows > 0) {
    exit("오늘은 공휴일이므로 결근 처리를 하지 않습니다.");
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

while ($user = $result->fetch_assoc()) {
    $uid = $user['user_id'];
    // 3. 결근 레코드 삽입
    $insert = "INSERT INTO records (user_id, work_date, status) VALUES (?, ?, 'absent')";
    $ins_stmt = $conn->prepare($insert);
    $ins_stmt->bind_param("is", $uid, $today);
    $ins_stmt->execute();
}
echo "결근 처리 완료";
?>