<?php
declare(strict_types=1);

function cart_set_notice(string $notice): void
{
    $_SESSION['cart_notice'] = $notice;
    $_SESSION['cart_notice_type'] = 'info';
}

function cart_product(int $id): ?array
{
    global $pdo;

    $statement = $pdo->prepare(
        'SELECT id, name, slug, description, price, stock, image_url, is_active
         FROM products
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $product = $statement->fetch();

    return $product ?: null;
}

function cart_add(int $id, int $qty): int
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product ID and quantity must be positive integers.');
    }

    $product = cart_product($id);
    if ($product === null || (int) $product['is_active'] !== 1) {
        cart_set_notice('This product is no longer available.');
        return 0;
    }

    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        $cart = [];
    }
    $currentQuantity = max(0, (int) ($cart[$id] ?? 0));
    $stock = max(0, (int) $product['stock']);
    $quantity = min($currentQuantity + $qty, $stock);

    if ($quantity < 1) {
        unset($cart[$id]);
        cart_set_notice($product['name'] . ' is out of stock and was not added.');
    } else {
        $cart[$id] = $quantity;
        if ($quantity < $currentQuantity + $qty) {
            cart_set_notice('Quantity for ' . $product['name'] . ' was adjusted to the available stock (' . $stock . ').');
        }
    }

    $_SESSION['cart'] = $cart;
    return $quantity;
}

function cart_update(int $id, int $qty): int
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product ID and quantity must be positive integers.');
    }

    $product = cart_product($id);
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        $cart = [];
    }
    if ($product === null || (int) $product['is_active'] !== 1) {
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        cart_set_notice('A product in your cart is no longer available and was removed.');
        return 0;
    }

    $stock = max(0, (int) $product['stock']);
    $quantity = min($qty, $stock);
    if ($quantity < 1) {
        unset($cart[$id]);
        cart_set_notice($product['name'] . ' is out of stock and was removed from your cart.');
    } else {
        $cart[$id] = $quantity;
        if ($quantity < $qty) {
            cart_set_notice('Quantity for ' . $product['name'] . ' was adjusted to the available stock (' . $stock . ').');
        }
    }

    $_SESSION['cart'] = $cart;
    return $quantity;
}

function cart_remove(int $id): void
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        $cart = [];
    }
    unset($cart[$id]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items(): array
{
    global $pdo;

    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart) || $cart === []) {
        $_SESSION['cart'] = [];
        return [];
    }

    $normalizedCart = [];
    foreach ($cart as $id => $quantity) {
        $productId = filter_var($id, FILTER_VALIDATE_INT);
        $validQuantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if ($productId !== false && $productId !== null && $productId > 0
            && $validQuantity !== false && $validQuantity !== null && $validQuantity > 0) {
            $normalizedCart[$productId] = $validQuantity;
        }
    }

    if ($normalizedCart === []) {
        $_SESSION['cart'] = [];
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($normalizedCart), '?'));
    $statement = $pdo->prepare(
        "SELECT id, name, slug, description, price, stock, image_url, is_active
         FROM products
         WHERE id IN ({$placeholders})"
    );
    $statement->execute(array_keys($normalizedCart));
    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $lines = [];
    $adjustedCart = [];
    $adjustments = [];
    foreach ($normalizedCart as $id => $quantity) {
        $product = $products[$id] ?? null;
        if ($product === null || (int) $product['is_active'] !== 1) {
            $adjustments[] = ($product['name'] ?? 'An unavailable product') . ' (removed)';
            continue;
        }

        $stock = max(0, (int) $product['stock']);
        $availableQuantity = min($quantity, $stock);
        if ($availableQuantity < 1) {
            $adjustments[] = $product['name'] . ' (out of stock)';
            continue;
        }
        if ($availableQuantity < $quantity) {
            $adjustments[] = $product['name'] . ' (limited to ' . $stock . ')';
        }

        $product['id'] = $id;
        $product['price'] = (float) $product['price'];
        $product['stock'] = $stock;
        $product['quantity'] = $availableQuantity;
        $product['line_total'] = $product['price'] * $availableQuantity;
        $product['stock_ok'] = $availableQuantity <= $stock;
        $lines[] = $product;
        $adjustedCart[$id] = $availableQuantity;
    }

    $_SESSION['cart'] = $adjustedCart;
    if ($adjustments !== []) {
        cart_set_notice('Your cart was adjusted to current availability: ' . implode(', ', $adjustments) . '.');
    }

    return $lines;
}

function cart_total(): float
{
    return array_sum(array_column(cart_items(), 'line_total'));
}

function cart_count(): int
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return 0;
    }

    return array_sum(array_map(
        static fn (mixed $quantity): int => max(0, (int) $quantity),
        $cart
    ));
}
