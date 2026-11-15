<?php
// Database reset. Keeps the progress table, rebuilds everything else.
require __DIR__ . '/config/bootstrap.php';

const DB_SCRIPTS_DIR = '/var/www/db';
const TABLES_TO_DROP = ['comments', 'messages', 'posts', 'progress', 'users'];

function run_sql_file(mysqli $conn, string $path): bool
{
    $sql = @file_get_contents($path);
    if ($sql === false || $sql === '') {
        return false;
    }
    if (!$conn->multi_query($sql)) {
        return false;
    }
    $ok = true;
    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        if ($conn->errno) {
            $ok = false;
        }
        if (!$conn->more_results()) {
            break;
        }
        if (!$conn->next_result()) {
            $ok = false;
            break;
        }
    } while (true);
    return $ok && !$conn->errno;
}

function reset_database(mysqli $conn): ?array
{
    // a. Save progress
    $res = $conn->query('SELECT module, level, solved_at FROM progress');
    if (!$res) {
        return null;
    }
    $saved = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();

    // b. Drop + schema + data
    $ok = $conn->query('SET FOREIGN_KEY_CHECKS=0');
    foreach (TABLES_TO_DROP as $table) {
        $ok = $ok && $conn->query('DROP TABLE IF EXISTS `' . $table . '`');
    }
    $ok = $ok
        && run_sql_file($conn, DB_SCRIPTS_DIR . '/01-schema.sql')
        && run_sql_file($conn, DB_SCRIPTS_DIR . '/02-seed.sql');
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
    if (!$ok) {
        return null;
    }

    // c. Restore progress
    $stmt = $conn->prepare('INSERT INTO progress (module, level, solved_at) VALUES (?, ?, ?)');
    if (!$stmt) {
        return null;
    }
    foreach ($saved as $row) {
        $stmt->bind_param('sss', $row['module'], $row['level'], $row['solved_at']);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
    }
    $stmt->close();

    // d. Numbers for the success message
    $users = $conn->query('SELECT COUNT(*) FROM users');
    $posts = $conn->query('SELECT COUNT(*) FROM posts');
    if (!$users || !$posts) {
        return null;
    }
    return [
        'users' => (int) $users->fetch_row()[0],
        'posts' => (int) $posts->fetch_row()[0],
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed');
}

$message = '';
$success = false;

if ($method === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    if (is_string($confirm) && $confirm === 'RESET') {
        $stats = reset_database(db());
        if ($stats === null) {
            http_response_code(500);
            $message = 'The reset failed.';
        } else {
            $success = true;
            $message = 'Database reset: ' . $stats['users'] . ' users, '
                . $stats['posts'] . ' posts. Progress kept.';
        }
    } else {
        $message = 'Wrong confirmation: type exactly RESET. Nothing was changed.';
    }
}
?>
<?php
$pageTitle = 'Database reset';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<h1>Database reset</h1>
<?php if ($message !== ''): ?>
    <p class="<?php echo $success ? 'msg ok' : 'msg err'; ?>"><strong><?php echo e($message); ?></strong></p>
<?php endif; ?>
<?php if (!$success): ?>
    <p><strong>Warning:</strong> users, posts, comments and messages will be restored to their initial state. Your changes will be lost. Progress (validated flags) is kept.</p>
    <form method="post" action="/reset.php" class="resetform">
        <label>Type RESET to confirm:
            <input type="text" name="confirm" autocomplete="off">
        </label>
        <button type="submit">RESET DATABASE</button>
    </form>
<?php endif; ?>
<p><a href="/index.php">Back to feed</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
