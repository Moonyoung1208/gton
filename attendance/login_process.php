<?php
session_start();
require_once 'config.php';
$conn = connectDB();

// 결과 정보를 담을 배열
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = trim($_POST['username']);
    $password_input = $_POST['password'];
    // 체크박스 값 확인 (HTML의 name="rememberMe"와 일치해야 함)
    $remember_me = isset($_POST['rememberMe']) ? true : false;

    if (empty($username_input) || empty($password_input)) {
        $response['message'] = '아이디와 비밀번호를 모두 입력해주세요.';
    } else {
        $sql = "SELECT user_id, username, name, password FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password_input, $user['password'])) {
                // 기본적인 세션 변수 등록
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['logged_in'] = TRUE;

                // 로그인 유지
                if ($remember_me) {
                    
                    $lifetime = 86400 * 30;
                    $params = session_get_cookie_params();

                    // 현재 세션 ID가 담긴 쿠키의 만료 시간을 미래로 재설정
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + $lifetime,
                        $params["path"],
                        $params["domain"],
                        $params["secure"],
                        $params["httponly"]
                    );
                }

                $response['success'] = true;
                $response['message'] = $user['name'] . '님, 로그인 성공!';
            } else {
                $response['message'] = '비밀번호가 일치하지 않습니다.';
            }
        } else {
            $response['message'] = '등록되지 않은 아이디입니다.';
        }
        $stmt->close();
    }
}

// JSON 형태로 출력 후 종료
header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
exit;