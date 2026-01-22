<?php
session_start();
require_once '../config.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== TRUE) {
    header("Location: admin-login.html");
    exit;
}

$conn = connectDB();

// 부서 목록 가져오기
$sql = "SELECT * FROM departments ORDER BY id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>부서 관리 - 근태ON</title>

    <?php include('head.php'); ?>
    
</head>

<body>
    <div class="admin-menu">
        <div class="admin-nav-link">
            <i class="fa-solid fa-bars" id="menuOpen"></i>
        </div>
        <div class="page-header">
            <h1 class="page-title">GTON 부서 관리</h1>
        </div>
    </div>

    <?php include('header.php'); ?>

    <main class="container">
        <section class="section">
            <h2 class="section-title">새 부서 등록</h2>
            <form action="add_dept_process.php" method="POST" class="inline-form">
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="text" name="dept_name" class="form-input" placeholder="부서명을 입력하세요 (예: 인사팀)" required>
                    <button type="submit" class="btn btn-primary" style="width: 120px;">추가</button>
                </div>
            </form>
        </section>

        <section class="section">
            <h2 class="section-title">등록된 부서 목록</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <!-- <th>ID</th> -->
                            <th>부서명</th>
                            <th>등록일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <!-- <td style="text-align: center;">
                                <?php echo $row['id']; ?>
                            </td> -->
                                    <td style="font-weight: 600;">
                                        <?php echo htmlspecialchars($row['dept_name']); ?>
                                    </td>
                                    <td style="text-align: center; color: var(--Gray-600);">
                                        <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button class="btn-icon color-primary"
                                            onclick="editDept(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['dept_name']); ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn-icon color-danger" onclick="deleteDept(<?php echo $row['id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--Gray-400);">등록된 부서가
                                    없습니다.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="./js/nav.js"></script>
    <script>
        // SweetAlert 실행
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'dept_success') {
                Swal.fire({
                    icon: 'success',
                    title: '부서 등록 완료',
                    text: '새로운 부서가 명단에 추가되었습니다.',
                    confirmButtonColor: '#3457D5'
                }).then(() => {
                    // 주소창에서 파라미터 제거 (새로고침 시 중복 실행 방지)
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            } else if (status === 'dept_error') {
                Swal.fire({
                    icon: 'error',
                    title: '등록 실패',
                    text: '이미 존재하는 부서명이거나 오류가 발생했습니다.',
                    confirmButtonColor: '#ff5252'
                });
            }
        });
        function editDept(id, name) {
            Swal.fire({
                title: '부서명 수정',
                input: 'text',
                inputLabel: '변경할 부서 이름을 입력하세요',
                inputValue: name, // 기존 부서명이 입력창에 미리 채워짐
                showCancelButton: true,
                confirmButtonText: '수정 완료',
                cancelButtonText: '취소',
                confirmButtonColor: '#3457D5',
                inputValidator: (value) => {
                    if (!value) {
                        return '부서명을 입력해야 합니다!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // 부서 이름이 변경되었을 때만 처리 페이지로 이동
                    if (result.value !== name) {
                        location.href = `edit_dept_process.php?id=${id}&new_name=${encodeURIComponent(result.value)}`;
                    }
                }
            });
        }
        function deleteDept(id) {
            Swal.fire({
                title: '부서를 삭제하시겠습니까?',
                text: "해당 부서에 소속된 직원이 있을 경우 문제가 발생할 수 있습니다.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3457D5',
                confirmButtonText: '삭제',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.href = `delete_dept_process.php?id=${id}`;
                }
            })
        }
    </script>

</body>

</html>