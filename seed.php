<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require __DIR__ . '/app/bootstrap.php';

$catalog = [
    'Home' => [
        ['Stoneware Ripple Mug', 'stoneware-ripple-mug', 'Hand-finished stoneware with a soft, tactile glaze for your daily coffee ritual.', 8500, 24],
        ['Linen Cushion Cover', 'linen-cushion-cover', 'A relaxed, breathable linen cover in a warm natural shade. Cushion insert not included.', 14500, 18],
        ['Woven Storage Basket', 'woven-storage-basket', 'A sturdy handwoven basket that keeps throws, toys and everyday clutter in order.', 22000, 0],
        ['Ceramic Table Vase', 'ceramic-table-vase', 'A sculptural matte ceramic vase that looks lovely with fresh stems or on its own.', 18000, 12],
        ['Cotton Throw Blanket', 'cotton-throw-blanket', 'A generously sized, soft cotton throw for slow mornings and cool evenings.', 32500, 9],
    ],
    'Kitchen' => [
        ['Acacia Serving Board', 'acacia-serving-board', 'A beautifully grained acacia board for serving, chopping and sharing.', 16500, 15],
        ['Glass Oil Bottle', 'glass-oil-bottle', 'A clear glass pourer with a neat stainless spout for oils and dressings.', 7800, 31],
        ['Everyday Cotton Apron', 'everyday-cotton-apron', 'A durable cotton apron with an adjustable neck strap and roomy front pocket.', 12500, 20],
        ['Stackable Prep Bowls', 'stackable-prep-bowls', 'A set of three glazed ceramic bowls, designed to stack neatly between uses.', 19500, 14],
        ['Bamboo Utensil Set', 'bamboo-utensil-set', 'Five lightweight bamboo cooking tools with smooth, comfortable handles.', 9900, 27],
    ],
    'Accessories' => [
        ['Canvas Market Tote', 'canvas-market-tote', 'A roomy, reusable cotton-canvas tote with reinforced handles for everyday errands.', 11500, 36],
        ['Minimal Brass Keyring', 'minimal-brass-keyring', 'A polished solid-brass keyring that makes a small, thoughtful gift.', 6500, 42],
        ['Everyday Leather Wallet', 'everyday-leather-wallet', 'A compact full-grain leather wallet with useful card slots and a slim profile.', 28500, 8],
        ['Botanical Scented Candle', 'botanical-scented-candle', 'A hand-poured soy candle with a gentle botanical scent and cotton wick.', 13500, 17],
        ['Classic Steel Water Bottle', 'classic-steel-water-bottle', 'A double-wall stainless bottle that keeps drinks cold or warm while you are out.', 17500, 23],
    ],
];

$pdo->beginTransaction();
try {
    $categoryInsert = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug) ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $categorySelect = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
    $productInsert = $pdo->prepare(
        'INSERT INTO products (category_id, name, slug, description, price, stock, image_url, is_active)
         VALUES (:category_id, :name, :slug, :description, :price, :stock, :image_url, 1)
         ON DUPLICATE KEY UPDATE
            category_id = VALUES(category_id),
            name = VALUES(name),
            description = VALUES(description),
            price = VALUES(price),
            stock = VALUES(stock),
            image_url = VALUES(image_url),
            is_active = 1'
    );

    $productCount = 0;
    foreach ($catalog as $categoryName => $items) {
        $categorySlug = strtolower(str_replace(' ', '-', $categoryName));
        $categoryInsert->execute(['name' => $categoryName, 'slug' => $categorySlug]);
        $categorySelect->execute(['slug' => $categorySlug]);
        $categoryId = (int) $categorySelect->fetchColumn();

        foreach ($items as [$name, $slug, $description, $price, $stock]) {
            $productInsert->execute([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image_url' => 'https://placehold.co/600x400?text=' . rawurlencode($name),
            ]);
            $productCount++;
        }
    }
    $pdo->commit();
    fwrite(STDOUT, "Seeded {$productCount} products across " . count($catalog) . " categories.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
