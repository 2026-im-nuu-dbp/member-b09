<?php
// profile.php
// 玩家可以修改自己的暱稱、Email、顏色、大頭貼、喜歡玩法、生態域與生物。

require_once __DIR__ . '/functions.php';

start_session_once();
require_login();

$user = current_user($pdo);

if (!$user) {
    flash_set('error', '找不到目前登入的使用者，請重新登入。');
    redirect('login.php');
}

// 統一整理使用者資料，避免 favorite_biomes 是 null 或字串造成 in_array() 錯誤。
function normalize_profile_user(array $user): array
{
    // 如果資料庫沒有 favorite_playstyle，給預設值。
    $user['favorite_playstyle'] = $user['favorite_playstyle'] ?? 'builder';

    // favorite_biomes 在資料庫中是 JSON 字串，所以畫面使用前要轉成 PHP 陣列。
    if (!isset($user['favorite_biomes']) || $user['favorite_biomes'] === '' || $user['favorite_biomes'] === null) {
        $user['favorite_biomes'] = [];
    } elseif (is_string($user['favorite_biomes'])) {
        $decoded = json_decode($user['favorite_biomes'], true);

        if (is_array($decoded)) {
            $user['favorite_biomes'] = $decoded;
        } else {
            $user['favorite_biomes'] = [];
        }
    } elseif (!is_array($user['favorite_biomes'])) {
        $user['favorite_biomes'] = [];
    }

    return $user;
}

$user = normalize_profile_user($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nickname = trim($_POST['nickname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $favoriteColor = safe_color($_POST['favorite_color'] ?? '#c8e6a0');
    $avatar = $_POST['avatar'] ?? 'steve';
    $favoritePlaystyle = $_POST['favorite_playstyle'] ?? 'builder';

    // checkbox 如果完全沒勾，$_POST['favorite_biomes'] 不會存在，所以要給空陣列。
    $favoriteBiomesArray = $_POST['favorite_biomes'] ?? [];

    if (!is_array($favoriteBiomesArray)) {
        $favoriteBiomesArray = [];
    }

    // 只保留系統允許的生態域選項，避免使用者竄改表單送奇怪資料進資料庫。
    $allowedBiomes = array_keys(biome_options());
    $favoriteBiomesArray = array_values(array_intersect($favoriteBiomesArray, $allowedBiomes));

    $favoriteBiomesJson = json_encode($favoriteBiomesArray, JSON_UNESCAPED_UNICODE);

    if ($nickname === '' || mb_strlen($nickname) > 40) {
        flash_set('error', '暱稱不可空白，且最多 40 字。');
        redirect('profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Email 格式不正確。');
        redirect('profile.php');
    }

    if (!array_key_exists($avatar, avatar_options())) {
        $avatar = 'steve';
    }

    if (!array_key_exists($favoritePlaystyle, playstyle_options())) {
        $favoritePlaystyle = 'builder';
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $stmt->execute([$email, $user['id']]);

    if ($stmt->fetch()) {
        flash_set('error', '這個 Email 已被其他會員使用。');
        redirect('profile.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET nickname = ?,
             email = ?,
             favorite_color = ?,
             avatar = ?,
             favorite_playstyle = ?,
             favorite_biomes = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $nickname,
        $email,
        $favoriteColor,
        $avatar,
        $favoritePlaystyle,
        $favoriteBiomesJson,
        $user['id']
    ]);

    flash_set('success', '個人資料已更新。');
    redirect('profile.php');
}

// 更新後重新取得使用者資料，並再次整理 favorite_biomes。
$user = current_user($pdo);

if (!$user) {
    flash_set('error', '找不到目前登入的使用者，請重新登入。');
    redirect('login.php');
}

$user = normalize_profile_user($user);

page_header('個人資料', $user);
?>

<section class="settings-wrap">
    <div class="pixel-card auth-card wide-auth">
        <div class="card-title">玩家資料設定</div>

        <?php if ($msg = flash_get('success')): ?>
            <div class="alert success"><?= h($msg) ?></div>
        <?php endif; ?>

        <?php if ($msg = flash_get('error')): ?>
            <div class="alert error"><?= h($msg) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>

            <div class="form-grid">
                <label>帳號
                    <input type="text" value="<?= h($user['username']) ?>" disabled>
                </label>

                <label>角色
                    <input type="text" value="<?= h($user['role']) ?>" disabled>
                </label>

                <label>暱稱
                    <input type="text" name="nickname" value="<?= h($user['nickname']) ?>" maxlength="40" required>
                </label>

                <label>Email
                    <input type="email" name="email" value="<?= h($user['email']) ?>" maxlength="255" required>
                </label>
            </div>

            <label>喜歡顏色</label>
            <div class="choice-grid color-grid">
                <?php foreach (color_options() as $value => $label): ?>
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="favorite_color"
                            value="<?= h($value) ?>"
                            <?= $user['favorite_color'] === $value ? 'checked' : '' ?>
                        >
                        <span class="color-dot" style="background: <?= h($value) ?>"></span>
                        <span><?= h($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>喜歡的 Minecraft 玩法</label>
            <div class="choice-grid playstyle-grid">
                <?php foreach (playstyle_options() as $value => $label): ?>
                    <label class="choice-card">
                        <input
                            type="radio"
                            name="favorite_playstyle"
                            value="<?= h($value) ?>"
                            <?= $user['favorite_playstyle'] === $value ? 'checked' : '' ?>
                        >
                        <span><?= h($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>喜歡的生態域及生物</label>
            <div class="choice-grid biome-grid">
                <?php foreach (biome_options() as $value => $label): ?>
                    <label class="choice-card">
                        <input
                            type="checkbox"
                            name="favorite_biomes[]"
                            value="<?= h($value) ?>"
                            <?= in_array($value, $user['favorite_biomes'], true) ? 'checked' : '' ?>
                        >
                        <span><?= h($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>大頭貼圖案</label>
            <div class="choice-grid avatar-grid">
                <?php foreach (avatar_options() as $key => $icon): ?>
                    <label class="choice-card avatar-choice">
                        <input
                            type="radio"
                            name="avatar"
                            value="<?= h($key) ?>"
                            <?= $user['avatar'] === $key ? 'checked' : '' ?>
                        >
                        <span class="avatar-preview"><?= $icon ?></span>
                        <span><?= h($key) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button class="btn" type="submit">儲存玩家資料</button>
        </form>
    </div>
</section>

<?php page_footer(); ?>