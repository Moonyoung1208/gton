/* record.js */

document.addEventListener('DOMContentLoaded', function () {
    // DOM 요소 정의
    const filterForm = document.getElementById('filterForm');
    const filterChips = document.querySelector('.filter-chips');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const searchButton = document.getElementById('searchButton');
    const statusFilterInput = document.getElementById('statusFilter');


    // 1. 날짜 변경 시 상태 필터 초기화 함수
    function resetStatusFilter() {

        // 모든 칩의 active 클래스 제거 (시각적 리셋)
        filterChips.querySelectorAll('.chip').forEach(chip => {
            chip.classList.remove('active');
        });

        // Hidden Input 값 초기화 (서버에 '선택 안 됨'을 알리기 위해 빈 문자열 사용)
        if (statusFilterInput) {
            statusFilterInput.value = '';
        }

    }

    // 2. 유효성 검사 함수: 날짜가 모두 채워져 있는지 확인
    function checkFilterValidity() {
        // 시작일과 종료일 값이 모두 존재할 때만 버튼 활성화
        if (startDateInput.value && endDateInput.value) {
            searchButton.classList.add('btn-primary');
            searchButton.classList.remove('btn-secondary');
        } else {
            searchButton.classList.add('btn-secondary');
            searchButton.classList.renmove('btn-primary');
        }
    }


    // 3. 근무 상태 칩 클릭 처리 함수
    function handleChipClick(e) {
        if (e.target.classList.contains('chip')) {
            const statusValue = e.target.getAttribute('data-value');

            // 1) Hidden Input 값 업데이트
            if (statusFilterInput) {
                statusFilterInput.value = statusValue;
            }

            // 2) 칩의 active 클래스 업데이트
            filterChips.querySelectorAll('.chip').forEach(chip => {
                chip.classList.remove('active');
            });
            e.target.classList.add('active');

            // 3) 폼 제출: 날짜가 유효할 경우에만 바로 제출
            if (startDateInput.value && endDateInput.value) {
                filterForm.submit();
            }
        }
    }

    // 4. 이벤트 리스너 연결

    // 날짜 입력 변경 시 유효성 검사
    startDateInput.addEventListener('change', resetStatusFilter);
    endDateInput.addEventListener('change', resetStatusFilter);

    // 칩 클릭 시 상태 업데이트 및 폼 제출 시도
    filterChips.addEventListener('click', handleChipClick);

    // 5. 초기 로드 시 실행: PHP에서 설정한 기본값이 유효한지 확인하고 버튼 상태 설정
    checkFilterValidity();
});