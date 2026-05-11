<?php
// activate.php
// 使用者點擊驗證信連結後會進到這裡。驗證 token 正確、未過期、未使用，就啟用帳號。

require_once __DIR__ . '/functions.php';
start_session_once();

$token = trim($_GET['token'] ?? '');
$ok = false;
$title = '信箱驗證結果';
$message = '';

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $message = '驗證連結格式不正確。';
} else {
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        'SELECT vt.id, vt.user_id, vt.expires_at, vt.used_at, u.status
         FROM verification_tokens vt
         JOIN users u ON u.id = vt.user_id
         WHERE vt.token_hash = ?
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        $message = '驗證連結無效。';
    } elseif ($row['used_at'] !== null) {
        $message = '這個驗證連結已經使用過。';
    } elseif (strtotime($row['expires_at']) < time()) {
        $message = '驗證連結已過期，請重新註冊或請管理員協助。';
    } elseif ($row['status'] === 'locked') {
        $message = '這個帳號已被停用，無法完成驗證。';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE verification_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
            $stmt->execute([(int)$row['id']]);

            $stmt = $pdo->prepare('UPDATE users SET status = "active", email_verified_at = NOW() WHERE id = ?');
            $stmt->execute([(int)$row['user_id']]);

            $pdo->commit();
            $ok = true;
            $message = '信箱驗證成功，現在可以登入論壇。';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = '驗證失敗：' . $e->getMessage();
        }
    }
}

page_header($title);
?>
<section class="auth-wrap">
    <div class="pixel-card auth-card">
        <div class="card-title">▣ <?= h($title) ?></div>
        <div class="alert <?= $ok ? 'success' : 'error' ?>"><?= h($message) ?></div>
        <div class="button-row">
            <a class="btn" href="login.php">前往登入</a>
            <?php if (!$ok): ?>
                <a class="btn secondary" href="register.php">重新註冊</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php page_footer(); ?>
