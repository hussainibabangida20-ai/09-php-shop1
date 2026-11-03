<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

unset($_SESSION['admin_id'], $_SESSION['admin_username']);
session_regenerate_id(true);
header('Location: login.php');
exit;
