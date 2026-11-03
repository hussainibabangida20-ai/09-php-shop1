<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = '';
$email = '';
$error = '';
$order = null;
$items = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referenceValue = $_POST['reference'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $reference = is_string($referenceValue) ? strtoupper(trim($referenceValue)) : '';
    $email = is_string($emailValue) ? trim($emailValue) : '';

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($reference === '' || strlen($reference) > 20 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
        $error = 'Enter a valid order reference and email address.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id, reference, customer_name, email, total, status, created_at
             FROM orders
             WHERE reference = :reference AND LOWER(email) = LOWER(:email)
             LIMIT 1'
        );
        $statement->execute(['reference' => $reference, 'email' => $email]);
        $order = $statement->fetch();
        if ($order) {
            $itemStatement = $pdo->prepare(
                'SELECT product_name, unit_price, quantity
                 FROM order_items
                 WHERE order_id = :order_id
                 ORDER BY product_name'
            );
            $itemStatement->execute(['order_id' => $order['id']]);
            $items = $itemStatement->fetchAll();
        } else {
            $error = 'We could not find an order matching that reference and email.';
        }
    }
}

$statusLabels = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$pageTitle = 'Track your order';
require __DIR__ . '/partials/header.php';
?>
<section class="lookup-page">
    <p class="eyebrow">WE'RE HERE TO HELP</p>
    <h1>Track your order</h1>
    <p class="admin-intro">Enter the reference from your order confirmation and the email used at checkout.</p>
    <?php if ($error !== ''): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="checkout-form lookup-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="reference">Order reference</label>
        <input id="reference" name="reference" type="text" maxlength="20" value="<?= e($reference) ?>" placeholder="ORD-20261103-7F3K" required>
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="160" autocomplete="email" value="<?= e($email) ?>" required>
        <button class="button button-dark place-order-button" type="submit">Find my order</button>
    </form>

    <?php if ($order): ?>
        <section class="lookup-result">
            <div class="lookup-order-header">
                <div><span class="admin-muted">Order</span><strong><?= e($order['reference']) ?></strong></div>
                <span class="status-pill status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>
            <p class="lookup-date">Placed <?= e(date('M j, Y', strtotime($order['created_at']))) ?></p>
            <ul class="checkout-items">
                <?php foreach ($items as $item): ?>
                    <li><span><?= e($item['product_name']) ?> <span class="checkout-quantity">&times; <?= (int) $item['quantity'] ?></span></span><strong><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <div class="summary-row"><span>Order total</span><strong><?= money($order['total']) ?></strong></div>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
