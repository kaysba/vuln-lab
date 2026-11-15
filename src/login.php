<?php
require __DIR__ . '/config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'HEAD' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit('Method not allowed');
}

// Medium filter: naive blacklist on the username, case-insensitive substring removal, single pass.
function login_blacklist(string $s): string
{
    // VULN: SQL injection - level medium (single-pass blacklist, nested keywords survive)
    return str_ireplace(['OR', 'AND', 'XOR', 'NOT', 'UNION', 'SELECT', '||', '&&', '--', '#', '/*', '*/'], '', $s);
}

// Runs the level-specific vulnerable login. Returns [row|null, error message|null].
function vulnerable_login(string $level, string $username, string $password, string $loginBy): array
{
    $db = db();
    $row = null;
    $error = null;
    try {
        switch ($level) {
            case 'easy':
                // VULN: SQL injection - level easy (string concatenation, no escaping)
                // VULN: weak auth - level easy (unsalted MD5, no rate limiting)
                $sql = "SELECT id, username, role FROM users WHERE username = '$username' AND password = MD5('$password') LIMIT 1";
                $res = $db->query($sql);
                if ($res === false) {
                    // VULN: SQL injection - level easy (verbose errors)
                    $error = $db->error;
                } else {
                    $row = $res->fetch_assoc() ?: null;
                }
                break;

            case 'medium':
                // VULN: SQL injection - level medium (username concatenated after a single-pass blacklist)
                // VULN: weak auth - level medium (unsalted MD5 computed in PHP, no rate limiting)
                $u = login_blacklist($username);
                $p = md5($password);
                $sql = "SELECT id, username, role FROM users WHERE username = '$u' AND password = '$p' LIMIT 1";
                $res = $db->query($sql);
                if ($res === false) {
                    $error = 'Login failed.';
                } else {
                    $row = $res->fetch_assoc() ?: null;
                }
                break;

            case 'hard':
            default:
                // VULN: SQL injection - level hard (login_by concatenated as a column identifier)
                // VULN: weak auth - level hard (unsalted MD5, no rate limiting)
                $sql = "SELECT id, username, role FROM users WHERE $loginBy = ? AND password = MD5(?) LIMIT 1";
                $stmt = $db->prepare($sql);
                if ($stmt === false) {
                    $error = 'Login failed.';
                    break;
                }
                if (!$stmt->bind_param('ss', $username, $password) || !$stmt->execute()) {
                    $stmt->close();
                    $error = 'Login failed.';
                    break;
                }
                $res = $stmt->get_result();
                $row = $res ? ($res->fetch_assoc() ?: null) : null;
                $stmt->close();
                break;
        }
    } catch (mysqli_sql_exception $ex) {
        // VULN: SQL injection - level easy (verbose errors, only shown on easy)
        $error = $level === 'easy' ? $ex->getMessage() : 'Login failed.';
        $row = null;
    }
    return [$row, $error];
}

// Safe check: are the submitted credentials genuinely valid for an admin account?
function genuine_admin(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, role FROM users WHERE username = ? AND password = MD5(?)');
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $res = $stmt->get_result();
    $found = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return is_array($found) && $found['role'] === 'admin';
}

$error = null;
$loginFlag = null;
$loginFlagLevel = null;
$failed = false;

if ($method === 'POST') {
    $level = get_level(); // read once, used for the switch and the flag
    $username = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $loginBy = is_string($_POST['login_by'] ?? null) ? $_POST['login_by'] : 'username';

    [$row, $error] = vulnerable_login($level, $username, $password, $loginBy);

    if ($row !== null) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['username'] = (string) $row['username'];
        $_SESSION['role'] = (string) $row['role'];
        $_SESSION['auth_level'] = $level;
        $error = null;
        if ($row['role'] === 'admin' && !genuine_admin($username, $password)) {
            $loginFlag = flag_for('login-sqli', $level);
            $loginFlagLevel = $level;
        }
    } else {
        $failed = true;
        if ($error === null) {
            $error = 'Invalid credentials.';
        }
    }
}

$level = get_level();
$levelDesc = [
    'easy'   => 'Legacy staff portal. Nobody ever audited this form.',
    'medium' => 'A web filter was bolted on after an incident. Management says it is fine.',
    'hard'   => 'The developers learned about prepared statements. Mostly.',
];

$pageTitle = 'Login';
$activeNav = 'login';
require __DIR__ . '/includes/header.php';
?>
<h1>Login</h1>
<p>Threat level: <span class="lvl-text lvl-text-<?php echo e($level); ?>"><?php echo e(strtoupper($level)); ?></span>
    <span class="dim">// <?php echo e($levelDesc[$level]); ?></span></p>

<?php if ($loginFlag !== null): ?>
    <section id="login-flag" class="flag-panel ok" data-breakin="1" aria-live="polite">
        <div class="head">AUTHENTICATION BYPASSED: <?php echo e(strtoupper($loginFlagLevel)); ?></div>
        <div>Flag: <code><?php echo e($loginFlag); ?></code></div>
        <div class="dim">Submit it on the flags page to record the capture.</div>
    </section>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])): ?>
    <p class="msg ok">Logged in as <?php echo e((string) $_SESSION['username']); ?> (<?php echo e((string) $_SESSION['role']); ?>)</p>
    <form method="post" action="/logout.php" class="login-form">
        <button type="submit">LOGOUT</button>
    </form>
<?php endif; ?>

<form method="post" action="/login.php" class="login-form" autocomplete="off">
    <label for="login-by">&gt; login with</label>
    <select id="login-by" name="login_by">
        <option value="username">username</option>
        <option value="email">email</option>
    </select>
    <label for="login-user">&gt; identifier</label>
    <input type="text" id="login-user" name="username" autocomplete="off" spellcheck="false" maxlength="200" required>
    <label for="login-pass">&gt; password</label>
    <input type="password" id="login-pass" name="password" autocomplete="off" maxlength="200">
    <button type="submit">LOGIN</button>
</form>
<?php if ($error !== null): ?>
    <p class="msg err" role="alert">ACCESS DENIED: <?php echo e($error); ?></p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
