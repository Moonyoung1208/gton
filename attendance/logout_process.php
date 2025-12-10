<?php
session_start(); // 1. 세션 시작

// 2. 세션 변수 전체 삭제
// 현재 세션에 등록된 모든 변수를 제거합니다.
$_SESSION = array();

// 3. 세션 쿠키 삭제 (선택 사항이지만 권장됨)
// 세션 ID가 저장된 쿠키 자체를 만료시켜 세션을 완전히 종료합니다.
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

// 4. 세션 파일 자체를 서버에서 파괴
session_destroy();

// 5. 로그인 페이지로 리디렉션
header("Location: ./index.html");
exit;
?>