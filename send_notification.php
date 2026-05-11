<?php
// send_notification.php
// 處理寄信表單。支援單一收件者、管理員群發、附加圖片。
require_once __DIR__ . '/mailer.php';
start_session_once();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mailer_panel.php');
}
verify_csrf();

$user = current_user($pdo);
$isAdmin = $user && $user['role'] === 'admin';
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');
$toEmail = trim($_POST['to_email'] ?? '');
$toName = trim($_POST['to_name'] ?? '');
$sendToAll = $isAdmin && isset($_POST['send_to_all']);

if ($subject === '' || $body === '') {
    flash_set('error', '主旨與內容不可空白。');
    redirect('mailer_panel.php');
}

$attachment = null;
if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    if ($_FILES['image']['size'] > MAX_IMAGE_SIZE) {
        flash_set('error', '圖片太大，請壓到 3MB 以下。');
        redirect('mailer_panel.php');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
        flash_set('error', '只允許 jpg、png、gif、webp 圖片。');
        redirect('mailer_panel.php');
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? 'img';
    $safeName = 'mail_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $uploadDir = __DIR__ . '/storage/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $targetPath = $uploadDir . '/' . $safeName;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        flash_set('error', '圖片儲存失敗。');
        redirect('mailer_panel.php');
    }
    $attachment = [
        'path' => $targetPath,
        'name' => $safeName,
        'mime' => $mime,
    ];
}

$recipients = [];
if ($sendToAll) {
    $stmt = $pdo->query('SELECT nickname, email FROM users WHERE status = "active" ORDER BY id ASC');
    foreach ($stmt->fetchAll() as $row) {
        $recipients[strtolower($row['email'])] = [
            'email' => $row['email'],
            'name' => $row['nickname'],
        ];
    }
    $recipients[strtolower(TEACHER_TEST_EMAIL)] = [
        'email' => TEACHER_TEST_EMAIL,
        'name' => TEACHER_TEST_NAME,
    ];
} else {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', '請填寫正確的收件人 Email。');
        redirect('mailer_panel.php');
    }
    $recipients[strtolower($toEmail)] = [
        'email' => $toEmail,
        'name' => $toName ?: $toEmail,
    ];
}

$success = 0;
$failed = 0;
foreach ($recipients as $recipient) {
    $personalBody = "您好，{$recipient['name']}：\n\n";
    $personalBody .= $body . "\n\n";
    $personalBody .= "---\n";
    $personalBody .= "寄件者：{$user['nickname']}\n";
    $personalBody .= "系統：" . APP_NAME . "\n";
    $personalBody .= "時間：" . date('Y-m-d H:i:s') . "\n";

    $ok = send_system_mail($pdo, $sendToAll ? 'notification' : 'direct', $recipient['email'], $recipient['name'], $subject, $personalBody, $attachment);
    $ok ? $success++ : $failed++;
}

flash_set($failed === 0 ? 'success' : 'error', "寄信完成：成功 {$success} 封，失敗 {$failed} 封。詳細錯誤請看寄信紀錄。");
redirect('mailer_panel.php');
