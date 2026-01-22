<?php
session_start(); //

// 1. 모든 세션 변수 해제
$_SESSION = array(); //

// 2. 세션 쿠키가 사용되었다면 쿠키도 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. 세션 완전 파기
session_destroy(); //

// 4. 로그인 페이지로 리다이렉트 (로그아웃 성공 메시지를 함께 보낼 수 있습니다)
header("Location: admin-login.html?logout=success");
exit;
?>