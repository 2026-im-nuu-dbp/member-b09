<?php
// register.php
// 顯示會員註冊頁。
// 欄位符合 README：帳號、密碼、暱稱、喜歡顏色、大頭貼圖案。

require_once __DIR__ . '/functions.php';

start_session_once();

if (is_logged_in()) {
    redirect('index.php');
}

$error = $_GET['error'] ?? flash_get('error');

$colors = color_options();
$avatars = avatar_options();

page_header('註冊');
?>

<section class="auth-wrap">
    <div class="pixel-card auth-card wide-auth">
        <div class="card-title">▣ 建立新玩家</div>
        <p class="muted">註冊資料會直接套用到論壇留言：暱稱、大頭貼、留言背景色。</p>

        <?php if ($error): ?>
            <div class="alert error"><?= h($error) ?></div>
        <?php endif; ?>

        <form action="register_process.php" method="post" class="form-stack">
            <?= csrf_field() ?>

            <div class="form-grid">
                <label>
                    帳號
                    <input
                        type="text"
                        name="username"
                        maxlength="50"
                        pattern="[A-Za-z0-9_]{3,50}"
                        required
                    >
                    <small>英文、數字、底線，至少 3 個字。</small>
                </label>

                <label>
                    Email
                    <input
                        type="email"
                        name="email"
                        maxlength="255"
                        required
                    >
                    <small></small>
                </label>

                <label>
                    密碼
                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        required
                    >
                    <small>至少 8 個字元。</small>
                </label>

                <label>
                    暱稱
                    <input
                        type="text"
                        name="nickname"
                        maxlength="40"
                        required
                    >
                    <small></small>
                </label>
            </div>

            <div class="section-label">喜歡顏色</div>
            <div class="choice-grid color-grid">
                <?php foreach ($colors as $value => $label): ?>
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="favorite_color"
                            value="<?= h($value) ?>"
                            <?= $value === '#c8e6a0' ? 'checked' : '' ?>
                        >
                        <span class="color-dot" style="background: <?= h($value) ?>"></span>
                        <span><?= h($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="section-label">大頭貼圖案</div>
            <div class="choice-grid avatar-grid">
                <?php foreach ($avatars as $key => $icon): ?>
                    <label class="choice-card avatar-choice">
                        <input
                            type="radio"
                            name="avatar"
                            value="<?= h($key) ?>"
                            <?= $key === 'steve' ? 'checked' : '' ?>
                        >
                        <span class="avatar-preview"><?= $icon ?></span>
                        <span><?= h($key) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button class="btn" type="submit">加入伺服器</button>
        </form>

        <p class="footer-link">
            已有帳號？<a href="login.php">回到登入</a>
        </p>
    </div>
</section>

<?php page_footer(); ?>