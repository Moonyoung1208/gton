<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: ./index.html");
    exit;
}
require_once 'config.php';

$current_user_id = $_SESSION['user_id'];
$conn = @connectDB();

// --- 1. 내 연차 통계 (상단 카드용) ---
$sql_status = "SELECT total_days, used_days, (total_days - used_days) as remain_days FROM leave_status WHERE user_id = ?";
$stmt_status = $conn->prepare($sql_status);
$stmt_status->bind_param("i", $current_user_id);
$stmt_status->execute();
$leave_status = $stmt_status->get_result()->fetch_assoc();

// --- 2. 달력 이벤트 통합 (근태 + 연차) ---
$calendarEvents = [];

if ($conn) {
    // A. 내 연차 신청 내역 (leave_requests)
    $sql_leave = "SELECT start_date, end_date, leave_type, status, reason FROM leave_requests WHERE user_id = ?";
    $stmt_leave = $conn->prepare($sql_leave);
    $stmt_leave->bind_param("i", $current_user_id);
    $stmt_leave->execute();
    $res_leave = $stmt_leave->get_result();

    $typeMap = ['full' => '연차', 'am_off' => '오전반차', 'pm_off' => '오후반차'];

    while ($row = $res_leave->fetch_assoc()) {
        $className = match ($row['status']) {
            'approved' => 'event-approved', // 승인됨
            'pending' => 'event-pending',   // 대기중
            'rejected' => 'event-rejected', // 반려됨
            default => ''
        };
        $calendarEvents[] = [
            'start' => $row['start_date'],
            'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')),
            'title' => '🏖️ ' . ($typeMap[$row['leave_type']] ?? '휴가'),
            'extendedProps' => ['reason' => $row['reason'], 'status' => $row['status']],
            'classNames' => [$className]
        ];
    }

    // B. 기존 근태 기록 (records - 선택사항: 달력에 함께 보고 싶을 경우)
    $sql_rec = "SELECT work_date, status FROM records WHERE user_id = ?";
    $stmt_rec = $conn->prepare($sql_rec);
    $stmt_rec->bind_param("i", $current_user_id);
    $stmt_rec->execute();
    $res_rec = $stmt_rec->get_result();

    while ($row = $res_rec->fetch_assoc()) {
        if ($row['status'] == 'absent')
            continue; // 결근은 제외하거나 별도 표시
        $calendarEvents[] = [
            'start' => $row['work_date'],
            'title' => '💼 출근',
            'display' => 'list-item',
            'classNames' => ['event-work']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>처리 중 - 근태ON</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./css/common.css">
</head>

<body class="bg-gray">
    <script>
        // PHP 결과에 따라 SweetAlert2 알림 띄우기
        const isSuccess = <?= json_encode($success) ?>;
        const message = <?= json_encode($msg) ?>;

        if (isSuccess) {
            Swal.fire({
                title: '신청 완료',
                text: message,
                icon: 'success',
                confirmButtonText: '확인',
                confirmButtonColor: '#3457D5'
            }).then(() => {
                // 신청 완료 후 메인 달력 페이지로 이동
                location.href = 'leave.html';
            });
        } else {
            Swal.fire({
                title: '오류 발생',
                text: message,
                icon: 'error',
                confirmButtonText: '뒤로가기',
                confirmButtonColor: '#757575'
            }).then(() => {
                history.back();
            });
        }
    </script>
</body>

</html>