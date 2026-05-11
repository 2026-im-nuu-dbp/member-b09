<?php
// admin_user_save.php
// 管理員新增或修改會員。active 代表已通過信箱驗證，pending 代表待驗證。

require_once __DIR__ . '/functions.php';
start_session_once();
require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
$statusInput = $_POST['status'] ?? 'active';
$status = in_array($statusInput, ['pending', 'active', 'locked'], true) ? $statusInput : 'active';
$favoriteColor = safe_color($_POST['favorite_color'] ?? '#c8e6a0');
$avatar = $_POST['avatar'] ?? 'steve';

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    flash_set('error', '帳號格式不正確。');
    redirect('admin.php' . ($id ? '?edit=' . $id : ''));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', 'Email 格式不正確。');
    redirect('admin.php' . ($id ? '?edit=' . $id : ''));
}
if ($nickname === '') {
    flash_set('error', '暱稱不可空白。');
    redirect('admin.php' . ($id ? '?edit=' . $id : ''));
}
if (!array_key_exists($avatar, avatar_options())) {
    $avatar = 'steve';
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
$stmt->execute([$username, $email, $id]);
if ($stmt->fetch()) {
    flash_set('error', '帳號或 Email 已被其他會員使用。');
    redirect('admin.php' . ($id ? '?edit=' . $id : ''));
}

// 管理員若把帳號調成 active，就補上 email_verified_at；調回 pending 則清空。
$emailVerifiedSql = $status === 'active' ? 'COALESCE(email_verified_at, NOW())' : 'NULL';

if ($id > 0) {
    if ($password !== '') {
        if (strlen($password) < 8) {
            flash_set('error', '密碼至少 8 個字元。');
            redirect('admin.php?edit=' . $id);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, nickname = ?, password_hash = ?, role = ?, status = ?, email_verified_at = {$emailVerifiedSql}, favorite_color = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $email, $nickname, $hash, $role, $status, $favoriteColor, $avatar, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, nickname = ?, role = ?, status = ?, email_verified_at = {$emailVerifiedSql}, favorite_color = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $email, $nickname, $role, $status, $favoriteColor, $avatar, $id]);
    }
    flash_set('success', '會員資料已更新。');
} else {
    if (strlen($password) < 8) {
        flash_set('error', '新增會員時密碼至少 8 個字元。');
        redirect('admin.php');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, nickname, password_hash, role, status, email_verified_at, favorite_color, avatar) VALUES (?, ?, ?, ?, ?, ?, {$emailVerifiedSql}, ?, ?)");
    $stmt->execute([$username, $email, $nickname, $hash, $role, $status, $favoriteColor, $avatar]);
    flash_set('success', '會員已新增。');
}

redirect('admin.php');
