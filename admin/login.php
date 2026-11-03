<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameValue = $_POST['username'] ?? '';
    $username = is_string($usernameValue) ? trim($usernameValue) : '';
    $password = $_POST['password'] ?? '';

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!is_string($password) || $username === '' || strlen($username) > 80) {
        $error = 'Enter a valid username and password.';
    } else {
        $statement = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = (string) $admin['username'];
            header('Location: index.php');
            exit;
        }

        $error = 'The username or password was not recognized.';
    }
}

$pageTitle = 'Admin login';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-login-page">
    <p class="eyebrow">SHOP ADMINISTRATION</p>
    <h1>Sign in</h1>
    <p class="admin-intro">Sign in to manage orders and view shop activity.</p>
    <?php if ($error !== ''): ?>
        <p class="notice notice-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" class="checkout-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="80" autocomplete="username" value="<?= e($username) ?>" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button button-dark place-order-button" type="submit">Sign in</button>
    </form>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
