<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Shop';
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'newest');
$sortOptions = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();
$conditions = ['p.is_active = 1'];
$parameters = [];
if ($categorySlug !== '') {
    $conditions[] = 'c.slug = :category';
    $parameters['category'] = $categorySlug;
}
if ($search !== '') {
    $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_description)';
    $parameters['search_name'] = '%' . $search . '%';
    $parameters['search_description'] = '%' . $search . '%';
}

$sql = 'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY ' . $sortOptions[$sort];
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$products = $statement->fetchAll();

require __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">THE EVERYDAY EDIT</p>
        <h1>Good things,<br>chosen well.</h1>
        <p>Useful, well-made pieces for a home and life you love.</p>
        <a class="button button-dark" href="#products">Explore the collection <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="hero-art" role="img" aria-label="A curated selection of everyday essentials">
        <span class="hero-note">A little more<br>beautiful, every day.</span>
        <span class="hero-orbit"></span>
    </div>
</section>

<section class="shop-section" id="products">
    <div class="section-heading">
        <div>
            <p class="eyebrow">FIND YOUR NEXT FAVOURITE</p>
            <h2>Shop the collection</h2>
        </div>
        <span class="result-count"><?= count($products) ?> <?= count($products) === 1 ? 'item' : 'items' ?></span>
    </div>

    <form class="filters" method="get" action="index.php">
        <label class="search-field">
            <span class="visually-hidden">Search products</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products...">
            <button type="submit" aria-label="Search">&rarr;</button>
        </label>
        <label class="filter-select">
            <span class="visually-hidden">Category</span>
            <select name="category" onchange="this.form.submit()">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-select">
            <span class="visually-hidden">Sort products</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            </select>
        </label>
    </form>

    <?php if ($products): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <a class="product-image" href="product.php?slug=<?= rawurlencode($product['slug']) ?>">
                        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php if ((int) $product['stock'] < 1): ?>
                            <span class="stock-badge">Out of stock</span>
                        <?php endif; ?>
                    </a>
                    <div class="product-info">
                        <p class="product-category"><?= e($product['category_name'] ?? 'Collection') ?></p>
                        <div class="product-title-row">
                            <h3><a href="product.php?slug=<?= rawurlencode($product['slug']) ?>"><?= e($product['name']) ?></a></h3>
                            <span class="product-price"><?= money($product['price']) ?></span>
                        </div>
                        <p class="product-description"><?= e($product['description']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">⌕</span>
            <h3>No products found</h3>
            <p>Try a different search or browse all of our collection.</p>
            <a class="text-link" href="index.php">Clear filters &rarr;</a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
