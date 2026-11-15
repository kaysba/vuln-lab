<?php
require __DIR__ . '/config/bootstrap.php';

$level = get_level(); // read once, used for the switch and the cookie

// Safe helper: prepared SELECT returning all rows.
function post_rows(string $sql, string $types, array $params): array
{
    $stmt = db()->prepare($sql);
    if ($stmt === false) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

$postId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : null;

// Commenting requires a valid session user (a real, existing account).
$sessionId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$sessionOk = $sessionId > 0 && count(post_rows('SELECT id FROM users WHERE id = ?', 'i', [$sessionId])) === 1;

// Add a comment (POST). The body is stored RAW: filtering happens only when it is displayed.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = isset($_POST['post_id']) && is_string($_POST['post_id']) && ctype_digit($_POST['post_id'])
        ? (int) $_POST['post_id'] : 0;
    $body = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';

    if (!$sessionOk) {
        header('Location: /login.php');
        exit;
    }
    if ($targetId > 0 && $body !== '' && strlen($body) <= 5000
        && count(post_rows('SELECT id FROM posts WHERE id = ?', 'i', [$targetId])) === 1) {
        $stmt = db()->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)');
        if ($stmt !== false) {
            $stmt->bind_param('iis', $targetId, $sessionId, $body);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Post/Redirect/Get: avoids resubmitting the form on refresh.
    header('Location: /post.php' . ($targetId > 0 ? '?id=' . $targetId : ''));
    exit;
}

// The flag lives in a NON-HttpOnly cookie: only JavaScript running on this page can read it.
// It is never written in the page output. Only the current level's cookie exists.
// (Intentionally not HttpOnly, on every level: this is what makes the XSS worth exploiting.)
setrawcookie('xss_stored_flag', flag_for('xss-stored', $level), [
    'expires'  => 0,
    'path'     => '/',
    'httponly' => false,
    'samesite' => 'Lax',
]);

$posts = [];
$post = null;
$comments = [];
if ($postId === null) {
    $posts = post_rows(
        'SELECT p.id, p.title, p.created_at, u.username
         FROM posts p JOIN users u ON u.id = p.user_id
         ORDER BY p.created_at DESC, p.id DESC',
        '',
        []
    );
} else {
    $rows = post_rows(
        'SELECT p.id, p.title, p.body, p.created_at, u.username
         FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?',
        'i',
        [$postId]
    );
    $post = $rows[0] ?? null;
    if ($post !== null) {
        $comments = post_rows(
            'SELECT c.id, c.body, c.created_at, u.username
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ? ORDER BY c.created_at ASC, c.id ASC',
            'i',
            [$postId]
        );
    }
}

// Display-time filtering of a comment body. The stored value is always raw, so a payload
// planted at one level is filtered again by whatever level is active when it is viewed.
function render_comment(string $body, string $level): string
{
    switch ($level) {
        case 'easy':
            // VULN: stored XSS - level easy (comment body rendered raw)
            return $body;

        case 'medium':
            // VULN: stored XSS - level medium (single-pass blacklist removes <script tags only)
            return str_ireplace(['<script', '</script'], '', $body);

        case 'hard':
        default:
            // VULN: stored XSS - level hard (allowlist of tags via strip_tags keeps attributes on allowed tags)
            return strip_tags($body, '<a><b><i><strong><em><code><br>');
    }
}

$levelDesc = [
    'easy'   => 'Comments are stored as typed. Displayed as stored.',
    'medium' => 'A filter stands guard. Only one kind of tag is on its list.',
    'hard'   => 'Only approved tags get through. Approved tags keep their baggage.',
];

$pageTitle = 'Posts';
$activeNav = 'posts';
require __DIR__ . '/includes/header.php';
?>
<h1>Posts</h1>
<p>Threat level: <span class="lvl-text lvl-text-<?php echo e($level); ?>"><?php echo e(strtoupper($level)); ?></span>
    <span class="dim">// <?php echo e($levelDesc[$level]); ?></span></p>
<p class="dim">A session cookie is set on this page.</p>

<?php if ($postId === null): ?>
    <?php if (!$posts): ?>
        <p class="dim">No posts yet.</p>
    <?php endif; ?>
    <?php foreach ($posts as $p): ?>
        <article class="post">
            <div class="who"><?php echo e($p['username']); ?><small><?php echo e($p['created_at']); ?></small></div>
            <div><a href="/post.php?id=<?php echo (int) $p['id']; ?>"><?php echo e($p['title']); ?></a></div>
        </article>
    <?php endforeach; ?>
<?php elseif ($post === null): ?>
    <p><a href="/post.php">&lt; back to posts</a></p>
    <p class="msg err" role="alert">NOT FOUND: no such post.</p>
<?php else: ?>
    <p><a href="/post.php">&lt; back to posts</a></p>
    <article class="post">
        <div class="who"><?php echo e($post['username']); ?><small><?php echo e($post['created_at']); ?></small></div>
        <div>
            <b><?php echo e($post['title']); ?></b><br>
            <?php echo nl2br(e($post['body'])); ?>
        </div>
    </article>

    <h2>Comments</h2>
    <?php if (!$comments): ?>
        <p class="dim">No comments yet.</p>
    <?php endif; ?>
    <?php foreach ($comments as $c): ?>
        <article class="post comment">
            <div class="who"><?php echo e($c['username']); ?><small><?php echo e($c['created_at']); ?></small></div>
            <div><?php echo render_comment($c['body'], $level); ?></div>
        </article>
    <?php endforeach; ?>

    <h2>Add a comment</h2>
    <?php if ($sessionOk): ?>
        <form method="post" action="/post.php" class="comment-form">
            <input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
            <label for="body">Comment</label>
            <textarea id="body" name="body" rows="4" maxlength="5000" required></textarea>
            <button type="submit">[POST COMMENT]</button>
        </form>
    <?php else: ?>
        <p class="dim">Log in to comment. <a href="/login.php">[LOGIN]</a></p>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
