<?php
// register_process.php
// 處理註冊：建立 pending 會員、產生驗證 token、透過 Gmail SMTP 寄出驗證信。

require_once __DIR__ . '/mailer.php';
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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, nickname, favorite_color, avatar, role, status, email_verified_at)
         VALUES (?, ?, ?, ?, ?, ?, "user", "pending", NULL)'
    );
    $stmt->execute([$username, $email, $passwordHash, $nickname, $favoriteColor, $avatar]);
    $userId = (int)$pdo->lastInsertId();

    $plainToken = create_activation_token($pdo, $userId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('register.php?error=' . urlencode('註冊失敗：' . $e->getMessage()));
}

$mailOk = send_activation_email($pdo, $userId, $nickname, $email, $plainToken);

if ($mailOk) {
    redirect('login.php?success=' . urlencode('註冊申請已送出，驗證信已寄到你的 Email。請先點擊信中的連結完成驗證。'));
}

redirect('login.php?error=' . urlencode('帳號已建立，但驗證信寄送失敗。請聯絡管理員或檢查 Gmail SMTP 設定。'));
