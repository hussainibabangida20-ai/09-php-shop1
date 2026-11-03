<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

function admin_require_login(): void
{
    if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_login_url(): string
{
    return 'login.php';
}
