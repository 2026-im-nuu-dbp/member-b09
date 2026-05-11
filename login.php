<?php
// login.php
// 顯示登入表單。真正的帳密檢查在 login_process.php。
require_once __DIR__ . '/functions.php';
start_session_once();

if (is_logged_in()) {
    redirect('index.php');
}

$error = $_GET['error'] ?? flash_get('error');
$success = $_GET['success'] ?? flash_get('success');
page_header('登入');
?>
<section class="auth-wrap">
    <div class="pixel-card auth-card">
        <div class="card-title">▣ 玩家登入</div>
        <p class="muted">登入前必須先完成 Email 驗證。登入後才能發文與留言。</p>

        <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= h($success) ?></div><?php endif; ?>

        <form action="login_process.php" method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>帳號或 Email
                <input type="text" name="account" required autocomplete="username">
            </label>
            <label>密碼
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="btn" type="submit">登入伺服器</button>
        </form>

        <p class="footer-link">還沒有帳號？<a href="register.php">建立新玩家</a></p>
    </div>
</section>
<?php page_footer(); ?>
