<?php
// register_process.php
// 處理註冊：檢查格式、雜湊密碼、寫入會員資料表。
require_once __DIR__ . '/functions.php';
start_session_once();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

verify_csrf();

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$nickname = trim($_POST['nickname'] ?? '');
$favoriteColor = safe_color($_POST['favorite_color'] ?? '#c8e6a0');
$avatar = $_POST['avatar'] ?? 'steve';

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    redirect('register.php?error=' . urlencode('帳號格式不正確'));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('register.php?error=' . urlencode('Email 格式不正確'));
}
if (strlen($password) < 8) {
    redirect('register.php?error=' . urlencode('密碼至少需要 8 個字元'));
}
if ($nickname === '' || mb_strlen($nickname) > 40) {
    redirect('register.php?error=' . urlencode('暱稱不可空白，且最多 40 字'));
}
if (!array_key_exists($avatar, avatar_options())) {
    $avatar = 'steve';
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    redirect('register.php?error=' . urlencode('帳號或 Email 已被使用'));
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, nickname, favorite_color, avatar, role, status) VALUES (?, ?, ?, ?, ?, ?, "user", "active")');
$stmt->execute([$username, $email, $passwordHash, $nickname, $favoriteColor, $avatar]);

redirect('login.php?success=' . urlencode('註冊成功，請登入。'));
