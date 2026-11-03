<?php
require_once __DIR__ . '/../app/cart.php';
$pageTitle = $pageTitle ?? 'Swd Shop';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Swd Shop</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php"><span class="brand-mark">S</span> swd<span>shop</span></a>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="index.php">Shop</a>
            <a href="order-lookup.php">Track order</a>
            <a class="cart-link" href="cart.php">Cart <span class="cart-count"><?= cart_count() ?></span></a>
        </nav>
    </div>
</header>
<main>
