function confirmLogout(event) {
    event.preventDefault();

    Swal.fire({
        title: '로그아웃 하시겠습니까?',
        text: "로그아웃 시 관리자 세션이 종료됩니다.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3457D5',
        cancelButtonColor: '#888',
        confirmButtonText: '로그아웃',
        cancelButtonText: '취소',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            location.href = 'logout.php';
        }
    });
}