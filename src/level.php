<?php
// Global level change. POST only.
require __DIR__ . '/config/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

$level = $_POST['level'] ?? '';
if (is_string($level)) {
    set_level($level);
}

// Local redirect only (no open redirect).
$return = $_POST['return'] ?? '/';
$target = '/';
if (is_string($return)
    && isset($return[0]) && $return[0] === '/'
    && strncmp($return, '//', 2) !== 0
    && strpos($return, '\\') === false
    && strpos($return, ':') === false
    && !preg_match('/[\x00-\x1f\x7f]/', $return)
) {
    $target = $return;
}
header('Location: ' . $target, true, 303);
exit;
