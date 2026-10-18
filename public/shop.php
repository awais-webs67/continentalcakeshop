<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$categorySlug = input('category');
$category = $categorySlug !== '' ? get_category_by_slug($categorySlug) : null;
if ($categorySlug !== '' && !$category) {
    not_found();
}

$sort = array_key_exists(input('sort'), PRODUCT_SORTS) ? input('sort') : 'featured';
$search = str_limit(input('q'), 60);
$products = get_products($category ? (int) $category['id'] : null, $sort, $search);
$categories = get_categories();

$baseUrl = $category ? category_url($category) : url('shop');
$crumbs = [['Home', url()], ['Shop', url('shop')]];
if ($category) {
    $crumbs[] = [$category['name'], $baseUrl];
}

$page = [
    'title'       => $category
        ? ($category['meta_title'] ?: $category['name'] . ' | ' . setting('store_short_name'))
        : 'Order Cakes Online in Canberra | ' . setting('store_short_name'),
    'description' => $category
        ? ($category['meta_description'] ?: $category['description'])
        : 'Browse tortes, velvet cakes, mud cakes and cheesecakes from The Continental Cake Shop. Order online and pick up from Greenway, Canberra.',
    'canonical'   => $baseUrl,
    'noindex'     => $search !== '' || $sort !== 'featured',
    'schema'      => [breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><?= e($category['name'] ?? 'Shop Cakes') ?></h1>
        <p class="page-lead"><?= e($category['description'] ?? 'Fresh cakes made in our shop with 100% Halal ingredients. Choose a cake, pick your size and select a pickup time at checkout.') ?></p>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <div class="shop-toolbar">
            <nav class="filter-chips" aria-label="Categories">
                <a class="chip <?= $category ? '' : 'is-active' ?>" href="<?= e(url('shop')) ?>" <?= $category ? '' : 'aria-current="page"' ?>>All cakes</a>
                <?php foreach ($categories as $cat): ?>
                    <?php $active = $category && (int) $category['id'] === (int) $cat['id']; ?>
                    <a class="chip <?= $active ? 'is-active' : '' ?>" href="<?= e(category_url($cat)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($cat['name']) ?></a>
                <?php endforeach; ?>
            </nav>

            <form class="shop-filters" method="get" action="<?= e($baseUrl) ?>">
                <label class="search-field">
                    <span class="sr-only">Search cakes</span>
                    <?= icon('search', '', 18) ?>
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search cakes" maxlength="60">
                </label>
                <label class="select-field">
                    <span class="sr-only">Sort by</span>
                    <select name="sort" data-auto-submit>
                        <?php foreach (PRODUCT_SORTS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <noscript><button class="btn btn--sm btn--primary" type="submit">Apply</button></noscript>
            </form>
        </div>

        <p class="result-count"><?= count($products) ?> cake<?= count($products) === 1 ? '' : 's' ?><?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?></p>

        <?php if ($products): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php render('partials/product-card', compact('product')); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('search', '', 40) ?>
                <h2>No cakes found</h2>
                <p>Try a different search, or call us on <?= e(setting('store_phone')) ?> and we will help you choose.</p>
                <a class="btn btn--primary" href="<?= e(url('shop')) ?>">See all cakes</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php render('footer'); ?>
