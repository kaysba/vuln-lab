<?php
require __DIR__ . '/config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'HEAD' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed');
}

$result = null; // null | 'new' | 'again' | 'denied'
$hit = null;
if ($method === 'POST') {
    $submitted = $_POST['flag'] ?? '';
    $hit = is_string($submitted) ? find_flag($submitted) : null;
    if ($hit === null) {
        $result = 'denied';
    } else {
        $result = mark_solved($hit['module'], $hit['level']) ? 'new' : 'again';
    }
}

$solved = solved_map();
$total = 0;
foreach ($solved as $lv) {
    $total += count($lv);
}

$pageTitle = 'Flags';
$activeNav = 'flags';
require __DIR__ . '/includes/header.php';
?>
<h1>Flags</h1>

<?php if (flag_secret_is_default()): ?>
    <p class="flag-warn" role="alert">WARNING: FLAG_SECRET is still the default value. Set a real FLAG_SECRET in .env and restart the containers.</p>
<?php endif; ?>

<?php if ($result === 'new' || $result === 'again'): ?>
    <section id="flag-success" class="flag-panel ok"<?php echo $result === 'new' ? ' data-breakin="1"' : ''; ?> aria-live="polite">
        <div class="head">FLAG CAPTURED: <?php echo e(MODULES[$hit['module']]); ?> - <?php echo e(strtoupper($hit['level'])); ?></div>
        <?php if ($result === 'new'): ?>
            <div class="xp">+100 XP</div>
        <?php else: ?>
            <div class="dim">Already captured</div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<form method="post" action="/flags.php" class="flag-form" autocomplete="off">
    <label for="flag-input">&gt; submit flag</label>
    <input type="text" id="flag-input" name="flag" autocomplete="off" spellcheck="false" placeholder="FLAG{...}" maxlength="100" required<?php echo $result === 'denied' ? ' data-denied="1"' : ''; ?>>
    <button type="submit">SUBMIT</button>
</form>
<?php if ($result === 'denied'): ?>
    <p class="flag-denied" role="alert">ACCESS DENIED</p>
<?php endif; ?>

<h2 class="grid-title">Progress <span class="total"><?php echo sprintf('%02d', $total); ?>/15 captured</span></h2>
<table class="flag-grid">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Module</th>
            <?php foreach (LEVELS as $l): ?>
                <th scope="col" class="lvl-text lvl-text-<?php echo e($l); ?>"><?php echo e(strtoupper($l)); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php $n = 0; foreach (MODULES as $key => $label): $n++; ?>
            <tr>
                <td class="num"><?php echo e((string) $n); ?></td>
                <td><?php echo e($label); ?></td>
                <?php foreach (LEVELS as $l): ?>
                    <?php if (isset($solved[$key][$l])): ?>
                        <td class="cell solved">[ SOLVED ]</td>
                    <?php else: ?>
                        <td class="cell locked">[ LOCKED ]</td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
