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

    // 근무 상태 칩 클릭 처리 함수
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
});