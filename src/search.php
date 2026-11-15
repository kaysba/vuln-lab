<?php
require __DIR__ . '/config/bootstrap.php';

$level = get_level(); // read once, used for the switch and the cookie

// The flag lives in a NON-HttpOnly cookie: only JavaScript running on this page can read it.
// It is never written in the page output. Only the current level's cookie exists.
// (Intentionally not HttpOnly, on every level: this is what makes the XSS worth exploiting.)
setrawcookie('xss_reflected_flag', flag_for('xss-reflected', $level), [
    'expires'  => 0,
    'path'     => '/',
    'httponly' => false,
    'samesite' => 'Lax',
]);

$q = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : '';
$searched = $q !== '';

// Posts search is safe: prepared statement, and results are escaped with e() on output.
$results = [];
if ($searched) {
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $stmt = db()->prepare(
        'SELECT p.id, p.title, p.body, p.created_at, u.username
         FROM posts p JOIN users u ON u.id = p.user_id
         WHERE p.title LIKE ? OR p.body LIKE ?
         ORDER BY p.created_at DESC, p.id DESC'
    );
    if ($stmt !== false) {
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $results = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

// How the search term is reflected back, per level.
$termHtml = '';  // goes in the HTML body ("You searched for")
$termAttr = '';  // goes in the single-quoted value attribute of the search input
switch ($level) {
    case 'easy':
        // VULN: reflected XSS - level easy (search term echoed raw into HTML body)
        $termHtml = $q;
        $termAttr = $q;
        break;

    case 'medium':
        // VULN: reflected XSS - level medium (single-pass blacklist removes <script tags only)
        $termHtml = str_ireplace(['<script', '</script'], '', $q);
        $termAttr = $termHtml;
        break;

    case 'hard':
    default:
        $termHtml = e($q); // body context is correctly escaped
        // VULN: reflected XSS - level hard (attribute context, single quotes not escaped)
        $termAttr = htmlspecialchars($q, ENT_COMPAT);
        break;
}

$levelDesc = [
    'easy'   => 'Whatever you type comes straight back.',
    'medium' => 'A filter stands guard. Only one kind of tag is on its list.',
    'hard'   => 'The page text is clean. Check every place your input lands.',
];

$pageTitle = 'Search';
$activeNav = 'search';
require __DIR__ . '/includes/header.php';
?>
<h1>Search</h1>
<p>Threat level: <span class="lvl-text lvl-text-<?php echo e($level); ?>"><?php echo e(strtoupper($level)); ?></span>
    <span class="dim">// <?php echo e($levelDesc[$level]); ?></span></p>
<p class="dim">A session cookie is set on this page.</p>

<form method="get" action="/search.php" class="search-form">
    <label for="q">Query</label>
    <input type="text" id="q" name="q" value='<?php echo $termAttr; ?>' placeholder="search the forum" autocomplete="off">
    <button type="submit">[SEARCH]</button>
</form>

<?php if ($searched): ?>
    <p class="search-term">You searched for: <b><?php echo $termHtml; ?></b></p>
    <?php if (!$results): ?>
        <p class="dim">No posts found.</p>
    <?php endif; ?>
    <?php foreach ($results as $p): ?>
        <article class="post">
            <div class="who"><?php echo e($p['username']); ?><small><?php echo e($p['created_at']); ?></small></div>
            <div>
                <b><?php echo e($p['title']); ?></b><br>
                <?php echo nl2br(e($p['body'])); ?>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
