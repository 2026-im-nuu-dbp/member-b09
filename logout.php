<?php
// logout.php
// 清除 session 完成登出。
require_once __DIR__ . '/functions.php';
start_session_once();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect('login.php?success=' . urlencode('已登出，背包整理完畢。'));
