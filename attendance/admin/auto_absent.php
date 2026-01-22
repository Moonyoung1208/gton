<?php
// admin/auto_absent.php
require_once '../config.php';
header('Content-Type: application/json');
$conn = connectDB();

$processed_dates = [];
$total_absent_count = 0;

// 1. 최근 7일간의 날짜를 확인 (필요에 따라 기간 조절 가능)
for ($i = 1; $i <= 7; $i++) {
    $targetDate = date('Y-m-d', strtotime("-$i day"));
    $dayOfWeek = date('N', strtotime($targetDate)); // 1(월)~7(일)

    // 2. 휴일/주말 체크
    $isHoliday = false;
    if ($dayOfWeek >= 6) {
        $isHoliday = true;
    } else {
        $hStmt = $conn->prepare("SELECT COUNT(*) FROM holidays WHERE holiday_date = ?");
        $hStmt->bind_param("s", $targetDate);
        $hStmt->execute();
        $hStmt->bind_result($hCount);
        $hStmt->fetch();
        $hStmt->close();
        if ($hCount > 0)
            $isHoliday = true;
    }

    if ($isHoliday)
        continue; // 휴일은 다음 날짜로 건너뜀

    // 3. 해당 날짜에 기록이 없는 사용자 조회
    $sql = "SELECT u.user_id 
            FROM users u 
            LEFT JOIN records r ON u.user_id = r.user_id AND r.work_date = ?
            WHERE r.record_id IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $targetDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $insertSql = "INSERT INTO records (user_id, work_date, status, check_in_time, check_out_time, work_hours) 
                      VALUES (?, ?, 'absent', NULL, NULL, '00:00:00')";
        $insertStmt = $conn->prepare($insertSql);

        while ($row = $result->fetch_assoc()) {
            $insertStmt->bind_param("is", $row['user_id'], $targetDate);
            if ($insertStmt->execute()) {
                $total_absent_count++;
            }
        }
        $processed_dates[] = $targetDate;
    }
}

echo json_encode([
    "success" => true,
    "total_count" => $total_absent_count,
    "processed_dates" => $processed_dates,
    "message" => "최근 누락된 기록을 모두 확인했습니다. 총 {$total_absent_count}건의 결근 처리가 완료되었습니다."
]);
$conn->close();