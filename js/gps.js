// gps.js - GPS 위치 관리

// ==================== GPS 상태 관리 ====================

let currentLocation = {
    latitude: null,
    longitude: null,
    accuracy: null,
    address: null,
    timestamp: null
};

let isGettingLocation = false;

/**
 * GPS 위치 업데이트
 */
async function updateGPSLocation() {
    if (isGettingLocation) return;

    isGettingLocation = true;

    const gpsAddress = document.getElementById('gpsAddress');
    const gpsAccuracy = document.getElementById('gpsAccuracy');
    const gpsInfo = document.querySelector('.gps-info');

    if (gpsAddress) {
        gpsAddress.textContent = '위치 정보를 가져오는 중...';
        gpsInfo.classList.add('gps-loading');
    }

    try {
        // GPS 위치 가져오기
        const position = await getCurrentPosition();

        currentLocation = {
            latitude: position.latitude,
            longitude: position.longitude,
            accuracy: position.accuracy,
            timestamp: new Date().toISOString()
        };

        // 주소로 변환
        const address = await getAddressFromCoords(
            position.latitude,
            position.longitude
        );

        currentLocation.address = address;

        // UI 업데이트
        if (gpsAddress) {
            gpsAddress.textContent = address;
        }

        if (gpsAccuracy) {
            const accuracyText = getAccuracyText(position.accuracy);
            gpsAccuracy.innerHTML = `<span style="color: ${getAccuracyColor(position.accuracy)}">${accuracyText}</span>`;
        }

    } catch (error) {
        console.error('GPS 오류:', error);

        if (gpsAddress) {
            gpsAddress.textContent = error.message || 'GPS 위치를 가져올 수 없습니다';
        }

        if (gpsAccuracy) {
            gpsAccuracy.textContent = '-';
        }

        // 사용자에게 오류 알림
        if (error.message.includes('권한')) {
            showToast('GPS 권한을 허용해주세요', 'warning', 5000);
        } else {
            showToast('GPS 위치를 가져올 수 없습니다', 'error');
        }

    } finally {
        isGettingLocation = false;
        if (gpsInfo) {
            gpsInfo.classList.remove('gps-loading');
        }
    }
}

/**
 * GPS 정확도에 따른 색상 반환
 */
function getAccuracyColor(accuracy) {
    if (accuracy < 10) return 'var(--color-success)';
    if (accuracy < 30) return 'var(--color-primary)';
    if (accuracy < 50) return 'var(--color-warning)';
    return 'var(--color-error)';
}

/**
 * 현재 GPS 위치 정보 반환
 */
function getCurrentGPSLocation() {
    return currentLocation;
}

/**
 * GPS 위치 유효성 확인
 */
function isGPSLocationValid() {
    if (!currentLocation.latitude || !currentLocation.longitude) {
        return false;
    }

    // 위치 정보가 5분 이상 오래되었는지 확인
    if (currentLocation.timestamp) {
        const now = new Date();
        const locationTime = new Date(currentLocation.timestamp);
        const diffMinutes = (now - locationTime) / 1000 / 60;

        if (diffMinutes > 5) {
            return false;
        }
    }

    return true;
}

/**
 * GPS 위치 새로고침 버튼 이벤트
 */
if (document.getElementById('refreshGpsBtn')) {
    document.getElementById('refreshGpsBtn').addEventListener('click', async () => {
        await updateGPSLocation();
    });
}

// ==================== 백그라운드 GPS 업데이트 ====================

/**
 * GPS 자동 업데이트 시작
 */
function startGPSAutoUpdate() {
    // 처음 로드 시 한번 실행
    updateGPSLocation();

    // 3분마다 자동 업데이트
    setInterval(() => {
        if (!isGettingLocation) {
            updateGPSLocation();
        }
    }, 3 * 60 * 1000); // 3분
}

// ==================== 위치 권한 요청 ====================

/**
 * GPS 권한 상태 확인
 */
async function checkGPSPermission() {
    if (!navigator.permissions) {
        return 'prompt';
    }

    try {
        const result = await navigator.permissions.query({ name: 'geolocation' });
        return result.state; // 'granted', 'denied', 'prompt'
    } catch (error) {
        return 'prompt';
    }
}

/**
 * GPS 권한 요청 및 안내
 */
async function requestGPSPermission() {
    const permission = await checkGPSPermission();

    if (permission === 'denied') {
        showConfirm(
            'GPS 권한 필요',
            'GPS 권한이 거부되어 있습니다. 출퇴근 기록을 위해 GPS 권한이 필요합니다. 브라우저 설정에서 권한을 허용해주세요.',
            () => {
                // 설정 페이지 안내
                showToast('브라우저 설정 > 개인정보 및 보안 > 사이트 설정에서 위치 권한을 허용해주세요', 'info', 7000);
            }
        );
        return false;
    }

    return true;
}

// ==================== 회사 위치와의 거리 계산 (선택적 기능) ====================

/**
 * 두 좌표 사이의 거리 계산 (Haversine formula)
 * @returns 거리 (미터)
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // 지구 반지름 (미터)
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c; // 거리 (미터)
}

/**
 * 회사 위치로부터의 거리 확인
 */
function checkDistanceFromOffice() {
    // 회사 위치 (실제로는 설정에서 가져와야 함)
    const officeLocation = getFromLocalStorage('officeLocation');

    if (!officeLocation || !currentLocation.latitude) {
        return { isNearOffice: true, distance: null };
    }

    const distance = calculateDistance(
        currentLocation.latitude,
        currentLocation.longitude,
        officeLocation.latitude,
        officeLocation.longitude
    );

    // 허용 반경 (미터) - 기본 500m
    const allowedRadius = officeLocation.radius || 500;

    return {
        isNearOffice: distance <= allowedRadius,
        distance: Math.round(distance)
    };
}