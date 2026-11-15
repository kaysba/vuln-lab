<?php
require __DIR__ . '/config/db.php';
$nbUsers = (int) db()->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>vuln-lab</title>
</head>
<body>
    <h1>vuln-lab</h1>
    <p>L'application tourne.</p>
    <p><strong>Attention :</strong> application volontairement vulnérable, usage local uniquement. Ne jamais l'exposer sur Internet.</p>
    <p>Version PHP : <?php echo phpversion(); ?></p>
    <p>Base de données : connectée (<?php echo $nbUsers; ?> utilisateurs)</p>
</body>
</html>
