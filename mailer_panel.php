<?php
// mailer_panel.php
// 原本的一般寄信 UI 已移除。此頁只給管理員查看「註冊驗證信」寄送紀錄。

require_once __DIR__ . '/functions.php';
start_session_once();
require_admin($pdo);
$user = current_user($pdo);

$logs = $pdo->query('SELECT id, email_type, recipient_email, recipient_name, subject, status, error_message, saved_path, created_at, sent_at FROM email_logs ORDER BY id DESC LIMIT 50')->fetchAll();
$tokens = $pdo->query(
    'SELECT vt.id, u.username, u.nickname, u.email, vt.expires_at, vt.used_at, vt.created_at
     FROM verification_tokens vt
     JOIN users u ON u.id = vt.user_id
     ORDER BY vt.id DESC
     LIMIT 50'
)->fetchAll();

page_header('驗證信紀錄', $user);
?>
<section class="mailer-wrap">
    <?php if ($msg = flash_get('success')): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert error"><?= h($msg) ?></div><?php endif; ?>

    <div class="pixel-card">
        <div class="card-title">註冊驗證信系統</div>
        <p class="muted">一般通知寄信功能已移除。現在只有使用者註冊時會自動寄出驗證信。</p>
        <p class="muted">目前寄信模式：<strong><?= h(MAIL_DRIVER) ?></strong>，SMTP 主機：<strong><?= h(SMTP_HOST) ?>:<?= h((string)SMTP_PORT) ?></strong></p>
    </div>

    <div class="pixel-card table-card">
        <div class="card-title">最近 50 筆驗證 Token</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>會員</th><th>Email</th><th>建立時間</th><th>過期時間</th><th>使用時間</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                        <tr>
                            <td>#<?= (int)$token['id'] ?></td>
                            <td><?= h($token['nickname']) ?> / <?= h($token['username']) ?></td>
                            <td><?= h($token['email']) ?></td>
                            <td><?= h($token['created_at']) ?></td>
                            <td><?= h($token['expires_at']) ?></td>
                            <td><?= h($token['used_at'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tokens): ?><tr><td colspan="6">目前沒有驗證 token。</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pixel-card table-card">
        <div class="card-title">最近 50 筆驗證信寄送紀錄</div>
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
