<?php
// post_reply.php
// 新增留言。作者使用 session 裡的登入者，而不是讓使用者手動輸入作者名稱。
require_once __DIR__ . '/functions.php';
start_session_once();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

$newsId = (int)($_POST['news_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$userId = current_user_id();

if ($newsId <= 0 || $content === '') {
    flash_set('error', '留言內容不可空白。');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT id FROM news WHERE id = ? LIMIT 1');
$stmt->execute([$newsId]);
if (!$stmt->fetch()) {
    flash_set('error', '找不到此討論。');
    redirect('index.php');
}

$content = mb_substr($content, 0, MAX_POST_LENGTH);
$stmt = $pdo->prepare('INSERT INTO replies (news_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$newsId, $userId, $content]);

flash_set('success', '留言已送出。');
redirect('show_news.php?id=' . $newsId);
