<?php
// login_process.php
// 處理登入：查詢會員、驗證 password_hash、建立 session。
require_once __DIR__ . '/functions.php';
start_session_once();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

verify_csrf();

$account = trim($_POST['account'] ?? '');
$password = $_POST['password'] ?? '';

if ($account === '' || $password === '') {
    redirect('login.php?error=' . urlencode('請輸入帳號與密碼'));
}

$stmt = $pdo->prepare('SELECT id, username, password_hash, role, status FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$account, $account]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirect('login.php?error=' . urlencode('帳號或密碼錯誤'));
}

if ($user['status'] !== 'active') {
    redirect('login.php?error=' . urlencode('此帳號目前被停用，請聯絡管理員'));
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];

redirect('index.php');
