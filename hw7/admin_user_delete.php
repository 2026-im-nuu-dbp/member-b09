<?php
// admin_user_delete.php
// 管理員刪除會員。資料庫設定 ON DELETE CASCADE，會員貼文與留言會一起刪除。
require_once __DIR__ . '/functions.php';
start_session_once();
require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$currentId = current_user_id();

if ($id <= 0) {
    flash_set('error', '無效的會員 ID。');
    redirect('admin.php');
}
if ($id === $currentId) {
    flash_set('error', '不能刪除目前登入中的自己。');
    redirect('admin.php');
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);
flash_set('success', '會員已刪除。');
redirect('admin.php');
