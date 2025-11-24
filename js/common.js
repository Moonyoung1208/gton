// common.js - 공통 유틸리티 함수

// ==================== 날짜/시간 유틸리티 ====================

/**
 * 날짜를 YYYY-MM-DD 형식으로 포맷
 */
function formatDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * 시간을 HH:MM:SS 형식으로 포맷
 */
function formatTime(date) {
    const d = new Date(date);
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
}

/**
 * 날짜와 시간을 함께 포맷
 */
function formatDateTime(date) {
    return `${formatDate(date)} ${formatTime(date)}`;
}

/**
 * 한글 요일 반환
 */
function getKoreanDayOfWeek(date) {
    const days = ['일', '월', '화', '수', '목', '금', '토'];
    return days[new Date(date).getDay()];
}

/**
 * 날짜를 "YYYY년 MM월 DD일 (요일)" 형식으로 포맷
 */
function formatKoreanDate(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    const day = d.getDate();
    const dayOfWeek = getKoreanDayOfWeek(d);
    return `${year}년 ${month}월 ${day}일 (${dayOfWeek})`;
}

/**
 * 두 시간의 차이를 계산 (분 단위)
 */
function calculateTimeDiff(startTime, endTime) {
    const start = new Date(startTime);
    const end = new Date(endTime);
    const diffMs = end - start;
    return Math.floor(diffMs / 1000 / 60); // 분 단위
}

/**
 * 분을 "N시간 M분" 형식으로 변환
 */
function formatMinutesToHours(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours === 0) {
        return `${mins}분`;
    } else if (mins === 0) {
        return `${hours}시간`;
    } else {
        return `${hours}시간 ${mins}분`;
    }
}

// ==================== 모달 관리 ====================

/**
 * 모달 열기
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * 모달 닫기
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

/**
 * 모달 외부 클릭 시 닫기 설정
 */
function setupModalBackdropClose(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modalId);
            }
        });
    }
}

// ==================== 알림 메시지 ====================

/**
 * 토스트 알림 표시
 */
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} slide-up`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.left = '50%';
    toast.style.transform = 'translateX(-50%)';
    toast.style.zIndex = '1000';
    toast.style.maxWidth = '90%';
    toast.style.width = '400px';

    const icon = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    }[type] || 'ℹ';

    toast.innerHTML = `
    <span class="alert-icon">${icon}</span>
    <div class="alert-content">${message}</div>
  `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(-20px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * 확인 다이얼로그
 */
function showConfirm(title, message, onConfirm, onCancel) {
    const modalId = 'confirmModal';
    let modal = document.getElementById(modalId);

    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal-backdrop hidden';
        modal.innerHTML = `
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title" id="confirmTitle"></h3>
        </div>
        <div class="modal-body">
          <p id="confirmMessage"></p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary flex-1" id="confirmCancel">취소</button>
          <button class="btn btn-primary flex-1" id="confirmOk">확인</button>
        </div>
      </div>
    `;
        document.body.appendChild(modal);
    }

    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;

    const okBtn = document.getElementById('confirmOk');
    const cancelBtn = document.getElementById('confirmCancel');

    // 기존 이벤트 리스너 제거
    const newOkBtn = okBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOkBtn, okBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

    newOkBtn.addEventListener('click', () => {
        closeModal(modalId);
        if (onConfirm) onConfirm();
    });

    newCancelBtn.addEventListener('click', () => {
        closeModal(modalId);
        if (onCancel) onCancel();
    });

    openModal(modalId);
}

// ==================== 로딩 오버레이 ====================

/**
 * 로딩 표시
 */
function showLoading() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(overlay);
    }
    overlay.classList.remove('hidden');
}

/**
 * 로딩 숨기기
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

// ==================== 로컬 스토리지 관리 ====================

/**
 * 로컬 스토리지에 데이터 저장
 */
function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        return true;
    } catch (e) {
        console.error('로컬 스토리지 저장 실패:', e);
        return false;
    }
}

/**
 * 로컬 스토리지에서 데이터 가져오기
 */
function getFromLocalStorage(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (e) {
        console.error('로컬 스토리지 읽기 실패:', e);
        return null;
    }
}

/**
 * 로컬 스토리지에서 데이터 삭제
 */
function removeFromLocalStorage(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (e) {
        console.error('로컬 스토리지 삭제 실패:', e);
        return false;
    }
}

// ==================== 인증 관리 ====================

/**
 * 현재 로그인한 사용자 정보 가져오기
 */
function getCurrentUser() {
    return getFromLocalStorage('currentUser');
}

/**
 * 로그인 상태 확인
 */
function isLoggedIn() {
    return getCurrentUser() !== null;
}

/**
 * 로그아웃
 */
function logout() {
    removeFromLocalStorage('currentUser');
    removeFromLocalStorage('authToken');
    window.location.href = 'login.html';
}

/**
 * 관리자 권한 확인
 */
function isAdmin() {
    const user = getCurrentUser();
    return user && user.role === 'admin';
}

/**
 * 페이지 접근 권한 체크
 */
function checkAuth(requireAdmin = false) {
    if (!isLoggedIn()) {
        // window.location.href = 'login.html';
        return false;
    }

    if (requireAdmin && !isAdmin()) {
        showToast('관리자 권한이 필요합니다', 'error');
        window.location.href = 'index.html';
        return false;
    }

    return true;
}

// ==================== 유효성 검사 ====================

/**
 * 이메일 유효성 검사
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * 비밀번호 유효성 검사 (최소 8자)
 */
function isValidPassword(password) {
    return password && password.length >= 8;
}

/**
 * 필수 입력 검사
 */
function isRequired(value) {
    return value !== null && value !== undefined && value.trim() !== '';
}

// ==================== 데이터 필터링/정렬 ====================

/**
 * 날짜 범위 필터
 */
function filterByDateRange(items, startDate, endDate, dateKey = 'date') {
    const start = new Date(startDate);
    const end = new Date(endDate);

    return items.filter(item => {
        const itemDate = new Date(item[dateKey]);
        return itemDate >= start && itemDate <= end;
    });
}

/**
 * 검색어로 필터
 */
function filterBySearch(items, searchTerm, searchKeys) {
    if (!searchTerm) return items;

    const term = searchTerm.toLowerCase();
    return items.filter(item => {
        return searchKeys.some(key => {
            const value = item[key];
            return value && String(value).toLowerCase().includes(term);
        });
    });
}

/**
 * 배열 정렬
 */
function sortBy(items, key, order = 'asc') {
    return [...items].sort((a, b) => {
        const aVal = a[key];
        const bVal = b[key];

        if (order === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });
}

// ==================== 네비게이션 활성화 ====================

/**
 * 현재 페이지에 맞는 네비게이션 활성화
 */
function setActiveNavItem() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navItems = document.querySelectorAll('.navbar-item');

    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// ==================== 초기화 ====================

// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', () => {
    // 네비게이션 활성화
    setActiveNavItem();

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal-backdrop:not(.hidden)');
            modals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
});

// ==================== GPS 관련 유틸리티 ====================

/**
 * 현재 위치 가져오기
 */
function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('GPS를 지원하지 않는 브라우저입니다'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                });
            },
            (error) => {
                let errorMessage = 'GPS 위치를 가져올 수 없습니다';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'GPS 권한이 거부되었습니다';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = '위치 정보를 사용할 수 없습니다';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'GPS 요청 시간이 초과되었습니다';
                        break;
                }
                reject(new Error(errorMessage));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
}

/**
 * GPS 정확도 텍스트 반환
 */
function getAccuracyText(accuracy) {
    if (accuracy < 10) return '매우 높음';
    if (accuracy < 30) return '높음';
    if (accuracy < 50) return '보통';
    if (accuracy < 100) return '낮음';
    return '매우 낮음';
}

/**
 * 좌표를 주소로 변환 (Reverse Geocoding - 실제 구현 시 API 필요)
 */
async function getAddressFromCoords(latitude, longitude) {
    // 실제로는 카카오맵 API, 구글맵 API 등을 사용
    // 여기서는 임시 데이터 반환
    return `서울시 강남구 테헤란로 ${Math.floor(Math.random() * 100)}길`;
}