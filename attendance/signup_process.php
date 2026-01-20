<?php
require_once 'config.php';
$conn = connectDB();

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dept_id = isset($_POST['dept_id']) ? intval($_POST['dept_id']) : 0;
    $position = trim($_POST['position'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // 1. 유효성 검사
    if (empty($username) || empty($name) || empty($email) || empty($password_input) || $dept_id === 0) {
        $response['message'] = '모든 필수 정보를 입력해주세요.';
    } elseif ($password_input !== $password_confirm) {
        $response['message'] = '비밀번호가 일치하지 않습니다.';
    } elseif (strlen($password_input) < 6) {
        $response['message'] = '비밀번호는 최소 6자 이상이어야 합니다.';
    } else {
        // 2. 중복 확인 (아이디)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = '이미 사용 중인 아이디입니다.';
        } else {
            // 3. 중복 확인 (이메일)
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $response['message'] = '이미 사용 중인 이메일입니다.';
            } else {
                // 4. 저장
                $hashed_pw = password_hash($password_input, PASSWORD_DEFAULT);
                $ins = $conn->prepare("INSERT INTO users (username, password, name, email, phone, dept_id, position, join_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $ins->bind_param("sssssis", $username, $hashed_pw, $name, $email, $phone, $dept_id, $position);

                if ($ins->execute()) {
                    $response['success'] = true;
                    $response['message'] = '회원가입이 완료되었습니다!';
                } else {
                    $response['message'] = 'DB 오류가 발생했습니다.';
                }
            }
        }
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;