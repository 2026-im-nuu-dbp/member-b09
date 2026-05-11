<?php
// functions.php
// 放共用小工具：安全輸出、跳轉、登入檢查、CSRF、會員資料、Minecraft 主題資料。

require_once __DIR__ . '/db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function start_session_once(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string
{
    start_session_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_session_once();
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('CSRF 驗證失敗，請回上一頁重新送出。');
    }
}

function current_user_id(): ?int
{
    start_session_once();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user(PDO $pdo): ?array
{
    $id = current_user_id();

    if (!$id) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT 
            id,
            username,
            nickname,
            email,
            favorite_color,
            avatar,
            role,
            status,
            favorite_playstyle,
            favorite_biomes
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php?error=' . urlencode('請先登入才能使用論壇功能'));
    }
}

function require_admin(PDO $pdo): void
{
    require_login();
    $user = current_user($pdo);
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        exit('權限不足：只有管理員可以進入這個頁面。');
    }
}

function flash_set(string $type, string $message): void
{
    start_session_once();
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): string
{
    start_session_once();
    $message = $_SESSION['flash'][$type] ?? '';
    unset($_SESSION['flash'][$type]);
    return $message;
}

function avatar_options(): array
{
    return [
        'steve' => '🧑‍🌾',
        'alex' => '🧑‍🦰',
        'creeper' => '🟩',
        'diamond' => '💎',
        'pickaxe' => '⛏️',
        'tnt' => '🧨',
        'enderman' => '◼️',
        'zombie' => '🧟',
        'bee' => '🐝',
        'axolotl' => '🦎',
    ];
}

function avatar_icon(?string $avatar): string
{
    $options = avatar_options();
    return $options[$avatar ?? ''] ?? '🧱';
}

function color_options(): array
{
    return [
        '#c8e6a0' => '草地方塊綠',
        '#d2b48c' => '泥土方塊棕',
        '#b0bec5' => '石頭灰',
        '#90caf9' => '鑽石藍',
        '#ffcc80' => '火把橘',
        '#f48fb1' => '櫻花粉',
        '#ce93d8' => '紫水晶紫',
        '#a5d6a7' => '苦力怕綠',
    ];
}

function safe_color(string $color): string
{
    return array_key_exists($color, color_options()) ? $color : '#c8e6a0';
}

function active_nav(string $page): string
{
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page ? 'active' : '';
}

function page_header(string $title, ?array $user = null): void
{
    $appTitle = h(APP_NAME);
    $pageTitle = h($title . ' - ' . APP_NAME);
    $nickname = $user ? h($user['nickname']) : '';
    $avatar = $user ? avatar_icon($user['avatar']) : '🧱';
    ?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php"><span class="brand-cube">▣</span><?= $appTitle ?></a>
        <nav class="nav-links">
            <a class="<?= active_nav('index.php') ?>" href="index.php">論壇</a>
            <?php if ($user): ?>
                <a class="<?= active_nav('mailer_panel.php') ?>" href="mailer_panel.php">寄信系統</a>
                <a class="<?= active_nav('profile.php') ?>" href="profile.php">個人資料</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="<?= active_nav('admin.php') ?>" href="admin.php">管理員</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="user-box">
            <?php if ($user): ?>
                <span class="mini-avatar"><?= $avatar ?></span>
                <span><?= $nickname ?></span>
                <a class="small-link" href="logout.php">登出</a>
            <?php else: ?>
                <a class="small-link" href="login.php">登入</a>
                <a class="small-link primary" href="register.php">註冊</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="layout">
    <?php
}

function page_footer(): void
{
    ?>
</main>
<footer class="footer">hw7 Demo · SMTP / Forum / Comment System</footer>
</body>
</html>
    <?php
}

function playstyle_options() {
    return [
        'builder' => '建築師',
        'explorer' => '探險家',
        'redstoner' => '紅石工程師',
        'farmer' => '農夫',
        'fighter' => '戰士'
    ];
}

function biome_options() {
    return [
        'plains' => '平原',
        'desert' => '沙漠',
        'jungle' => '叢林',
        'ocean' => '海洋',
        'nether' => '地獄',
        'end' => '終界'
    ];
}

function options() {
    return [
        'steve' => '<img src="/assets/avatars/steve.png" alt="Steve">',
        'alex' => '<img src="/assets/avatars/alex.png" alt="Alex">',
        'creeper' => '<img src="/assets/avatars/creeper.png" alt="Creeper">',
        'zombie' => '<img src="/assets/avatars/zombie.png" alt="Zombie">',
        'enderman' => '<img src="/assets/avatars/enderman.png" alt="Enderman">'
    ];
}
