<?php
require __DIR__ . '/config/bootstrap.php';

// Safe helper: prepared SELECT returning all rows. $types/$params are bound as given.
function msg_rows(string $sql, string $types, array $params): array
{
    $stmt = db()->prepare($sql);
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// Require a valid session user (must be a real, existing account).
$sessionId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$sessionOk = $sessionId > 0 && count(msg_rows('SELECT id FROM users WHERE id = ?', 'i', [$sessionId])) === 1;
if (!$sessionOk) {
    header('Location: /login.php');
    exit;
}

$level = get_level(); // read once, used for the switch and the flag

$viewId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : null;
$format = isset($_GET['format']) && is_string($_GET['format']) ? $_GET['format'] : '';

$cols = 'm.id, m.sender_id, m.recipient_id, m.subject, m.body, m.created_at, s.username AS sender, r.username AS recipient';
$joins = 'FROM messages m JOIN users s ON s.id = m.sender_id JOIN users r ON r.id = m.recipient_id';

// Whose mailbox is displayed. Only medium lets the request decide.
$boxOwner = $sessionId;
if ($level === 'medium') {
    // VULN: IDOR - level medium (mailbox owner taken from a request parameter instead of the session)
    $boxOwner = isset($_GET['box_owner']) && is_string($_GET['box_owner']) && ctype_digit($_GET['box_owner'])
        ? (int) $_GET['box_owner'] : $sessionId;
}

$message = null;
$rawView = false;
$received = [];
$sent = [];
$notFound = false;
$idorFlag = null;

if ($viewId !== null) {
    $rows = [];
    switch ($level) {
        case 'easy':
            // VULN: IDOR - level easy (lookup by id only, no owner filter)
            $rows = msg_rows("SELECT $cols $joins WHERE m.id = ?", 'i', [$viewId]);
            break;

        case 'medium':
            // VULN: IDOR - level medium (owner filter uses the client-supplied box_owner, not the session)
            $rows = msg_rows(
                "SELECT $cols $joins WHERE m.id = ? AND (m.recipient_id = ? OR m.sender_id = ?)",
                'iii',
                [$viewId, $boxOwner, $boxOwner]
            );
            break;

        case 'hard':
        default:
            if ($format === 'raw') {
                // VULN: IDOR - level hard (export path skips the owner check)
                $rows = msg_rows("SELECT $cols $joins WHERE m.id = ?", 'i', [$viewId]);
                $rawView = true;
            } else {
                $rows = msg_rows(
                    "SELECT $cols $joins WHERE m.id = ? AND (m.recipient_id = ? OR m.sender_id = ?)",
                    'iii',
                    [$viewId, $sessionId, $sessionId]
                );
            }
            break;
    }
    $message = $rows[0] ?? null;
    $notFound = $message === null;

    // Flag only when the message really belongs to two OTHER users (checked against the session).
    if ($message !== null
        && (int) $message['sender_id'] !== $sessionId
        && (int) $message['recipient_id'] !== $sessionId) {
        $idorFlag = flag_for('idor', $level);
    }
} else {
    $received = msg_rows("SELECT $cols $joins WHERE m.recipient_id = ? ORDER BY m.created_at DESC, m.id DESC", 'i', [$boxOwner]);
    $sent = msg_rows("SELECT $cols $joins WHERE m.sender_id = ? ORDER BY m.created_at DESC, m.id DESC", 'i', [$boxOwner]);
    // Listing another user's mailbox (possible on medium only) also exposes their messages.
    if ($boxOwner !== $sessionId && ($received || $sent)) {
        $idorFlag = flag_for('idor', $level);
    }
}

$levelDesc = [
    'easy'   => 'Every message has a number. Numbers are easy to count.',
    'medium' => 'The mailbox owner is a parameter. Parameters are polite.',
    'hard'   => 'Ownership is enforced. On the main road.',
];
$boxParam = $level === 'medium' ? '&box_owner=' . $boxOwner : '';

$pageTitle = 'Messages';
$activeNav = 'messages';
require __DIR__ . '/includes/header.php';
?>
<h1>Messages</h1>
<p>Threat level: <span class="lvl-text lvl-text-<?php echo e($level); ?>"><?php echo e(strtoupper($level)); ?></span>
    <span class="dim">// <?php echo e($levelDesc[$level]); ?></span></p>

<?php if ($idorFlag !== null): ?>
    <section id="idor-flag" class="flag-panel ok" data-breakin="1" aria-live="polite">
        <div class="head">ANOTHER USER'S MESSAGES EXPOSED: <?php echo e(strtoupper($level)); ?></div>
        <div>Flag: <code><?php echo e($idorFlag); ?></code></div>
        <div class="dim">Submit it on the flags page to record the capture.</div>
    </section>
<?php endif; ?>

<?php if ($viewId !== null): ?>
    <p><a href="/messages.php<?php echo $level === 'medium' ? '?box_owner=' . (int) $boxOwner : ''; ?>">&lt; back to mailbox</a></p>
    <?php if ($notFound): ?>
        <p class="msg err" role="alert">NOT FOUND: no such message.</p>
    <?php elseif ($rawView): ?>
        <h2>Raw export #<?php echo (int) $message['id']; ?></h2>
        <pre class="raw"><?php echo e('Subject: ' . $message['subject'] . "\n\n" . $message['body']); ?></pre>
    <?php else: ?>
        <article class="post">
            <div class="who"><?php echo e($message['sender']); ?><small><?php echo e($message['created_at']); ?> to <?php echo e($message['recipient']); ?></small></div>
            <div>
                <b><?php echo e($message['subject']); ?></b><br>
                <?php echo nl2br(e($message['body'])); ?>
                <?php if ($level === 'hard'): ?>
                    <br><a class="dim" href="/messages.php?id=<?php echo (int) $message['id']; ?>&amp;format=raw">[raw export]</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endif; ?>
<?php else: ?>
    <h2>Inbox</h2>
    <?php if (!$received): ?>
        <p class="dim">No messages received.</p>
    <?php endif; ?>
    <?php foreach ($received as $m): ?>
        <article class="post">
            <div class="who"><?php echo e($m['sender']); ?><small><?php echo e($m['created_at']); ?></small></div>
            <div><a href="/messages.php?id=<?php echo (int) $m['id']; ?><?php echo e($boxParam); ?>"><?php echo e($m['subject']); ?></a></div>
        </article>
    <?php endforeach; ?>

    <h2>Sent</h2>
    <?php if (!$sent): ?>
        <p class="dim">No messages sent.</p>
    <?php endif; ?>
    <?php foreach ($sent as $m): ?>
        <article class="post">
            <div class="who">to <?php echo e($m['recipient']); ?><small><?php echo e($m['created_at']); ?></small></div>
            <div><a href="/messages.php?id=<?php echo (int) $m['id']; ?><?php echo e($boxParam); ?>"><?php echo e($m['subject']); ?></a></div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
