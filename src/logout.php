<?php
require __DIR__ . '/config/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['auth_level']);
session_regenerate_id(true);
header('Location: /login.php');
exit;
