<?php
// get_holidays.php (최종본)
require_once '../config.php';
header('Content-Type: application/json');
$conn = connectDB();

// DB(holidays 테이블)에 있는 것만 가져와서 화면에 뿌려줌
$sql = "SELECT holiday_date as start, holiday_name as title FROM holidays";
$result = $conn->query($sql);
$holidays = [];

while ($row = $result->fetch_assoc()) {
    // 화면 표시용 스타일 설정
    $row['className'] = 'holiday-event';
    $row['display'] = 'block';
    $row['extendedProps'] = [
        'source' => 'holiday' 
    ];
    
    $holidays[] = $row;
}

echo json_encode($holidays);