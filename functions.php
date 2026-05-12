<?php
// functions.php
// 共用小工具：安全輸出、跳轉、登入檢查、CSRF、會員資料、驗證 token、Minecraft 主題資料。

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
        'SELECT id, username, nickname, email, favorite_color, avatar, role, status,
                email_verified_at, favorite_playstyle, favorite_biomes
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

function base_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function create_activation_token(PDO $pdo, int $userId): string
{
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);
    $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_HOURS * 3600);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    // 同一個帳號只保留最新未使用的驗證連結，避免舊信件還能被拿來開通。
    $stmt = $pdo->prepare('UPDATE verification_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare(
        'INSERT INTO verification_tokens (user_id, token_hash, expires_at, requested_ip, requested_ua)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $tokenHash, $expiresAt, $ip, $ua]);

    return $plainToken;
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
                <a class="<?= active_nav('profile.php') ?>" href="profile.php">個人資料</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="<?= active_nav('admin.php') ?>" href="admin.php">管理員</a>
                    <a class="<?= active_nav('mailer_panel.php') ?>" href="mailer_panel.php">驗證信紀錄</a>
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
<footer class="footer">hw7 Demo · Minecraft Forum · Email Verification</footer>
</body>
</html>
    <?php
}

function playstyle_options(): array
{
    return [
        'builder' => '建築師',
        'explorer' => '探險家',
        'redstoner' => '紅石工程師',
        'farmer' => '農夫',
        'fighter' => '戰士',
    ];
}

function biome_options(): array
{
    return [
        'plains' => '平原',
        'desert' => '沙漠',
        'jungle' => '叢林',
        'ocean' => '海洋',
        'nether' => '地獄',
        'end' => '終界',
    ];
}
