<?php
// index.php
// Dcard 網頁版風格的討論列表：左側分類、中間貼文卡片、右側系統狀態。
require_once __DIR__ . '/functions.php';
start_session_once();
$user = current_user($pdo);

$keyword = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($keyword !== '') {
    $where = 'WHERE n.title LIKE ? OR n.content LIKE ? OR u.nickname LIKE ?';
    $like = '%' . $keyword . '%';
    $params = [$like, $like, $like];
}

$sql = "
    SELECT n.id, n.title, n.content, n.created_at,
           u.nickname, u.avatar, u.favorite_color,
           COUNT(r.id) AS reply_count
    FROM news n
    JOIN users u ON u.id = n.user_id
    LEFT JOIN replies r ON r.news_id = n.id
    {$where}
    GROUP BY n.id, n.title, n.content, n.created_at, u.nickname, u.avatar, u.favorite_color
    ORDER BY n.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

page_header('論壇', $user);
?>
<aside class="sidebar left-panel">
    <div class="pixel-card side-card">
        <div class="side-title">伺服器看板</div>
        <a class="side-link active" href="index.php">全部貼文</a>
        <a class="side-link" href="index.php?q=問題">問題求救</a>
        <a class="side-link" href="index.php?q=分享">作品分享</a>
        <a class="side-link" href="index.php?q=公告">系統公告</a>
        <a class="side-link" href="index.php?q=交易">村民交易</a>
    </div>
</aside>

<section class="feed">
    <?php if ($msg = flash_get('success')): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert error"><?= h($msg) ?></div><?php endif; ?>

    <div class="pixel-card composer-card">
        <?php if ($user): ?>
            <form action="post.php" method="post" class="composer-form">
                <?= csrf_field() ?>
                <div class="composer-user">
                    <span class="big-avatar" style="background: <?= h($user['favorite_color']) ?>"><?= avatar_icon($user['avatar']) ?></span>
                    <div>
                        <strong><?= h($user['nickname']) ?></strong>
                        <p class="muted">像 Dcard 一樣發一篇討論，但長得比較方。</p>
                    </div>
                </div>
                <input type="text" name="title" maxlength="200" placeholder="標題：今天想討論哪個方塊？" required>
                <textarea name="content" rows="5" maxlength="<?= MAX_POST_LENGTH ?>" placeholder="內容：分享問題、想法、作品或作業 demo 說明..." required></textarea>
                <button class="btn" type="submit">發布貼文</button>
            </form>
        <?php else: ?>
            <div class="login-hint">
                <div class="big-avatar">🧱</div>
                <div>
                    <strong>登入後才能發文與留言</strong>
                    <p class="muted">會員資料會自動套用成暱稱、大頭貼與留言背景色。</p>
                    <a class="btn small" href="login.php">登入</a>
                    <a class="btn secondary small" href="register.php">註冊</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <form class="search-bar" method="get" action="index.php">
        <input type="text" name="q" value="<?= h($keyword) ?>" placeholder="搜尋貼文、內容或玩家暱稱">
        <button class="btn secondary" type="submit">搜尋</button>
    </form>

    <?php if (!$posts): ?>
        <div class="pixel-card empty-card">目前沒有貼文。這片草原還很安靜。</div>
    <?php endif; ?>

    <?php foreach ($posts as $post): ?>
        <?php $excerpt = mb_substr(trim($post['content']), 0, 120); ?>
        <article class="post-card pixel-card" style="--user-color: <?= h(safe_color($post['favorite_color'])) ?>">
            <a class="post-link" href="show_news.php?id=<?= (int)$post['id'] ?>">
                <div class="post-meta-row">
                    <span class="mini-avatar" style="background: <?= h(safe_color($post['favorite_color'])) ?>"><?= avatar_icon($post['avatar']) ?></span>
                    <span class="nickname"><?= h($post['nickname']) ?></span>
                    <span class="dot">·</span>
                    <span><?= h($post['created_at']) ?></span>
                </div>
                <h2><?= h($post['title']) ?></h2>
                <p><?= h($excerpt) ?><?= mb_strlen($post['content']) > 120 ? '...' : '' ?></p>
                <div class="post-actions">
                    <span>💬 <?= (int)$post['reply_count'] ?> 則留言</span>
                    <span>⛏️ 點擊進入討論串</span>
                </div>
            </a>
        </article>
    <?php endforeach; ?>
</section>

<aside class="sidebar right-panel">
    <div class="pixel-card side-card quest-board" data-quest-board>
        <div class="quest-board-head">
            <div>
                <div class="side-title compact-title">系統任務板</div>
                <p class="muted">點開任務可查看 demo 說明，也可以切換完成狀態。</p>
            </div>
            <span class="quest-level" data-quest-level>Lv. 0</span>
        </div>

        <div class="quest-progress-wrap" aria-label="任務完成進度">
            <div class="quest-progress-text">
                <span>完成進度</span>
                <strong data-quest-progress-text>0 / 0</strong>
            </div>
            <div class="quest-progress-track">
                <div class="quest-progress-bar" data-quest-progress-bar style="width: 0%"></div>
            </div>
        </div>

        <div class="quest-filter" role="group" aria-label="任務篩選">
            <button type="button" class="quest-filter-btn active" data-quest-filter="all">全部</button>
            <button type="button" class="quest-filter-btn" data-quest-filter="done">完成</button>
            <button type="button" class="quest-filter-btn" data-quest-filter="todo">待辦</button>
        </div>

        <div class="quest-items">
            <article class="quest-item" data-quest="auth" data-default-status="done">
                <div class="quest-main">
                    <button type="button" class="quest-check" data-quest-toggle aria-label="切換會員系統任務狀態">✓</button>
                    <button type="button" class="quest-open" data-quest-open aria-expanded="false">
                        <span class="quest-name">會員註冊 / 登入 / 登出</span>
                        <span class="quest-reward">+20 XP</span>
                    </button>
                    <span class="quest-badge" data-quest-badge>完成</span>
                </div>
                <div class="quest-detail" data-quest-detail hidden>
                    <p>展示帳號申請、密碼驗證、Session 登入狀態與登出流程。</p>
                    <a href="register.php">前往註冊</a>
                </div>
            </article>

            <article class="quest-item" data-quest="profile" data-default-status="done">
                <div class="quest-main">
                    <button type="button" class="quest-check" data-quest-toggle aria-label="切換玩家外觀任務狀態">✓</button>
                    <button type="button" class="quest-open" data-quest-open aria-expanded="false">
                        <span class="quest-name">暱稱 + 大頭貼顯示</span>
                        <span class="quest-reward">+15 XP</span>
                    </button>
                    <span class="quest-badge" data-quest-badge>完成</span>
                </div>
                <div class="quest-detail" data-quest-detail hidden>
                    <p>貼文與留言會讀取登入者暱稱和大頭貼，符合會員討論區要求。</p>
                    <a href="profile.php">編輯玩家資料</a>
                </div>
            </article>

            <article class="quest-item" data-quest="reply_color" data-default-status="done">
                <div class="quest-main">
                    <button type="button" class="quest-check" data-quest-toggle aria-label="切換留言背景色任務狀態">✓</button>
                    <button type="button" class="quest-open" data-quest-open aria-expanded="false">
                        <span class="quest-name">留言背景色套用</span>
                        <span class="quest-reward">+15 XP</span>
                    </button>
                    <span class="quest-badge" data-quest-badge>完成</span>
                </div>
                <div class="quest-detail" data-quest-detail hidden>
                    <p>留言卡片會依照玩家設定的喜歡顏色呈現，demo 時很容易看出效果。</p>
                </div>
            </article>

            <article class="quest-item" data-quest="smtp" data-default-status="done">
                <div class="quest-main">
                    <button type="button" class="quest-check" data-quest-toggle aria-label="切換 SMTP 任務狀態">✓</button>
                    <button type="button" class="quest-open" data-quest-open aria-expanded="false">
                        <span class="quest-name">Gmail SMTP 寄信</span>
                        <span class="quest-reward">+25 XP</span>
                    </button>
                    <span class="quest-badge" data-quest-badge>完成</span>
                </div>
                <div class="quest-detail" data-quest-detail hidden>
                    <p>管理員可以透過 Gmail SMTP 發送系統通知，並保留寄信紀錄。</p>
                    <a href="mailer_panel.php">打開寄信系統</a>
                </div>
            </article>

            <article class="quest-item" data-quest="admin" data-default-status="done">
                <div class="quest-main">
                    <button type="button" class="quest-check" data-quest-toggle aria-label="切換管理員任務狀態">✓</button>
                    <button type="button" class="quest-open" data-quest-open aria-expanded="false">
                        <span class="quest-name">管理員會員管理</span>
                        <span class="quest-reward">+25 XP</span>
                    </button>
                    <span class="quest-badge" data-quest-badge>完成</span>
                </div>
                <div class="quest-detail" data-quest-detail hidden>
                    <p>管理員可新增、修改、刪除會員資料，對應 README 的後台要求。</p>
                    <a href="admin.php">進入管理後台</a>
                </div>
            </article>
        </div>

        <button type="button" class="quest-reset" data-quest-reset>重置任務狀態</button>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const board = document.querySelector('[data-quest-board]');
    if (!board) return;

    const storageKey = 'minecraft_forum_quest_status_v1';
    const items = Array.from(board.querySelectorAll('[data-quest]'));
    const progressText = board.querySelector('[data-quest-progress-text]');
    const progressBar = board.querySelector('[data-quest-progress-bar]');
    const levelText = board.querySelector('[data-quest-level]');
    const filterButtons = Array.from(board.querySelectorAll('[data-quest-filter]'));
    const resetButton = board.querySelector('[data-quest-reset]');

    let savedStatus = {};
    let currentFilter = 'all';

    try {
        savedStatus = JSON.parse(localStorage.getItem(storageKey) || '{}');
    } catch (error) {
        savedStatus = {};
    }

    function getQuestStatus(item) {
        const key = item.dataset.quest;
        return savedStatus[key] || item.dataset.defaultStatus || 'todo';
    }

    function setQuestStatus(item, status) {
        const key = item.dataset.quest;
        savedStatus[key] = status;
        localStorage.setItem(storageKey, JSON.stringify(savedStatus));
        renderQuest(item);
        updateProgress();
        applyFilter();
    }

    function renderQuest(item) {
        const status = getQuestStatus(item);
        const check = item.querySelector('[data-quest-toggle]');
        const badge = item.querySelector('[data-quest-badge]');

        item.dataset.status = status;
        item.classList.toggle('is-done', status === 'done');

        if (check) {
            check.textContent = status === 'done' ? '✓' : '!';
            check.setAttribute('aria-pressed', status === 'done' ? 'true' : 'false');
        }

        if (badge) {
            badge.textContent = status === 'done' ? '完成' : '待辦';
        }
    }

    function updateProgress() {
        const doneCount = items.filter(function (item) {
            return getQuestStatus(item) === 'done';
        }).length;
        const total = items.length;
        const percent = total === 0 ? 0 : Math.round((doneCount / total) * 100);

        progressText.textContent = doneCount + ' / ' + total;
        progressBar.style.width = percent + '%';
        levelText.textContent = 'Lv. ' + doneCount;
    }

    function applyFilter() {
        items.forEach(function (item) {
            const status = getQuestStatus(item);
            const shouldShow = currentFilter === 'all' || status === currentFilter;
            item.hidden = !shouldShow;
        });
    }

    items.forEach(function (item) {
        renderQuest(item);

        const toggleButton = item.querySelector('[data-quest-toggle]');
        const openButton = item.querySelector('[data-quest-open]');
        const detail = item.querySelector('[data-quest-detail]');

        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                const nextStatus = getQuestStatus(item) === 'done' ? 'todo' : 'done';
                setQuestStatus(item, nextStatus);
            });
        }

        if (openButton && detail) {
            openButton.addEventListener('click', function () {
                const isOpen = !detail.hidden;
                detail.hidden = isOpen;
                openButton.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                item.classList.toggle('is-open', !isOpen);
            });
        }
    });

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            currentFilter = button.dataset.questFilter;
            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn === button);
            });
            applyFilter();
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            localStorage.removeItem(storageKey);
            savedStatus = {};
            items.forEach(renderQuest);
            updateProgress();
            applyFilter();
        });
    }

    updateProgress();
    applyFilter();
});
</script>

<?php page_footer(); ?>
