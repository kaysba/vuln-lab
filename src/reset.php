<?php
// Remise à zéro de la base. Garde la table progress, refait tout le reste.
require __DIR__ . '/config/db.php';

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
    // a. Sauvegarde de la progression
    $res = $conn->query('SELECT module, level, solved_at FROM progress');
    if (!$res) {
        return null;
    }
    $saved = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();

    // b. Drop + schéma + données
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

    // c. Restauration de la progression
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

    // d. Chiffres pour le message de succès
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
    exit('Méthode non autorisée');
}

$message = '';
$success = false;

if ($method === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    if (is_string($confirm) && $confirm === 'RESET') {
        $stats = reset_database(db());
        if ($stats === null) {
            http_response_code(500);
            $message = 'La remise à zéro a échoué.';
        } else {
            $success = true;
            $message = 'Base remise à zéro : ' . $stats['users'] . ' utilisateurs, '
                . $stats['posts'] . ' posts. Progression conservée.';
        }
    } else {
        $message = 'Confirmation incorrecte : tapez exactement RESET. Rien n\'a été modifié.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>vuln-lab - Remise à zéro</title>
</head>
<body>
    <h1>Remise à zéro de la base</h1>
    <?php if ($message !== ''): ?>
        <p><strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php endif; ?>
    <?php if (!$success): ?>
        <p><strong>Attention :</strong> les utilisateurs, posts, commentaires et messages seront remis à leur état initial. Vos modifications seront perdues. La progression (flags validés) est conservée.</p>
        <form method="post" action="reset.php">
            <label>Tapez RESET pour confirmer :
                <input type="text" name="confirm" autocomplete="off">
            </label>
            <button type="submit">Remettre à zéro</button>
        </form>
    <?php endif; ?>
    <p><a href="index.php">Retour à l'accueil</a></p>
</body>
</html>
