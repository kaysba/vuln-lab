<?php
require __DIR__ . '/config/bootstrap.php';

$posts = [];
$res = db()->query(
    'SELECT p.id, p.title, p.body, p.created_at, u.username
     FROM posts p JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC, p.id DESC'
);
if ($res) {
    $posts = $res->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'News feed';
$activeNav = 'feed';
require __DIR__ . '/includes/header.php';
?>
<h1>News feed</h1>
<?php if (!$posts): ?>
    <p class="dim">No posts.</p>
<?php endif; ?>
<?php foreach ($posts as $p): ?>
    <article class="post">
        <div class="who"><?php echo e($p['username']); ?><small><?php echo e($p['created_at']); ?></small></div>
        <div>
            <b><?php echo e($p['title']); ?></b><br>
            <?php echo nl2br(e($p['body'])); ?>
        </div>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
