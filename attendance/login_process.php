<?php
session_start();

require_once 'config.php';

// 1. 데이터베이스 연결
$conn = connectDB();

// 2. 폼 데이터 수신 및 유효성 검사
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // $conn->real_escape_string() 대신 prepared statement를 사용하고 있으므로, 
    // $username_input을 직접 사용하여 바인딩합니다. (보안상 더 좋음)
    $username_input = trim($_POST['username']);
    $password_input = $_POST['password'];

    if (empty($username_input) || empty($password_input)) {
        echo "<script>alert('아이디와 비밀번호를 모두 입력해주세요.'); window.location.href='index.html';</script>";
        exit;
    }

    // 3. 데이터베이스에서 사용자 정보 조회
    $sql = "SELECT user_id, username, name, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    // 사용자 아이디 파라미터 바인딩
    $stmt->bind_param("s", $username_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 4. 비밀번호 검증
        if (password_verify($password_input, $user['password'])) {

            // 비밀번호 일치: 로그인 성공
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['logged_in'] = TRUE;

            echo "<script>alert('로그인 성공'); window.location.href='dashboard.html';</script>";

        } else {
            // 비밀번호 불일치
            echo "<script>alert('비밀번호가 일치하지 않습니다.'); window.location.href='index.html';</script>";
            exit;
        }
    } else {
        // 사용자를 찾을 수 없음
        echo "<script>alert('등록되지 않은 아이디입니다.'); window.location.href='index.html';</script>";
        exit;
    }

    $stmt->close();

} else {
    // POST 요청이 아닌 경우 (직접 접근 방지)
    header("Location: index.html");
    exit;
}

$conn->close();
?>