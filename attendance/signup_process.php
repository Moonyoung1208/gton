<?php
// 세션 사용 안 함

require_once 'config.php';

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 폼 데이터 가져오기
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);

    $password_input = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 에러 발생 시 입력값을 유지하기 위한 함수
    function redirectWithError($message, $name, $email, $username, $phone, $department, $position)
    {
        $error_msg = urlencode($message);
        $redirect_url = "signup.html?error=" . $error_msg .
            "&name=" . urlencode($name) .
            "&email=" . urlencode($email) .
            "&username=" . urlencode($username) .
            "&phone=" . urlencode($phone) .
            "&department=" . urlencode($department) .
            "&position=" . urlencode($position);
        header("Location: " . $redirect_url);
        exit;
    }

    // 1. 필수 필드 확인
    if (empty($username) || empty($name) || empty($email) || empty($password_input) || empty($password_confirm)) {
        redirectWithError('모든 필수 정보를 입력해주세요.', $name, $email, $username, $phone, $department, $position);
    }

    // 2. 비밀번호 일치 확인
    if ($password_input !== $password_confirm) {
        redirectWithError('비밀번호가 일치하지 않습니다.', $name, $email, $username, $phone, $department, $position);
    }

    // 3. 비밀번호 길이 확인 (최소 6자 이상 권장)
    if (strlen($password_input) < 6) {
        redirectWithError('비밀번호는 최소 6자 이상이어야 합니다.', $name, $email, $username, $phone, $department, $position);
    }

    // 4. 아이디 (username) 중복 확인
    $check_username_sql = "SELECT user_id FROM users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();

    if ($check_username_result->num_rows > 0) {
        $check_username_stmt->close();
        $conn->close();
        redirectWithError('이미 사용 중인 아이디입니다.', $name, $email, $username, $phone, $department, $position);
    }
    $check_username_stmt->close();

    // 5. 이메일 중복 확인
    $check_email_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        $check_email_stmt->close();
        $conn->close();
        redirectWithError('이미 사용 중인 이메일입니다.', $name, $email, $username, $phone, $department, $position);
    }
    $check_email_stmt->close();

    // 6. 이메일 형식 검증
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        redirectWithError('올바른 이메일 형식이 아닙니다.', $name, $email, $username, $phone, $department, $position);
    }

    // 7. 비밀번호 해싱
    $password_to_store = password_hash($password_input, PASSWORD_DEFAULT);

    // 8. 데이터베이스에 사용자 정보 삽입
    $insert_sql = "INSERT INTO users (username, password, name, email, phone, department, position) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    // 바인딩: 7개의 문자열 타입 파라미터 ("sssssss")
    // 순서: username, hashed_password, name, email, phone, department, position
    $insert_stmt->bind_param("sssssss", $username, $password_to_store, $name, $email, $phone, $department, $position);

    if ($insert_stmt->execute()) {
        // 회원가입 성공
        $insert_stmt->close();
        $conn->close();
        echo "<script>alert('회원가입이 성공적으로 완료되었습니다. 로그인 해주세요.'); window.location.href='index.html';</script>";
        exit;
    } else {
        // 삽입 실패
        $error_message = "회원가입에 실패했습니다. 잠시 후 다시 시도해주세요.";
        // 개발 환경에서만 상세 오류 표시 (운영 환경에서는 제거 권장)
        // $error_message .= " (오류: " . $insert_stmt->error . ")";

        $insert_stmt->close();
        $conn->close();
        redirectWithError($error_message, $name, $email, $username, $phone, $department, $position);
    }

} else {
    // POST 요청이 아닌 경우 (직접 접근 방지)
    header("Location: index.html");
    exit;
}

$conn->close();
?>