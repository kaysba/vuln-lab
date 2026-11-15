<?php
// Expected variables: $pageTitle, $activeNav. Requires bootstrap.php.
$pageTitle = $pageTitle ?? 'vuln-lab';
$activeNav = $activeNav ?? '';
$level = get_level();
$flagCount = 0;
foreach (solved_map() as $solvedLevels) {
    $flagCount += count($solvedLevels);
}
$docroot = dirname(__DIR__);
$navItems = [
    ['1', 'feed',     'FEED',     '/index.php',       'index.php'],
    ['2', 'login',    'LOGIN', '/login.php',       'login.php'],
    ['3', 'search',   'SEARCH', '/search.php',      'search.php'],
    ['4', 'messages', 'MESSAGES',  '/messages.php',    'messages.php'],
    ['5', 'posts',    'POSTS',     '/post.php',        'post.php'],
    ['6', 'admin',    'ADMIN',     '/admin/index.php', 'admin/index.php'],
    ['F', 'flags',    'FLAGS',     '/flags.php',       'flags.php'],
];
$levelLabels = ['easy' => 'EASY', 'medium' => 'MEDIUM', 'hard' => 'HARD'];
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> - vuln-lab</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' fill='%230b0f0c'/%3E%3Cpath d='M3 4l4 4-4 4' stroke='%2339ff88' stroke-width='2' fill='none'/%3E%3Crect x='8' y='11' width='5' height='2' fill='%2339ff88'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&amp;family=Silkscreen&amp;display=swap">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/ui.js" defer></script>
</head>
<body>
<header class="top">
    <a class="brand" href="/index.php" aria-label="vuln-lab, home">
        <span class="brand-name">VULN-LAB</span>
        <span class="brand-sub">// operator console</span>
    </a>
    <div class="defcon">
        <div class="t" id="threat-label">THREAT LEVEL</div>
        <form method="post" action="/level.php" class="gauge" aria-labelledby="threat-label">
            <input type="hidden" name="return" value="<?php echo e($requestUri); ?>">
            <?php foreach (LEVELS as $l): ?>
                <button type="submit" name="level" value="<?php echo e($l); ?>"
                        class="lvl lvl-<?php echo e($l); ?><?php echo $l === $level ? ' on' : ''; ?>"
                        aria-pressed="<?php echo $l === $level ? 'true' : 'false'; ?>"><?php echo e($levelLabels[$l]); ?></button>
            <?php endforeach; ?>
        </form>
    </div>
    <div class="status">
        <button type="button" id="snd-toggle" class="snd" aria-pressed="true">[SND: ON]</button>
        <div class="flags">FLAGS <?php echo sprintf('%02d', $flagCount); ?>/15</div>
    </div>
</header>
<nav class="menu" aria-label="Main navigation">
    <?php foreach ($navItems as [$key, $id, $label, $href, $file]): ?>
        <?php if (file_exists($docroot . '/' . $file)): ?>
            <a class="item<?php echo $id === $activeNav ? ' on' : ''; ?>" href="<?php echo e($href); ?>"<?php echo $id === $activeNav ? ' aria-current="page"' : ''; ?>><span class="n">[<?php echo e($key); ?>]</span> <?php echo e($label); ?></a>
        <?php else: ?>
            <span class="item off" aria-disabled="true"><span class="n">[<?php echo e($key); ?>]</span> <?php echo e($label); ?> <span class="tag">OFFLINE</span></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<main>
