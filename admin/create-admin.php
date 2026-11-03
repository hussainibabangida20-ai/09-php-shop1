<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require __DIR__ . '/../app/bootstrap.php';

$username = trim((string) getenv('ADMIN_USERNAME'));
$password = (string) getenv('ADMIN_PASSWORD');
if ($username === '' || strlen($username) > 80 || $password === '') {
    fwrite(STDERR, "Set ADMIN_USERNAME and ADMIN_PASSWORD in the environment before running this script.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "The admin password must be at least 12 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$statement = $pdo->prepare(
    'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
);
$statement->execute([
    'username' => $username,
    'password_hash' => $passwordHash,
]);

fwrite(STDOUT, "Admin account created for {$username}.\n");
