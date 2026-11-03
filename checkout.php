<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/cart.php';

$pageTitle = 'Checkout';
$items = cart_items();
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];
$errors = [];
$checkoutError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $_) {
        $value = $_POST[$field] ?? '';
        $form[$field] = is_string($value) ? trim($value) : '';
    }

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $checkoutError = 'Your session expired. Refresh the page and try again.';
    } else {
        if ($form['name'] === '' || strlen($form['name']) > 120) {
            $errors['name'] = 'Enter your name (up to 120 characters).';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 160) {
            $errors['email'] = 'Enter a valid email address (up to 160 characters).';
        }
        if (strlen($form['phone']) > 20 || !preg_match('/^[0-9+().\-\s]{7,20}$/', $form['phone'])) {
            $errors['phone'] = 'Enter a valid phone number (7 to 20 characters).';
        }
        if ($form['address'] === '' || strlen($form['address']) > 5000) {
            $errors['address'] = 'Enter your delivery address (up to 5000 characters).';
        }
        if ($items === []) {
            $checkoutError = 'Your cart is empty or the items are no longer available. Review your cart before checking out.';
        }

        if ($errors === [] && $checkoutError === '') {
            try {
                $quantities = $_SESSION['cart'] ?? [];
                if (!is_array($quantities) || $quantities === []) {
                    throw new DomainException('Your cart is empty. Add an item before checking out.');
                }
                ksort($quantities, SORT_NUMERIC);

                $pdo->beginTransaction();

                $lockProduct = $pdo->prepare(
                    'SELECT id, name, price, stock, is_active
                     FROM products
                     WHERE id = ?
                     FOR UPDATE'
                );
                $lockedProducts = [];
                $totalCents = 0;
                foreach ($quantities as $id => $quantity) {
                    $id = filter_var($id, FILTER_VALIDATE_INT);
                    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
                    if ($id === false || $id === null || $id < 1
                        || $quantity === false || $quantity === null || $quantity < 1) {
                        throw new DomainException('Your cart contains an invalid item. Please review it and try again.');
                    }

                    $lockProduct->execute([$id]);
                    $product = $lockProduct->fetch();
                    if (!$product || (int) $product['is_active'] !== 1) {
                        throw new DomainException('An item in your cart is no longer available. Please review your cart.');
                    }
                    if ((int) $product['stock'] < $quantity) {
                        throw new DomainException('There is not enough stock for ' . $product['name'] . '. Please review your cart.');
                    }

                    $priceParts = explode('.', (string) $product['price'], 2);
                    $priceCents = ((int) $priceParts[0] * 100)
                        + (int) str_pad(substr($priceParts[1] ?? '0', 0, 2), 2, '0');
                    $totalCents += $priceCents * $quantity;
                    $lockedProducts[] = [
                        'id' => (int) $product['id'],
                        'name' => (string) $product['name'],
                        'unit_price' => (string) $product['price'],
                        'quantity' => $quantity,
                    ];
                }

                $total = intdiv($totalCents, 100) . '.'
                    . str_pad((string) ($totalCents % 100), 2, '0', STR_PAD_LEFT);
                $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders (reference, customer_name, email, phone, address, total)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insertOrder->execute([
                    $reference,
                    $form['name'],
                    $form['email'],
                    $form['phone'],
                    $form['address'],
                    $total,
                ]);
                $orderId = (int) $pdo->lastInsertId();

                $insertOrderItem = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $updateStock = $pdo->prepare(
                    'UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?'
                );

                foreach ($lockedProducts as $product) {
                    $insertOrderItem->execute([
                        $orderId,
                        $product['id'],
                        $product['name'],
                        $product['unit_price'],
                        $product['quantity'],
                    ]);
                    $updateStock->execute([
                        $product['quantity'],
                        $product['id'],
                        $product['quantity'],
                    ]);
                    if ($updateStock->rowCount() !== 1) {
                        throw new DomainException('Stock changed while placing your order. Please review your cart and try again.');
                    }
                }

                $pdo->commit();
                cart_clear();
                unset($_SESSION['cart_notice'], $_SESSION['cart_notice_type']);
                header('Location: order-success.php?ref=' . rawurlencode($reference), true, 303);
                exit;
            } catch (DomainException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $checkoutError = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Checkout failed: ' . $exception->getMessage());
                $checkoutError = 'We could not place your order right now. Your cart has been kept; please try again.';
            }
        }
    }
}

$items = cart_items();
$subtotal = array_sum(array_column($items, 'line_total'));
if (isset($_SESSION['cart_notice'])) {
    $availabilityNotice = (string) $_SESSION['cart_notice'];
    unset($_SESSION['cart_notice'], $_SESSION['cart_notice_type']);
} else {
    $availabilityNotice = '';
}

require __DIR__ . '/partials/header.php';
?>
<section class="checkout-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="index.php">Shop</a><span>/</span><a href="cart.php">Your cart</a><span>/</span><span>Checkout</span>
    </nav>
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">ALMOST YOURS</p>
            <h1>Checkout</h1>
        </div>
    </div>

    <?php if ($checkoutError !== ''): ?>
        <p class="notice notice-error" role="alert"><?= e($checkoutError) ?></p>
    <?php endif; ?>
    <?php if ($availabilityNotice !== ''): ?>
        <p class="notice notice-info" role="status"><?= e($availabilityNotice) ?></p>
    <?php endif; ?>

    <?php if ($items): ?>
        <div class="checkout-layout">
            <form method="post" class="checkout-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Delivery details</h2>
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" maxlength="120" autocomplete="name" value="<?= e($form['name']) ?>" required>
                <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>

                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="160" autocomplete="email" value="<?= e($form['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>

                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($form['phone']) ?>" required>
                <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>

                <label for="address">Delivery address</label>
                <textarea id="address" name="address" rows="4" maxlength="5000" autocomplete="street-address" required><?= e($form['address']) ?></textarea>
                <?php if (isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>

                <button class="button button-dark place-order-button" type="submit">Place order <span aria-hidden="true">&rarr;</span></button>
                <a class="continue-shopping" href="cart.php">&larr; Back to cart</a>
            </form>

            <aside class="cart-summary checkout-summary">
                <h2>Order summary</h2>
                <ul class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <span><?= e($item['name']) ?> <span class="checkout-quantity">&times; <?= (int) $item['quantity'] ?></span></span>
                            <strong><?= money($item['line_total']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
                <p class="summary-note">Your order total is calculated using current product prices. Delivery fees, if applicable, are not included.</p>
            </aside>
        </div>
    <?php else: ?>
        <div class="empty-state cart-empty">
            <span class="empty-icon">♡</span>
            <h2>Your cart is empty</h2>
            <p>Add a few favourites before checking out.</p>
            <a class="button button-dark" href="index.php">Explore the shop <span aria-hidden="true">&rarr;</span></a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
