<?php
session_start();

require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // config.php의 connectDB 함수를 사용하여 DB 연결
    $conn = connectDB();

    // 1. DB에서 사용자 조회
    // ID를 기준으로 관리자 정보와 해시된 비밀번호를 가져옵니다.
    $sql = "SELECT admin_id, password_hash, admin_name FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // 2. 비밀번호 확인: password_verify() 함수로 입력된 비밀번호와 DB의 해시를 비교합니다.
        if (password_verify($password, $admin['password_hash'])) {
            // 로그인 성공

            // 세션 생성
            $_SESSION['admin_logged_in'] = TRUE;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_name'];

            $stmt->close();
            $conn->close();

            // 근무 기준 시간 설정 페이지로 이동
            header("Location: admin-dashboard.html");
            exit;
        }
    }

    // 3. 로그인 실패 (조회된 사용자가 없거나, 비밀번호가 틀린 경우)

    // DB 연결 정리
    if (isset($stmt))
        $stmt->close();
    if (isset($conn))
        $conn->close();

    // 실패 메시지를 띄우고 로그인 페이지로 복귀
    echo "<script>alert('아이디 또는 비밀번호가 잘못되었습니다.'); window.location.href='admin-login.html';</script>";
    exit;
}

// POST 요청이 아니면 로그인 페이지로 리다이렉트
header("Location: admin-login.html");
exit;
?>