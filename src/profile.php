<?php
require __DIR__ . '/config/bootstrap.php';

// Safe helper: returns the row of a user by id, or null. Prepared statement.
function profile_user(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, username, email, role, bio, created_at FROM users WHERE id = ?');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? ($res->fetch_assoc() ?: null) : null;
    $stmt->close();
    return $row;
}

// Require a valid session user (must be a real, existing account).
$sessionId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$sessionUser = $sessionId > 0 ? profile_user($sessionId) : null;
if ($sessionUser === null) {
    header('Location: /login.php');
    exit;
}

$level = get_level(); // read once, used for the switch and the flag

$id = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : $sessionId;
$profile = profile_user($id);

$showPrivate = false;
$idorFlag = null;

if ($profile !== null) {
    switch ($level) {
        case 'easy':
            // VULN: IDOR - level easy (no ownership check, private email shown for any id)
            $showPrivate = true;
            break;

        case 'medium':
            // VULN: IDOR - level medium (ownership decided by a client-supplied "me" parameter, not the session)
            $claimed = isset($_GET['me']) && is_string($_GET['me']) && ctype_digit($_GET['me']) ? (int) $_GET['me'] : $sessionId;
            $showPrivate = ($claimed === (int) $profile['id']);
            break;

        case 'hard':
        default:
            // Correct check: the private field is only shown to its owner (session identity).
            // The incomplete endpoint of this module lives in messages.php.
            $showPrivate = ((int) $profile['id'] === $sessionId);
            break;
    }

    // Flag only when the private data of a DIFFERENT real user is actually displayed.
    if ($showPrivate && (int) $profile['id'] !== $sessionId) {
        $idorFlag = flag_for('idor', $level);
    }
}

$members = [];
$mres = db()->query('SELECT id, username FROM users ORDER BY id');
if ($mres) {
    $members = $mres->fetch_all(MYSQLI_ASSOC);
}

$levelDesc = [
    'easy'   => 'Profiles are public. Obviously.',
    'medium' => 'The page asks the browser who you are. The browser is honest.',
    'hard'   => 'Profiles got a proper owner check. Look elsewhere.',
];
$meParam = $level === 'medium' ? '&me=' . $sessionId : '';

$pageTitle = 'Profile';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<h1>Profile</h1>
<p>Threat level: <span class="lvl-text lvl-text-<?php echo e($level); ?>"><?php echo e(strtoupper($level)); ?></span>
    <span class="dim">// <?php echo e($levelDesc[$level]); ?></span></p>

<?php if ($idorFlag !== null): ?>
    <section id="idor-flag" class="flag-panel ok" data-breakin="1" aria-live="polite">
        <div class="head">PRIVATE DATA OF ANOTHER USER EXPOSED: <?php echo e(strtoupper($level)); ?></div>
        <div>Flag: <code><?php echo e($idorFlag); ?></code></div>
        <div class="dim">Submit it on the flags page to record the capture.</div>
    </section>
<?php endif; ?>

<?php if ($profile === null): ?>
    <p class="msg err" role="alert">NOT FOUND: no such user.</p>
<?php else: ?>
    <article class="post">
        <div class="who"><?php echo e($profile['username']); ?><small>member since <?php echo e($profile['created_at']); ?></small></div>
        <div>
            <b>Role:</b> <?php echo e($profile['role']); ?><br>
            <b>Bio:</b> <?php echo nl2br(e((string) $profile['bio'])); ?><br>
            <?php if ($showPrivate): ?>
                <b>Email (private):</b> <?php echo e($profile['email']); ?>
            <?php else: ?>
                <span class="dim">Email: hidden (owner only)</span>
            <?php endif; ?>
        </div>
    </article>
<?php endif; ?>

<h2>Members</h2>
<ul class="members">
    <?php foreach ($members as $m): ?>
        <li><a href="/profile.php?id=<?php echo (int) $m['id']; ?><?php echo e($meParam); ?>"><?php echo e($m['username']); ?></a></li>
    <?php endforeach; ?>
</ul>
<?php require __DIR__ . '/includes/footer.php'; ?>
