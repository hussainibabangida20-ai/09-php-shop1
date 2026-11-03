<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
admin_require_login();

$todaySales = $pdo->query(
    "SELECT COALESCE(SUM(total), 0)
     FROM orders
     WHERE created_at >= CURDATE()
       AND created_at < CURDATE() + INTERVAL 1 DAY
       AND status IN ('paid', 'shipped', 'delivered')"
)->fetchColumn();
$statusCounts = $pdo->query(
    'SELECT status, COUNT(*) AS order_count
     FROM orders
     GROUP BY status
     ORDER BY FIELD(status, "pending", "paid", "shipped", "delivered", "cancelled")'
)->fetchAll();
$topProducts = $pdo->query(
    "SELECT oi.product_name, SUM(oi.quantity) AS quantity_sold
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> 'cancelled'
     GROUP BY oi.product_id, oi.product_name
     ORDER BY quantity_sold DESC, oi.product_name ASC
     LIMIT 5"
)->fetchAll();

$statusLabels = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$pageTitle = 'Admin dashboard';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <div class="admin-topbar">
        <div>
            <p class="eyebrow">SHOP ADMINISTRATION</p>
            <h1>Dashboard</h1>
        </div>
        <div class="admin-actions">
            <span>Signed in as <?= e($_SESSION['admin_username']) ?></span>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="text-button" type="submit">Sign out</button>
            </form>
        </div>
    </div>
    <nav class="admin-nav" aria-label="Admin navigation">
        <a class="active" href="index.php">Dashboard</a>
        <a href="orders.php">Orders</a>
    </nav>

    <div class="admin-metric">
        <span>Today's sales <small>paid, shipped, and delivered orders</small></span>
        <strong><?= money($todaySales) ?></strong>
    </div>

    <div class="admin-grid">
        <section class="admin-panel">
            <h2>Orders by status</h2>
            <?php if ($statusCounts): ?>
                <div class="status-list">
                    <?php foreach ($statusCounts as $row): ?>
                        <a href="orders.php?status=<?= rawurlencode($row['status']) ?>">
                            <span class="status-pill status-<?= e($row['status']) ?>"><?= e($statusLabels[$row['status']] ?? $row['status']) ?></span>
                            <strong><?= (int) $row['order_count'] ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="admin-muted">No orders yet.</p>
            <?php endif; ?>
        </section>
        <section class="admin-panel">
            <h2>Top 5 products by quantity sold</h2>
            <?php if ($topProducts): ?>
                <ol class="top-products">
                    <?php foreach ($topProducts as $product): ?>
                        <li><span><?= e($product['product_name']) ?></span><strong><?= (int) $product['quantity_sold'] ?></strong></li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="admin-muted">No completed sales yet.</p>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
