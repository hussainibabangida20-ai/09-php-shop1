<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/cart.php';

$pageTitle = 'Your cart';
$pageError = '';
$pageErrorType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $pageError = 'Your session expired. Refresh the page and try again.';
    } else {
        unset($_SESSION['cart_notice'], $_SESSION['cart_notice_type']);
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'update') {
            $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || $id < 1 || $quantity === false || $quantity === null || $quantity < 1) {
                http_response_code(422);
                $pageError = 'Choose a valid product and a quantity of at least 1.';
            } else {
                $updatedQuantity = cart_update($id, $quantity);
                if (!isset($_SESSION['cart_notice'])) {
                    $_SESSION['cart_notice'] = $updatedQuantity > 0 ? 'Cart updated.' : 'The product is no longer available.';
                    $_SESSION['cart_notice_type'] = $updatedQuantity > 0 ? 'success' : 'error';
                }
            }
        } elseif ($action === 'remove') {
            $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || $id < 1) {
                http_response_code(422);
                $pageError = 'Choose a valid product to remove.';
            } else {
                cart_remove($id);
                $_SESSION['cart_notice'] = 'Item removed from your cart.';
                $_SESSION['cart_notice_type'] = 'success';
            }
        } elseif ($action === 'clear') {
            cart_clear();
            $_SESSION['cart_notice'] = 'Your cart has been cleared.';
            $_SESSION['cart_notice_type'] = 'success';
        } else {
            http_response_code(400);
            $pageError = 'That cart action is not available.';
        }

        if ($pageError === '') {
            header('Location: cart.php');
            exit;
        }
    }
}

$items = cart_items();
$subtotal = cart_total();
$notice = $pageError !== '' ? $pageError : (string) ($_SESSION['cart_notice'] ?? '');
$noticeType = $pageError !== '' ? $pageErrorType : (string) ($_SESSION['cart_notice_type'] ?? 'info');
unset($_SESSION['cart_notice'], $_SESSION['cart_notice_type']);

require __DIR__ . '/partials/header.php';
?>
<section class="cart-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="index.php">Shop</a><span>/</span><span>Your cart</span>
    </nav>
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">YOUR PICKS</p>
            <h1>Your shopping cart</h1>
        </div>
        <span class="result-count"><?= cart_count() ?> <?= cart_count() === 1 ? 'item' : 'items' ?></span>
    </div>

    <?php if ($notice !== ''): ?>
        <p class="notice notice-<?= e($noticeType) ?>" role="status"><?= e($notice) ?></p>
    <?php endif; ?>

    <?php if ($items): ?>
        <div class="cart-layout">
            <div class="cart-table-wrap">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Price</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="cart-product-cell">
                                    <a class="cart-product-image" href="product.php?slug=<?= rawurlencode($item['slug']) ?>">
                                        <img src="<?= e($item['image_url']) ?>" alt="">
                                    </a>
                                    <div>
                                        <a class="cart-product-name" href="product.php?slug=<?= rawurlencode($item['slug']) ?>"><?= e($item['name']) ?></a>
                                        <span class="cart-stock"><?= (int) $item['stock'] ?> available</span>
                                    </div>
                                </td>
                                <td data-label="Price"><?= money($item['price']) ?></td>
                                <td data-label="Quantity">
                                    <div class="cart-actions">
                                        <form method="post" class="cart-update-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <label class="visually-hidden" for="quantity-<?= (int) $item['id'] ?>">Quantity for <?= e($item['name']) ?></label>
                                            <input id="quantity-<?= (int) $item['id'] ?>" type="number" name="quantity" min="1" max="<?= (int) $item['stock'] ?>" value="<?= (int) $item['quantity'] ?>" required>
                                            <button class="text-button" type="submit">Update</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <button class="text-button remove-button" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </td>
                                <td class="cart-line-total" data-label="Total"><?= money($item['line_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" class="clear-cart-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="clear">
                    <button class="text-button remove-button" type="submit">Clear cart</button>
                </form>
            </div>
            <aside class="cart-summary">
                <h2>Order summary</h2>
                <div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div>
                <p class="summary-note">Shipping and any applicable fees are calculated at checkout.</p>
                <a class="button button-dark checkout-button" href="checkout.php">Proceed to checkout <span aria-hidden="true">&rarr;</span></a>
                <a class="continue-shopping" href="index.php">&larr; Continue shopping</a>
            </aside>
        </div>
    <?php else: ?>
        <div class="empty-state cart-empty">
            <span class="empty-icon">♡</span>
            <h2>Your cart is waiting</h2>
            <p>Take a look around and add something you love.</p>
            <a class="button button-dark" href="index.php">Explore the shop <span aria-hidden="true">&rarr;</span></a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
