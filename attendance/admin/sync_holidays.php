<?php
// 에러 보고를 켜서 무엇이 문제인지 확인 (개발 완료 후 삭제 권장)
error_reporting(E_ALL);
ini_set('display_errors', 0); // 에러 문구가 JSON 출력을 망치지 않게 설정

require_once '../config.php';
header('Content-Type: application/json');

try {
    $conn = connectDB();
    if (!$conn)
        throw new Exception("DB 연결 실패");

    $api_key = "AIzaSyDMTQ8mSkHvslyq_Q_MbnNf9FEXWf1MHFo";
    $calendar_id = "ko.south_korea#holiday@group.v.calendar.google.com";

    // 연도 범위 설정
    $timeMin = date('Y-01-01\T00:00:00\Z');
    $timeMax = date('Y-12-31\T23:59:59\Z');

    $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendar_id) . "/events?key=" . $api_key . "&timeMin=" . $timeMin . "&timeMax=" . $timeMax . "&singleEvents=true";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 타임아웃 10초로 늘림

    $apiResponse = curl_exec($ch);
    $curlError = curl_error($ch); // CURL 에러 확인용
    curl_close($ch);

    if (!$apiResponse) {
        throw new Exception("구글 API 호출 실패: " . $curlError);
    }

    $data = json_decode($apiResponse, true);

    // 구글 API 응답 에러 확인
    if (isset($data['error'])) {
        throw new Exception("구글 API 에러: " . ($data['error']['message'] ?? '알 수 없는 에러'));
    }

    $count = 0;
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            // 종일 이벤트인 경우 'date' 필드 사용
            $date = $item['start']['date'] ?? substr($item['start']['dateTime'], 0, 10);
            $name = $item['summary'];

            $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, holiday_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name)");
            $stmt->bind_param("ss", $date, $name);
            if ($stmt->execute())
                $count++;
        }
    }

    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    // 에러 발생 시 정확한 원인을 JSON으로 반환
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}