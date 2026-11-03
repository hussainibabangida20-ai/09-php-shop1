<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
admin_require_login();

$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($orderId === false || $orderId === null || $orderId < 1) {
    http_response_code(404);
    $orderId = 0;
}

$transitions = [
    'pending' => ['paid', 'cancelled'],
    'paid' => ['shipped', 'cancelled'],
    'shipped' => ['delivered', 'cancelled'],
    'delivered' => [],
    'cancelled' => [],
];
$error = '';
$notice = (string) ($_SESSION['admin_order_notice'] ?? '');
unset($_SESSION['admin_order_notice']);

if ($orderId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatusValue = $_POST['status'] ?? '';
    $newStatus = is_string($newStatusValue) ? $newStatusValue : '';
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        try {
            $pdo->beginTransaction();
            $lockOrder = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
            $lockOrder->execute([$orderId]);
            $lockedOrder = $lockOrder->fetch();
            if (!$lockedOrder) {
                throw new DomainException('This order could not be found.');
            }

            $currentStatus = (string) $lockedOrder['status'];
            if (!in_array($newStatus, $transitions[$currentStatus] ?? [], true)) {
                throw new DomainException('That status change is not allowed.');
            }

            if ($newStatus === 'cancelled') {
                $itemQuery = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? ORDER BY product_id');
                $itemQuery->execute([$orderId]);
                $items = $itemQuery->fetchAll();
                $restoreStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($items as $item) {
                    $restoreStock->execute([(int) $item['quantity'], (int) $item['product_id']]);
                    if ($restoreStock->rowCount() !== 1) {
                        throw new DomainException('Stock could not be restored for this order. No changes were saved.');
                    }
                }
            }

            $update = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $update->execute([$newStatus, $orderId]);
            $pdo->commit();
            $_SESSION['admin_order_notice'] = $newStatus === 'cancelled'
                ? 'Order cancelled and item stock restored.'
                : 'Order status updated.';
            header('Location: order.php?id=' . $orderId, true, 303);
            exit;
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin order update failed: ' . $exception->getMessage());
            $error = 'The order could not be updated. No changes were saved.';
        }
    }
}

$order = null;
$items = [];
if ($orderId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, reference, customer_name, email, phone, address, total, status, created_at
         FROM orders
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $orderId]);
    $order = $statement->fetch();
    if ($order) {
        $itemStatement = $pdo->prepare(
            'SELECT product_id, product_name, unit_price, quantity
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY product_name'
        );
        $itemStatement->execute(['order_id' => $orderId]);
        $items = $itemStatement->fetchAll();
    } else {
        http_response_code(404);
    }
}

$statusLabels = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$pageTitle = $order ? 'Order ' . $order['reference'] : 'Order not found';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <div class="admin-topbar">
        <div>
            <p class="eyebrow">SHOP ADMINISTRATION</p>
            <h1><?= $order ? 'Order details' : 'Order not found' ?></h1>
        </div>
        <a class="text-link" href="orders.php">&larr; Back to orders</a>
    </div>
    <?php if ($error !== ''): ?><p class="notice notice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="notice notice-success" role="status"><?= e($notice) ?></p><?php endif; ?>

    <?php if ($order): ?>
        <div class="order-detail-grid">
            <div class="admin-panel">
                <div class="order-detail-heading">
                    <div><span class="admin-muted">Reference</span><strong><?= e($order['reference']) ?></strong></div>
                    <span class="status-pill status-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
                </div>
                <h2>Items</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e($item['product_name']) ?></td>
                                    <td><?= money($item['unit_price']) ?></td>
                                    <td><?= (int) $item['quantity'] ?></td>
                                    <td><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="order-total-row"><td colspan="3">Order total</td><td><?= money($order['total']) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <aside class="admin-panel customer-panel">
                <h2>Customer</h2>
                <dl>
                    <dt>Name</dt><dd><?= e($order['customer_name']) ?></dd>
                    <dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
                    <dt>Phone</dt><dd><?= e($order['phone']) ?></dd>
                    <dt>Delivery address</dt><dd><?= nl2br(e($order['address'])) ?></dd>
                    <dt>Placed</dt><dd><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></dd>
                </dl>
                <?php if ($transitions[$order['status']] ?? []): ?>
                    <form method="post" class="status-change-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label for="status">Change status</label>
                        <select id="status" name="status" required>
                            <?php foreach ($transitions[$order['status']] as $nextStatus): ?>
                                <option value="<?= e($nextStatus) ?>"><?= e($statusLabels[$nextStatus]) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button button-dark" type="submit">Save status</button>
                    </form>
                    <?php if ($order['status'] !== 'delivered'): ?>
                        <p class="admin-muted cancellation-note">Cancelling restores the quantities sold to stock.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </div>
    <?php else: ?>
        <div class="empty-state"><h2>We couldn't find that order.</h2><a class="text-link" href="orders.php">Return to orders</a></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
