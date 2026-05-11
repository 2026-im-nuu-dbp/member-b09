<?php
// mailer_panel.php
// 寄信系統 UI：可寄給單一信箱，也可由管理員寄給所有啟用會員，並強制加入老師測試信箱。
require_once __DIR__ . '/mailer.php';
start_session_once();
require_login();
$user = current_user($pdo);
$isAdmin = $user && $user['role'] === 'admin';

$logs = $pdo->query('SELECT id, email_type, recipient_email, recipient_name, subject, status, error_message, saved_path, created_at, sent_at FROM email_logs ORDER BY id DESC LIMIT 30')->fetchAll();
page_header('寄信系統', $user);
?>
<section class="mailer-wrap">
    <?php if ($msg = flash_get('success')): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert error"><?= h($msg) ?></div><?php endif; ?>

    <div class="pixel-card">
        <div class="card-title">通知系統</div>
        <p class="muted">目前模式：<strong><?= h(MAIL_DRIVER) ?></strong></p>
        <form action="send_notification.php" method="post" enctype="multipart/form-data" class="form-stack">
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>收件人 Email
                    <input type="email" name="to_email" placeholder="someone@example.com" <?= $isAdmin ? '' : 'required' ?>>
                    <small>一般使用者只能寄給這裡填寫的信箱。</small>
                </label>
                <label>收件人名稱
                    <input type="text" name="to_name" maxlength="100" placeholder="例如：測試者">
                </label>
            </div>

            <?php if ($isAdmin): ?>
                <label class="checkbox-row">
                    <input type="checkbox" name="send_to_all" value="1">
                    管理員：寄給所有啟用會員，並強制加入老師測試信箱 <?= h(TEACHER_TEST_EMAIL) ?>
                </label>
            <?php endif; ?>

            <label>信件主旨
                <input type="text" name="subject" maxlength="255" required placeholder="例如：hw7 Demo 通知">
            </label>
            <label>信件內容
                <textarea name="body" rows="8" required placeholder="輸入通知內容..."></textarea>
            </label>
            <label>附加圖片，選填
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <small>允許 jpg、png、gif、webp，大小上限 <?= (int)(MAX_IMAGE_SIZE / 1024 / 1024) ?>MB。</small>
            </label>
            <button class="btn" type="submit">送出信件</button>
        </form>
    </div>

    <div class="pixel-card table-card">
        <div class="card-title">最近 30 筆寄信紀錄</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>類型</th><th>收件人</th><th>主旨</th><th>狀態</th><th>時間</th><th>錯誤</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>#<?= (int)$log['id'] ?></td>
                            <td><?= h($log['email_type']) ?></td>
                            <td><?= h(($log['recipient_name'] ?: '-') . ' <' . $log['recipient_email'] . '>') ?></td>
                            <td><?= h($log['subject']) ?></td>
                            <td><span class="badge <?= h($log['status']) ?>"><?= h($log['status']) ?></span></td>
                            <td><?= h($log['sent_at'] ?: $log['created_at']) ?></td>
                            <td><?= h($log['error_message'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$logs): ?><tr><td colspan="7">目前沒有寄信紀錄。</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php page_footer(); ?>
