// main.js - 메인 페이지 (출퇴근 기록) JavaScript

// ==================== 페이지 인증 ====================

// 로그인 체크
if (!checkAuth()) {
    // common.js의 checkAuth가 자동으로 로그인 페이지로 리다이렉트
}

// ==================== 전역 변수 ====================

let clockInterval;
let workTimeInterval;
let todayAttendance = null;

// ==================== 초기화 ====================

document.addEventListener('DOMContentLoaded', async () => {
    // GPS 권한 확인 및 요청
    await requestGPSPermission();

    // GPS 자동 업데이트 시작
    startGPSAutoUpdate();

    // 실시간 시계 시작
    startClock();

    // 오늘의 출퇴근 현황 로드
    loadTodayAttendance();

    // 최근 기록 로드
    loadRecentRecords();

    // 연차 확인
    checkTodayLeave();

    // 이벤트 리스너 설정
    setupEventListeners();
});

// ==================== 실시간 시계 ====================

function startClock() {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
}

function updateClock() {
    const now = new Date();

    const timeElement = document.getElementById('currentTime');
    const dateElement = document.getElementById('currentDate');

    if (timeElement) {
        timeElement.textContent = formatTime(now);
    }

    if (dateElement) {
        dateElement.textContent = formatKoreanDate(now);
    }
}

// ==================== 출퇴근 현황 로드 ====================

function loadTodayAttendance() {
    // 로컬 스토리지에서 오늘 출퇴근 기록 가져오기
    const today = formatDate(new Date());
    const attendanceRecords = getFromLocalStorage('attendanceRecords') || [];

    todayAttendance = attendanceRecords.find(record => record.date === today);

    // UI 업데이트
    updateAttendanceUI();

    // 출근했으면 근무 시간 실시간 업데이트
    if (todayAttendance && todayAttendance.checkInTime && !todayAttendance.checkOutTime) {
        startWorkTimeCounter();
    }
}

function updateAttendanceUI() {
    const checkInStatus = document.getElementById('checkInStatus');
    const checkOutStatus = document.getElementById('checkOutStatus');
    const workingHours = document.getElementById('workingHours');
    const checkInBtn = document.getElementById('checkInBtn');
    const checkOutBtn = document.getElementById('checkOutBtn');

    if (todayAttendance) {
        // 출근 정보
        if (todayAttendance.checkInTime) {
            const checkInTime = new Date(todayAttendance.checkInTime);
            checkInStatus.innerHTML = `
        <span class="status-time">${formatTime(checkInTime)}</span>
        <span class="badge badge-success">완료</span>
      `;

            // 출근 버튼 비활성화
            checkInBtn.disabled = true;
            checkInBtn.textContent = '출근 완료';

            // 퇴근 버튼 활성화
            checkOutBtn.disabled = false;
        }

        // 퇴근 정보
        if (todayAttendance.checkOutTime) {
            const checkOutTime = new Date(todayAttendance.checkOutTime);
            checkOutStatus.innerHTML = `
        <span class="status-time">${formatTime(checkOutTime)}</span>
        <span class="badge badge-success">완료</span>
      `;

            // 퇴근 버튼 비활성화
            checkOutBtn.disabled = true;
            checkOutBtn.innerHTML = '<span class="btn-icon">✓</span>퇴근 완료';

            // 근무 시간 정지
            stopWorkTimeCounter();
        }

        // 근무 시간 계산
        if (todayAttendance.checkInTime) {
            const endTime = todayAttendance.checkOutTime
                ? new Date(todayAttendance.checkOutTime)
                : new Date();

            const minutes = calculateTimeDiff(todayAttendance.checkInTime, endTime);
            const timeText = formatMinutesToHours(minutes);

            workingHours.innerHTML = `<span class="status-time">${timeText}</span>`;
        }
    } else {
        // 출퇴근 전 상태
        checkInStatus.innerHTML = '<span class="status-time">미기록</span>';
        checkOutStatus.innerHTML = '<span class="status-time">미기록</span>';
        workingHours.innerHTML = '<span class="status-time">0시간 0분</span>';

        checkInBtn.disabled = false;
        checkOutBtn.disabled = true;
    }
}

// ==================== 근무 시간 실시간 업데이트 ====================

function startWorkTimeCounter() {
    if (workTimeInterval) {
        clearInterval(workTimeInterval);
    }

    workTimeInterval = setInterval(() => {
        if (todayAttendance && todayAttendance.checkInTime && !todayAttendance.checkOutTime) {
            const now = new Date();
            const minutes = calculateTimeDiff(todayAttendance.checkInTime, now);
            const timeText = formatMinutesToHours(minutes);

            const workingHours = document.getElementById('workingHours');
            if (workingHours) {
                workingHours.innerHTML = `<span class="status-time time-update">${timeText}</span>`;

                // 애니메이션 클래스 제거 (다음 업데이트를 위해)
                setTimeout(() => {
                    const timeSpan = workingHours.querySelector('.status-time');
                    if (timeSpan) {
                        timeSpan.classList.remove('time-update');
                    }
                }, 300);
            }
        }
    }, 60000); // 1분마다 업데이트
}

function stopWorkTimeCounter() {
    if (workTimeInterval) {
        clearInterval(workTimeInterval);
        workTimeInterval = null;
    }
}

// ==================== 이벤트 리스너 ====================

function setupEventListeners() {
    // 출근 버튼
    const checkInBtn = document.getElementById('checkInBtn');
    if (checkInBtn) {
        checkInBtn.addEventListener('click', handleCheckIn);
    }

    // 퇴근 버튼
    const checkOutBtn = document.getElementById('checkOutBtn');
    if (checkOutBtn) {
        checkOutBtn.addEventListener('click', handleCheckOut);
    }

    // 출근 확인
    const confirmCheckInBtn = document.getElementById('confirmCheckInBtn');
    if (confirmCheckInBtn) {
        confirmCheckInBtn.addEventListener('click', confirmCheckIn);
    }

    // 퇴근 확인
    const confirmCheckOutBtn = document.getElementById('confirmCheckOutBtn');
    if (confirmCheckOutBtn) {
        confirmCheckOutBtn.addEventListener('click', confirmCheckOut);
    }
}

// ==================== 출근 처리 ====================

async function handleCheckIn() {
    // GPS 위치 확인
    if (!isGPSLocationValid()) {
        showToast('GPS 위치를 확인하는 중입니다. 잠시 후 다시 시도해주세요.', 'warning');
        await updateGPSLocation();
        return;
    }

    const location = getCurrentGPSLocation();
    const now = new Date();

    // 확인 모달에 정보 표시
    document.getElementById('confirmCheckInTime').textContent = formatTime(now);
    document.getElementById('confirmCheckInLocation').textContent = location.address || '위치 정보 없음';

    // 모달 열기
    openModal('checkInModal');
}

function confirmCheckIn() {
    const location = getCurrentGPSLocation();
    const now = new Date();

    // 출근 기록 생성
    const today = formatDate(now);
    const attendanceRecords = getFromLocalStorage('attendanceRecords') || [];

    const newRecord = {
        id: 'att_' + Date.now(),
        userId: getCurrentUser().id,
        date: today,
        checkInTime: now.toISOString(),
        checkInLocation: {
            latitude: location.latitude,
            longitude: location.longitude,
            address: location.address,
            accuracy: location.accuracy
        },
        checkOutTime: null,
        checkOutLocation: null
    };

    // 기존 기록이 있으면 업데이트, 없으면 추가
    const existingIndex = attendanceRecords.findIndex(r => r.date === today && r.userId === getCurrentUser().id);

    if (existingIndex >= 0) {
        attendanceRecords[existingIndex] = {
            ...attendanceRecords[existingIndex],
            ...newRecord
        };
    } else {
        attendanceRecords.push(newRecord);
    }

    // 저장
    saveToLocalStorage('attendanceRecords', attendanceRecords);

    // 모달 닫기
    closeModal('checkInModal');

    // 성공 메시지
    showToast('✓ 출근이 기록되었습니다', 'success');

    // UI 업데이트
    todayAttendance = newRecord;
    updateAttendanceUI();

    // 근무 시간 카운터 시작
    startWorkTimeCounter();
}

// ==================== 퇴근 처리 ====================

async function handleCheckOut() {
    if (!todayAttendance || !todayAttendance.checkInTime) {
        showToast('출근 기록이 없습니다', 'error');
        return;
    }

    // GPS 위치 확인
    if (!isGPSLocationValid()) {
        showToast('GPS 위치를 확인하는 중입니다. 잠시 후 다시 시도해주세요.', 'warning');
        await updateGPSLocation();
        return;
    }

    const location = getCurrentGPSLocation();
    const now = new Date();

    // 근무 시간 계산
    const minutes = calculateTimeDiff(todayAttendance.checkInTime, now);
    const workHours = formatMinutesToHours(minutes);

    // 확인 모달에 정보 표시
    document.getElementById('confirmCheckOutTime').textContent = formatTime(now);
    document.getElementById('confirmCheckOutLocation').textContent = location.address || '위치 정보 없음';
    document.getElementById('confirmWorkHours').textContent = workHours;

    // 모달 열기
    openModal('checkOutModal');
}

function confirmCheckOut() {
    const location = getCurrentGPSLocation();
    const now = new Date();

    // 퇴근 기록 업데이트
    const today = formatDate(now);
    const attendanceRecords = getFromLocalStorage('attendanceRecords') || [];

    const recordIndex = attendanceRecords.findIndex(
        r => r.date === today && r.userId === getCurrentUser().id
    );

    if (recordIndex >= 0) {
        attendanceRecords[recordIndex].checkOutTime = now.toISOString();
        attendanceRecords[recordIndex].checkOutLocation = {
            latitude: location.latitude,
            longitude: location.longitude,
            address: location.address,
            accuracy: location.accuracy
        };

        // 저장
        saveToLocalStorage('attendanceRecords', attendanceRecords);

        // 모달 닫기
        closeModal('checkOutModal');

        // 성공 메시지
        const minutes = calculateTimeDiff(
            attendanceRecords[recordIndex].checkInTime,
            attendanceRecords[recordIndex].checkOutTime
        );
        const workHours = formatMinutesToHours(minutes);

        showToast(`✓ 퇴근이 기록되었습니다 (근무시간: ${workHours})`, 'success', 4000);

        // UI 업데이트
        todayAttendance = attendanceRecords[recordIndex];
        updateAttendanceUI();

        // 최근 기록 새로고침
        loadRecentRecords();
    } else {
        closeModal('checkOutModal');
        showToast('출근 기록을 찾을 수 없습니다', 'error');
    }
}

// ==================== 최근 기록 로드 ====================

function loadRecentRecords() {
    const recordList = document.getElementById('recentRecords');
    if (!recordList) return;

    const currentUser = getCurrentUser();
    const attendanceRecords = getFromLocalStorage('attendanceRecords') || [];

    // 현재 사용자의 최근 5일 기록
    const userRecords = attendanceRecords
        .filter(r => r.userId === currentUser.id)
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 5);

    if (userRecords.length === 0) {
        recordList.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div class="empty-state-text">아직 출퇴근 기록이 없습니다</div>
      </div>
    `;
        return;
    }

    recordList.innerHTML = userRecords.map(record => {
        const date = new Date(record.date);
        const dayOfWeek = getKoreanDayOfWeek(date);

        let checkInDisplay = '-';
        let checkOutDisplay = '-';
        let workHours = '';

        if (record.checkInTime) {
            checkInDisplay = formatTime(new Date(record.checkInTime)).substring(0, 5);
        }

        if (record.checkOutTime) {
            checkOutDisplay = formatTime(new Date(record.checkOutTime)).substring(0, 5);
            const minutes = calculateTimeDiff(record.checkInTime, record.checkOutTime);
            workHours = `<div class="record-hours">${formatMinutesToHours(minutes)}</div>`;
        }

        return `
      <div class="record-item" onclick="viewRecordDetail('${record.id}')">
        <div class="record-date">
          <div class="record-day">${date.getMonth() + 1}/${date.getDate()}</div>
          <div class="record-weekday">${dayOfWeek}요일</div>
        </div>
        <div class="record-times">
          <div class="record-time-item">
            <div class="record-time-label">출근</div>
            <div class="record-time-value">${checkInDisplay}</div>
          </div>
          <div class="record-time-item">
            <div class="record-time-label">퇴근</div>
            <div class="record-time-value">${checkOutDisplay}</div>
          </div>
        </div>
        ${workHours}
      </div>
    `;
    }).join('');
}

// 기록 상세보기 (records.html로 이동)
window.viewRecordDetail = function (recordId) {
    window.location.href = `records.html?id=${recordId}`;
};

// ==================== 연차 확인 ====================

function checkTodayLeave() {
    const today = formatDate(new Date());
    const currentUser = getCurrentUser();
    const leaveRecords = getFromLocalStorage('leaveRecords') || [];

    // 오늘 승인된 연차가 있는지 확인
    const todayLeave = leaveRecords.find(leave =>
        leave.userId === currentUser.id &&
        leave.status === 'approved' &&
        leave.startDate <= today &&
        leave.endDate >= today
    );

    const leaveNotice = document.getElementById('leaveNotice');
    const checkInBtn = document.getElementById('checkInBtn');
    const checkOutBtn = document.getElementById('checkOutBtn');

    if (todayLeave) {
        // 연차 알림 표시
        leaveNotice.classList.remove('hidden');

        // 반차 체크
        if (todayLeave.type === 'morning_half') {
            // 오전 반차 - 오후 출근 가능
            leaveNotice.querySelector('h3').textContent = '오전 반차입니다';
            leaveNotice.querySelector('p').textContent = '오후 1시부터 출근해주세요';

            const now = new Date();
            const afternoon = new Date(now);
            afternoon.setHours(13, 0, 0, 0);

            if (now < afternoon) {
                checkInBtn.disabled = true;
                checkOutBtn.disabled = true;
            }
        } else if (todayLeave.type === 'afternoon_half') {
            // 오후 반차 - 오후 퇴근 불필요
            leaveNotice.querySelector('h3').textContent = '오후 반차입니다';
            leaveNotice.querySelector('p').textContent = '오후 1시에 자동 퇴근 처리됩니다';
        } else {
            // 종일 연차 - 출퇴근 불필요
            checkInBtn.disabled = true;
            checkOutBtn.disabled = true;
        }
    }
}

// ==================== 페이지 언로드 시 정리 ====================

window.addEventListener('beforeunload', () => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
    if (workTimeInterval) {
        clearInterval(workTimeInterval);
    }
});