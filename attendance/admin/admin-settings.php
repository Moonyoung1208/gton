<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    header("Location: admin-login.html");
    exit;
}

$conn = connectDB();

// 1. settings 테이블에서 데이터 가져오기
$sql = "SELECT * FROM settings WHERE setting_id = 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

// 현재 설정값 조회
$sql_select = "SELECT standard_check_in, standard_check_out, late_threshold FROM settings WHERE setting_id = 1";
$result_select = $conn->query($sql_select);
$settings_row = $result_select->fetch_assoc();

$current_check_in = isset($settings_row['standard_check_in']) ? substr($settings_row['standard_check_in'], 0, 5) : '09:00';
$current_check_out = isset($settings_row['standard_check_out']) ? substr($settings_row['standard_check_out'], 0, 5) : '18:00';
$current_late_time = $settings_row['late_threshold'] ?? 10;
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 설정 - 근태ON</title>
    <?php include('head.php'); ?>
</head>

<body>
    <div class="admin-menu">
        <div class="admin-nav-link">
            <i class="fa-solid fa-bars" id="menuOpen" style="display: block;"></i>
        </div>
        <div class="page-header">
            <h2 class="page-title">GTON 알림 및 보안 설정</h2>
        </div>
    </div>

    <?php include('header.php'); ?>

    <main class="container">
        <section class="section">
            <h3 class="section-title">전사 근무 기준 설정</h3>
            <form method="POST" action="update_settings.php">
                <div class="date-inputs">
                    <div class="form-group">
                        <label class="form-label">출근 시각</label>
                        <input type="time" name="work-start" class="form-input" value="<?= $current_check_in ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">퇴근 시각</label>
                        <input type="time" name="work-end" class="form-input" value="<?= $current_check_out ?>"
                            required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">지각 허용 기준 (분)</label>
                    <input type="number" name="late-time" class="form-input" value="<?= $current_late_time ?>" required>
                    <p class="form-desc" style="font-size: 0.8rem; color: #888; margin-top: 5px;">* 설정한 시간(분) 이후 출근 시
                        '지각'으로 자동 처리됩니다.</p>
                </div>
                <button type="button" id="btn-save-settings" class="btn btn-primary full">
                    <i class="fa-regular fa-circle-check"></i> 설정 저장
                </button>
            </form>
        </section>

        <script>
            //근무시간 설정 완료 alert
            document.addEventListener('DOMContentLoaded', function () {
                const saveBtn = document.getElementById('btn-save-settings');
                const settingsForm = saveBtn.closest('form'); // 버튼이 속한 폼 찾기

                saveBtn.addEventListener('click', function () {
                    Swal.fire({
                        title: '근무 기준을 변경하시겠습니까?',
                        text: "변경사항은 이후 기록부터 적용되며, 이전 출퇴근 기록에는 영향을 주지 않습니다.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3457D5',
                        cancelButtonColor: '#757575',
                        confirmButtonText: '변경하기',
                        cancelButtonText: '취소',
                        reverseButtons: true // 확인/취소 버튼 위치 반전 (선택사항)
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // 사용자가 확인을 눌렀을 때만 폼 제출
                            settingsForm.submit();
                        }
                    });
                });

                const urlParams = new URLSearchParams(window.location.search);
                const status = urlParams.get('status');

                if (status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '설정 저장 완료',
                        text: '전사 근무 기준이 성공적으로 업데이트되었습니다.',
                        confirmButtonColor: '#4A90E2'
                    }).then(() => {
                        window.history.replaceState({}, document.title, window.location.pathname);
                    });
                } else if (status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: '저장 실패',
                        text: '데이터베이스 오류가 발생했습니다.',
                        confirmButtonColor: '#ff5252'
                    });
                }
            });
        </script>

        <section class="section">
            <h3 class="section-title">출퇴근 위치 설정</h3>
            <div class="admin-card">

                <div id="map" style="width:100%;height:350px;"></div>

                <form id="settingsForm" action="update_settings_process.php" method="POST">
                    <div class="form-group">
                        <label>회사 위도 (Latitude)</label>
                        <input type="text" name="company_lat" id="company_lat" value="<?= $settings['company_lat'] ?>"
                            readonly>
                    </div>
                    <div class="form-group">
                        <label>회사 경도 (Longitude)</label>
                        <input type="text" name="company_lng" id="company_lng" value="<?= $settings['company_lng'] ?>"
                            readonly>
                    </div>
                    <div class="form-group">
                        <label>허용 반경 (미터)</label>
                        <input type="number" name="allowed_radius" id="allowed_radius"
                            value="<?= $settings['allowed_radius'] ?>" placeholder="예: 100">
                    </div>
                    <button type="submit" class="btn btn-primary full">설정 저장</button>
                </form>
            </div>
        </section>

        <script type="text/javascript"
            src="https://dapi.kakao.com/v2/maps/sdk.js?appkey=d59b88e548c7e955f323a84e09ce25ab&autoload=false"></script>

        <script>
            // 페이지 로드 및 카카오 SDK 준비 완료 후 실행
            window.onload = function () {
                kakao.maps.load(function () {
                    // DB에서 가져온 좌표값 (없으면 대구 기본좌표)
                    var initialLat = Number("<?= $settings['company_lat'] ?>") || 37.5665;
                    var initialLng = Number("<?= $settings['company_lng'] ?>") || 126.9780;
                    var initialRadius = Number("<?= $settings['allowed_radius'] ?>") || 100;

                    var mapContainer = document.getElementById('map');
                    var mapOption = {
                        center: new kakao.maps.LatLng(initialLat, initialLng),
                        level: 3
                    };

                    var map = new kakao.maps.Map(mapContainer, mapOption);

                    // 1. 마커 생성 (드래그 가능)
                    var marker = new kakao.maps.Marker({
                        position: new kakao.maps.LatLng(initialLat, initialLng),
                        draggable: true
                    });
                    marker.setMap(map);

                    // 2. 허용 반경 시각화 (원)
                    var circle = new kakao.maps.Circle({
                        center: new kakao.maps.LatLng(initialLat, initialLng),
                        radius: initialRadius,
                        strokeWeight: 2,
                        strokeColor: '#75B8FA',
                        strokeOpacity: 0.8,
                        fillColor: '#CFE7FF',
                        fillOpacity: 0.5
                    });
                    circle.setMap(map);

                    // 3. 지도 컨트롤 추가 (스카이뷰 등)
                    var mapTypeControl = new kakao.maps.MapTypeControl();
                    map.addControl(mapTypeControl, kakao.maps.ControlPosition.TOPRIGHT);

                    // 4. 지도 클릭 이벤트: 클릭한 곳으로 좌표 업데이트
                    kakao.maps.event.addListener(map, 'click', function (mouseEvent) {
                        updatePosition(mouseEvent.latLng);
                    });

                    // 5. 마커 드래그 이벤트: 드래그가 끝난 곳으로 좌표 업데이트
                    kakao.maps.event.addListener(marker, 'dragend', function () {
                        updatePosition(marker.getPosition());
                    });

                    // 6. 반경 입력 시 원 크기 즉시 변경
                    document.getElementById('allowed_radius').addEventListener('input', function () {
                        circle.setRadius(Number(this.value));
                    });

                    // 좌표 및 UI 업데이트 함수
                    function updatePosition(latlng) {
                        marker.setPosition(latlng);
                        circle.setCenter(latlng);

                        // input 필드에 값 할당
                        document.getElementById('company_lat').value = latlng.getLat().toFixed(6);
                        document.getElementById('company_lng').value = latlng.getLng().toFixed(6);
                    }

                    console.log("카카오 지도 설정 완료");
                });
            };
        </script>

        <section class="section">
            <h3>알림 설정</h3>
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-label">지각 알림</div>
                    <div class="setting-desc">직원이 지각할 경우 이메일로 알림</div>
                </div>
                <label class="toggle">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-label">결근 알림</div>
                    <div class="setting-desc">직원이 결근할 경우 이메일로 알림</div>
                </div>
                <label class="toggle">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-label">일일 리포트</div>
                    <div class="setting-desc">매일 출퇴근 현황 리포트 발송</div>
                </div>
                <label class="toggle">
                    <input type="checkbox">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-label">주간 리포트</div>
                    <div class="setting-desc">매주 월요일 주간 근태 리포트 발송</div>
                </div>
                <label class="toggle">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </section>

        <section class="section">
            <h3>보안 설정</h3>
            <form id="password-form" method="POST" action="update_password.php">
                <div class="form-group">
                    <label class="form-label" for="current-password">현재 비밀번호</label>
                    <input type="password" name="current_pw" id="current-password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new-password">새 비밀번호</label>
                    <input type="password" name="new_pw" id="new-password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm-password">새 비밀번호 확인</label>
                    <input type="password" id="confirm-password" class="form-input" required>
                </div>

                <button type="submit" class="btn btn-primary full">비밀번호 변경</button>
            </form>
        </section>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // 1. URL에서 pw_status 파라미터 가져오기
                const urlParams = new URLSearchParams(window.location.search);
                const pwStatus = urlParams.get('pw_status');

                // 2. 상태에 따른 SweetAlert 메시지 설정
                if (pwStatus) {
                    let swalConfig = {
                        confirmButtonColor: '#3457D5',
                        confirmButtonText: '확인'
                    };

                    if (pwStatus === 'success') {
                        Swal.fire({
                            ...swalConfig,
                            icon: 'success',
                            title: '변경 완료',
                            text: '비밀번호가 안전하게 변경되었습니다.'
                        });
                    } else if (pwStatus === 'wrong_pw') {
                        Swal.fire({
                            ...swalConfig,
                            icon: 'error',
                            title: '변경 실패',
                            text: '현재 비밀번호가 일치하지 않습니다.'
                        });
                    } else if (pwStatus === 'error') {
                        Swal.fire({
                            ...swalConfig,
                            icon: 'error',
                            title: '오류 발생',
                            text: '데이터베이스 처리 중 문제가 발생했습니다.'
                        });
                    }

                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        </script>
    </main>
</body>

</html>