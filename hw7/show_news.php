<?php
// show_news.php
// 顯示單篇貼文與留言。留言會顯示會員暱稱、大頭貼，背景套用使用者喜歡的顏色。
require_once __DIR__ . '/functions.php';
start_session_once();
$user = current_user($pdo);

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($newsId <= 0) {
    exit('無效的討論 ID。<br><a href="index.php">返回首頁</a>');
}

$stmt = $pdo->prepare('
    SELECT n.id, n.title, n.content, n.created_at,
           u.nickname, u.avatar, u.favorite_color
    FROM news n
    JOIN users u ON u.id = n.user_id
    WHERE n.id = ?
    LIMIT 1
');
$stmt->execute([$newsId]);
$post = $stmt->fetch();

if (!$post) {
    exit('找不到此討論。<br><a href="index.php">返回首頁</a>');
}

$stmt = $pdo->prepare('
    SELECT r.id, r.content, r.created_at,
           u.nickname, u.avatar, u.favorite_color
    FROM replies r
    JOIN users u ON u.id = r.user_id
    WHERE r.news_id = ?
    ORDER BY r.created_at ASC
');
$stmt->execute([$newsId]);
$replies = $stmt->fetchAll();

page_header($post['title'], $user);
?>
<section class="thread-wrap">
    <?php if ($msg = flash_get('success')): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert error"><?= h($msg) ?></div><?php endif; ?>

    <a href="index.php" class="back-link">← 回到論壇列表</a>

    <article class="pixel-card thread-card" style="--user-color: <?= h(safe_color($post['favorite_color'])) ?>">
        <div class="post-meta-row">
            <span class="big-avatar" style="background: <?= h(safe_color($post['favorite_color'])) ?>"><?= avatar_icon($post['avatar']) ?></span>
            <div>
                <strong><?= h($post['nickname']) ?></strong>
                <p class="muted"><?= h($post['created_at']) ?></p>
            </div>
        </div>
        <h1><?= h($post['title']) ?></h1>
        <div class="post-body"><?= nl2br(h($post['content'])) ?></div>
    </article>

    <section class="pixel-card replies-card">
        <div class="card-title">💬 留言 <?= count($replies) ?></div>
        <?php if (!$replies): ?>
            <p class="muted">目前還沒有人留言。第一支火把等你插上。</p>
        <?php endif; ?>

        <?php foreach ($replies as $index => $reply): ?>
            <article class="reply-block" style="background: <?= h(safe_color($reply['favorite_color'])) ?>">
                <div class="reply-head">
                    <span class="mini-avatar"><?= avatar_icon($reply['avatar']) ?></span>
                    <strong><?= h($reply['nickname']) ?></strong>
                    <span class="floor">B<?= $index + 1 ?></span>
                    <span class="reply-time"><?= h($reply['created_at']) ?></span>
                </div>
                <div class="reply-content"><?= nl2br(h($reply['content'])) ?></div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="pixel-card reply-form-card">
        <div class="card-title">新增留言</div>
        <?php if ($user): ?>
            <form action="post_reply.php" method="post" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="news_id" value="<?= (int)$newsId ?>">
                <textarea name="content" rows="5" maxlength="<?= MAX_POST_LENGTH ?>" placeholder="留下你的方塊觀點..." required></textarea>
                <button class="btn" type="submit">送出留言</button>
            </form>
        <?php else: ?>
            <p class="muted">請先登入才能留言。</p>
            <a class="btn small" href="login.php">登入</a>
        <?php endif; ?>
    </section>
</section>
<?php page_footer(); ?>
