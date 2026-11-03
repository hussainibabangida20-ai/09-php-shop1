<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
admin_require_login();

$allowedStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
$statusValue = $_GET['status'] ?? '';
$fromValue = $_GET['from'] ?? '';
$toValue = $_GET['to'] ?? '';
$status = is_string($statusValue) ? trim($statusValue) : '';
$from = is_string($fromValue) ? trim($fromValue) : '';
$to = is_string($toValue) ? trim($toValue) : '';
$filters = [];
$parameters = [];
$validDate = static function (string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $filters[] = 'o.status = :status';
    $parameters['status'] = $status;
} elseif ($status !== '') {
    $status = '';
}
if ($from !== '' && $validDate($from)) {
    $filters[] = 'o.created_at >= :date_from';
    $parameters['date_from'] = $from . ' 00:00:00';
} elseif ($from !== '') {
    $from = '';
}
if ($to !== '' && $validDate($to)) {
    $filters[] = 'o.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
    $parameters['date_to'] = $to;
} elseif ($to !== '') {
    $to = '';
}

$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';
$statement = $pdo->prepare(
    "SELECT o.id, o.reference, o.customer_name, o.email, o.total, o.status, o.created_at,
            COALESCE(items.item_count, 0) AS item_count,
            COALESCE(items.line_count, 0) AS line_count
     FROM orders o
     LEFT JOIN (
         SELECT order_id, SUM(quantity) AS item_count, COUNT(*) AS line_count
         FROM order_items
         GROUP BY order_id
     ) items ON items.order_id = o.id
     {$where}
     ORDER BY o.created_at DESC, o.id DESC"
);
$statement->execute($parameters);
$orders = $statement->fetchAll();

$summaryStatement = $pdo->prepare("SELECT COUNT(*) AS order_count, COALESCE(SUM(o.total), 0) AS order_total FROM orders o {$where}");
$summaryStatement->execute($parameters);
$summary = $summaryStatement->fetch();
$statusLabels = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$pageTitle = 'Orders';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <div class="admin-topbar">
        <div>
            <p class="eyebrow">SHOP ADMINISTRATION</p>
            <h1>Orders</h1>
        </div>
        <div class="admin-actions">
            <a class="text-link" href="index.php">Dashboard</a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="text-button" type="submit">Sign out</button>
            </form>
        </div>
    </div>
    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="index.php">Dashboard</a>
        <a class="active" href="orders.php">Orders</a>
    </nav>
    <form method="get" class="admin-filters">
        <label>Status
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach ($allowedStatuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($statusLabels[$option]) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>
        <label>To
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>
        <button class="button button-dark" type="submit">Apply filters</button>
        <a class="admin-clear-filter" href="orders.php">Clear</a>
    </form>
    <div class="admin-results">
        <span><?= (int) $summary['order_count'] ?> orders</span>
        <strong>Order total: <?= money($summary['order_total']) ?></strong>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reference</th><th>Customer</th><th>Date</th><th>Status</th><th>Items</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a class="admin-order-link" href="order.php?id=<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                        <td><?= e($order['customer_name']) ?><small><?= e($order['email']) ?></small></td>
                        <td><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
                        <td><span class="status-pill status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                        <td><?= (int) $order['item_count'] ?> <small><?= (int) $order['line_count'] ?> lines</small></td>
                        <td><?= money($order['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$orders): ?>
                    <tr><td class="admin-empty" colspan="6">No orders match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
