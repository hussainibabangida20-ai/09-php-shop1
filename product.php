<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = $pdo->prepare(
    'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = :slug AND p.is_active = 1
     LIMIT 1'
);
$statement->execute(['slug' => $slug]);
$product = $statement->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="empty-state page-empty">
        <span class="empty-icon">?</span>
        <h1>We couldn't find that product</h1>
        <p>It may have moved or is no longer available.</p>
        <a class="button button-dark" href="index.php">Back to the shop</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = (string) $product['name'];
$cartMessage = '';
$cartMessageType = 'success';
if (isset($_SESSION['cart_message'])) {
    $cartMessage = (string) $_SESSION['cart_message'];
    $cartMessageType = (string) ($_SESSION['cart_message_type'] ?? 'success');
    unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $cartMessage = 'Your session expired. Refresh the page and try again.';
        $cartMessageType = 'error';
    } elseif ((int) $product['stock'] < 1) {
        $cartMessage = 'This item is currently out of stock.';
        $cartMessageType = 'error';
    } else {
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > (int) $product['stock']) {
            http_response_code(422);
            $cartMessage = 'Choose a quantity between 1 and ' . (int) $product['stock'] . '.';
            $cartMessageType = 'error';
        } else {
            $cart = $_SESSION['cart'] ?? [];
            $currentQuantity = (int) ($cart[$product['id']] ?? 0);
            $newQuantity = $currentQuantity + $quantity;
            if ($newQuantity > (int) $product['stock']) {
                $cartMessage = 'Your cart already has ' . $currentQuantity . ' of this item; only '
                    . max(0, (int) $product['stock'] - $currentQuantity) . ' more are available.';
                $cartMessageType = 'error';
            } else {
                $cart[$product['id']] = $newQuantity;
                $_SESSION['cart'] = $cart;
                $_SESSION['cart_message'] = $quantity . ' × ' . $product['name'] . ' added to your cart.';
                $_SESSION['cart_message_type'] = 'success';
                header('Location: product.php?slug=' . rawurlencode((string) $product['slug']));
                exit;
            }
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<section class="product-detail">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="index.php">Shop</a><span>/</span><span><?= e($product['name']) ?></span>
    </nav>
    <div class="detail-layout">
        <div class="detail-image">
            <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
            <?php if ((int) $product['stock'] < 1): ?>
                <span class="stock-badge">Out of stock</span>
            <?php endif; ?>
        </div>
        <div class="detail-copy">
            <p class="eyebrow"><?= e($product['category_name'] ?? 'Collection') ?></p>
            <h1><?= e($product['name']) ?></h1>
            <p class="detail-price"><?= money($product['price']) ?></p>
            <div class="detail-rule"></div>
            <p class="detail-description"><?= nl2br(e($product['description'])) ?></p>
            <?php if ($cartMessage !== ''): ?>
                <p class="notice notice-<?= e($cartMessageType) ?>" role="status"><?= e($cartMessage) ?></p>
            <?php endif; ?>
            <?php if ((int) $product['stock'] > 0): ?>
                <form method="post" class="add-to-cart-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="quantity-label" for="quantity">Quantity</label>
                    <div class="purchase-row">
                        <input id="quantity" name="quantity" type="number" min="1" max="<?= (int) $product['stock'] ?>" value="1" required>
                        <button class="button button-dark add-button" type="submit">Add to cart <span aria-hidden="true">&rarr;</span></button>
                    </div>
                    <p class="stock-note"><?= (int) $product['stock'] ?> available</p>
                </form>
            <?php else: ?>
                <button class="button button-disabled" type="button" disabled>Currently unavailable</button>
            <?php endif; ?>
            <a class="back-link" href="index.php">&larr; Back to all products</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
