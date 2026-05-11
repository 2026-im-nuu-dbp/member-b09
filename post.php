<?php
// post.php
// 新增討論貼文。作者不再由表單輸入，而是直接使用登入者 user_id。
require_once __DIR__ . '/functions.php';
start_session_once();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$userId = current_user_id();

if ($title === '' || $content === '') {
    flash_set('error', '標題與內容不可空白。');
    redirect('index.php');
}

$title = mb_substr($title, 0, 200);
$content = mb_substr($content, 0, MAX_POST_LENGTH);

$stmt = $pdo->prepare('INSERT INTO news (title, content, user_id) VALUES (?, ?, ?)');
$stmt->execute([$title, $content, $userId]);

flash_set('success', '貼文已發布。');
redirect('index.php');
