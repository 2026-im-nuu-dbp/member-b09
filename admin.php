<?php
// admin.php
// 管理員介面：新增、修改、刪除會員資料。
require_once __DIR__ . '/functions.php';
start_session_once();
require_admin($pdo);
$user = current_user($pdo);

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editUser = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, username, email, nickname, favorite_color, avatar, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

$users = $pdo->query('SELECT id, username, email, nickname, favorite_color, avatar, role, status, created_at FROM users ORDER BY id ASC')->fetchAll();
page_header('管理員', $user);
?>
<section class="admin-wrap">
    <?php if ($msg = flash_get('success')): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert error"><?= h($msg) ?></div><?php endif; ?>

    <div class="pixel-card">
        <div class="card-title"><?= $editUser ? '修改會員' : '新增會員' ?></div>
        <form action="admin_user_save.php" method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= h($editUser['id'] ?? 0) ?>">
            <div class="form-grid">
                <label>帳號
                    <input type="text" name="username" maxlength="50" pattern="[A-Za-z0-9_]{3,50}" value="<?= h($editUser['username'] ?? '') ?>" required>
                </label>
                <label>Email
                    <input type="email" name="email" maxlength="255" value="<?= h($editUser['email'] ?? '') ?>" required>
                </label>
                <label>暱稱
                    <input type="text" name="nickname" maxlength="40" value="<?= h($editUser['nickname'] ?? '') ?>" required>
                </label>
                <label>密碼 <?= $editUser ? '<small>不改請留空</small>' : '' ?>
                    <input type="password" name="password" minlength="8" <?= $editUser ? '' : 'required' ?>>
                </label>
                <label>角色
                    <select name="role">
                        <option value="user" <?= ($editUser['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>user</option>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>admin</option>
                    </select>
                </label>
                <label>狀態
                    <select name="status">
                        <option value="active" <?= ($editUser['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>active</option>
                        <option value="locked" <?= ($editUser['status'] ?? '') === 'locked' ? 'selected' : '' ?>>locked</option>
                    </select>
                </label>
                <label>喜歡顏色
                    <select name="favorite_color">
                        <?php foreach (color_options() as $value => $label): ?>
                            <option value="<?= h($value) ?>" <?= ($editUser['favorite_color'] ?? '#c8e6a0') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>大頭貼
                    <select name="avatar">
                        <?php foreach (avatar_options() as $key => $icon): ?>
                            <option value="<?= h($key) ?>" <?= ($editUser['avatar'] ?? 'steve') === $key ? 'selected' : '' ?>><?= $icon ?> <?= h($key) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="button-row">
                <button class="btn" type="submit"><?= $editUser ? '更新會員' : '新增會員' ?></button>
                <?php if ($editUser): ?><a class="btn secondary" href="admin.php">取消修改</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="pixel-card table-card">
        <div class="card-title">會員列表</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>玩家</th><th>帳號</th><th>Email</th><th>角色</th><th>狀態</th><th>建立時間</th><th>操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $member): ?>
                    <tr>
                        <td>#<?= (int)$member['id'] ?></td>
                        <td><span class="mini-avatar" style="background: <?= h(safe_color($member['favorite_color'])) ?>"><?= avatar_icon($member['avatar']) ?></span> <?= h($member['nickname']) ?></td>
                        <td><?= h($member['username']) ?></td>
                        <td><?= h($member['email']) ?></td>
                        <td><span class="badge"><?= h($member['role']) ?></span></td>
                        <td><span class="badge <?= h($member['status']) ?>"><?= h($member['status']) ?></span></td>
                        <td><?= h($member['created_at']) ?></td>
                        <td class="actions">
                            <a href="admin.php?edit=<?= (int)$member['id'] ?>">修改</a>
                            <?php if ((int)$member['id'] !== (int)$user['id']): ?>
                                <form action="admin_user_delete.php" method="post" onsubmit="return confirm('確定刪除這位會員？相關貼文與留言會一起刪除。');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
                                    <button type="submit" class="link-danger">刪除</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php page_footer(); ?>
