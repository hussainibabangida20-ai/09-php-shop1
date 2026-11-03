<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = trim((string) ($_GET['ref'] ?? ''));
$statement = $pdo->prepare(
    'SELECT reference, customer_name, total, status, created_at
     FROM orders
     WHERE reference = :reference
     LIMIT 1'
);
$statement->execute(['reference' => $reference]);
$order = $statement->fetch();

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order confirmed' : 'Order not found';
require __DIR__ . '/partials/header.php';
?>
<section class="empty-state page-empty order-success">
    <?php if ($order): ?>
        <span class="success-mark" aria-hidden="true">&check;</span>
        <p class="eyebrow">THANK YOU, <?= e(strtoupper((string) $order['customer_name'])) ?></p>
        <h1>Your order is confirmed</h1>
        <p>We have received your order. Keep this reference for your records.</p>
        <div class="order-confirmation">
            <span>Order reference</span>
            <strong><?= e($order['reference']) ?></strong>
            <span>Order total</span>
            <strong><?= money($order['total']) ?></strong>
        </div>
        <a class="button button-dark" href="index.php">Continue shopping <span aria-hidden="true">&rarr;</span></a>
    <?php else: ?>
        <span class="empty-icon">?</span>
        <h1>Order not found</h1>
        <p>We couldn't find an order matching that reference.</p>
        <a class="button button-dark" href="index.php">Back to the shop</a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
