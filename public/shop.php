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
$products = with_card_photos(get_products($category ? (int) $category['id'] : null, $sort, $search));
$categories = get_categories();

$baseUrl = $category ? category_url($category) : url('shop');
$crumbs = [['Home', url()], ['Our Range', url('shop')]];
if ($category) {
    $crumbs[] = [$category['name'], $baseUrl];
}

$shopDescription = $category
    ? ($category['meta_description'] ?: content_text((string) $category['description']))
    : (multi_branch()
        ? 'Order fresh 100% Halal tortes, mud cakes and cheesecakes online. Baked daily for pickup in ' . shop_places() . ' or delivery across Canberra.'
        : 'Order fresh 100% Halal tortes, velvet cakes, mud cakes and cheesecakes online. Baked in ' . shop_places() . ' for pickup or delivery across Canberra.');

$page = [
    'title'       => $category
        ? ($category['meta_title'] ?: $category['name'] . ' | ' . setting('store_short_name'))
        : 'Order Cakes Online in Canberra | ' . setting('store_short_name'),
    'description' => $shopDescription,
    'canonical'   => $baseUrl,
    // Search and sort versions repeat the same cakes: keep them out of Google but follow their links.
    'robots'      => $search !== '' || $sort !== 'featured' ? 'noindex, follow' : '',
    'schema'      => [
        breadcrumb_schema($crumbs),
        webpage_schema($category['name'] ?? 'Our Range of Cakes', $baseUrl, $shopDescription, $category['updated_at'] ?? null, 'CollectionPage'),
        [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $category['name'] ?? 'Cakes',
            'itemListElement' => array_map(static fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => product_url($p), 'name' => $p['name']], $products, array_keys($products)),
        ],
    ],
];
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title"><?= e($category['name'] ?? 'Our Range of Cakes') ?></h1>
        <?php if (!empty($category['description'])): ?>
            <div class="page-lead prose"><?= rich_text($category['description']) ?></div>
        <?php else: ?>
            <p class="page-lead">Fresh cakes made in our shop with 100% Halal ingredients. Choose a cake, pick your size and choose pickup or delivery at checkout.</p>
        <?php endif; ?>
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

<?php if ($search === ''): ?>
<section class="section section--compact section--tint">
    <div class="container container--narrow">
        <?php if ($category && !empty($category['guide'])): ?>
            <article class="prose"><?= rich_text($category['guide']) ?></article>
        <?php elseif (!$category): ?>
            <article class="prose">
                <h2>How to Choose the Right Cake Size &amp; Style in Canberra</h2>
                <p>Every cake on our menu is baked from scratch in our Greenway kitchen (established March 2015) using <strong>100% Halal ingredients</strong>—with zero alcohol, no animal shortening, and Halal-suitable gelatine and vanilla across all sponges, mousses and cheesecakes. Order online at least <?= e(notice_label((int) setting('min_lead_hours', '24'))) ?> ahead for free pickup at <?= e(store_address_line()) ?><?= setting_bool('delivery_enabled') ? ' or scheduled delivery across Canberra and Queanbeyan' : '' ?>.</p>
                <img src="<?= e(asset('img/cake-serving-guide.svg')) ?>" alt="Cake portion and serving size guide comparing Half Cheesecake (6 serves), Midi Cake (8 serves), Full Cheesecake (12 serves), Full Cake (14 serves), and XL or Profiterole Towers (15 to 20 serves)" width="960" height="340" loading="lazy" decoding="async">
                <h3>Compare Our Six Cake Ranges</h3>
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Texture &amp; Filling</th>
                            <th scope="col">Sizes &amp; Servings</th>
                            <th scope="col">Price Range</th>
                            <th scope="col">Best Occasions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="<?= e(url('shop/tortes')) ?>"><strong>Tortes</strong></a></td>
                            <td>Light layered European sponge with fresh cream, fruit, coffee or mousse</td>
                            <td>Midi (8), Full (14), XL Rainbow (20)</td>
                            <td>$39.95 – $69.95</td>
                            <td>Birthdays, family dinners, guests preferring light cream cakes</td>
                        </tr>
                        <tr>
                            <td><a href="<?= e(url('shop/velvet-cakes')) ?>"><strong>Velvet Cakes</strong></a></td>
                            <td>Soft crimson velvet crumb layered with smooth cream cheese frosting</td>
                            <td>Midi (8), Full (14)</td>
                            <td>$59.95 – $69.95</td>
                            <td>Anniversaries, engagements, Eid &amp; milestone celebrations</td>
                        </tr>
                        <tr>
                            <td><a href="<?= e(url('shop/mud-cakes')) ?>"><strong>Mud Cakes</strong></a></td>
                            <td>Dense, rich chocolate, white gold, marble or caramel ganache crumb</td>
                            <td>Midi (8), Full (14)</td>
                            <td>$39.95 – $69.95</td>
                            <td>Parties needing sturdy transport, chocolate lovers &amp; custom bases</td>
                        </tr>
                        <tr>
                            <td><a href="<?= e(url('shop/baked-cheesecakes')) ?>"><strong>Baked Cheesecakes</strong></a></td>
                            <td>Velvety oven-baked New York style cream cheese on a biscuit crust</td>
                            <td>Half (6), Full (12)</td>
                            <td>$22.95 – $44.95</td>
                            <td>Dinner parties, office morning teas &amp; coffee catch-ups</td>
                        </tr>
                        <tr>
                            <td><a href="<?= e(url('shop/continental-cheesecakes')) ?>"><strong>Continental Cheesecakes</strong></a></td>
                            <td>Chilled, airy mousse-style set cheesecake with fruit or caramel</td>
                            <td>Half (6), Full (12)</td>
                            <td>$22.95 – $39.95</td>
                            <td>Refreshing dessert after festive lunches, summer gatherings &amp; Iftar</td>
                        </tr>
                        <tr>
                            <td><a href="<?= e(url('shop/tea-cakes-desserts')) ?>"><strong>Tea Cakes &amp; Desserts</strong></a></td>
                            <td>Spiced carrot cake, orange poppy seed, crisp pavlova &amp; profiterole towers</td>
                            <td>Midi (8), One size (10), Full (14), Towers (10–18)</td>
                            <td>$34.95 – $85.00</td>
                            <td>Morning teas, grazing tables &amp; centrepiece dessert towers</td>
                        </tr>
                    </tbody>
                </table>
                <h3>Dietary Options, Free Cake Messages &amp; Custom Designs</h3>
                <p>Every celebration cake includes a <strong>free piped message</strong> on top—simply enter your wording before adding to cart. Need an <strong>eggless</strong>, <strong>vegan</strong> or <strong>gluten-free</strong> cake, or planning a multi-tier celebration? Call our Greenway team on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a>, read our <a href="<?= e(url('allergens')) ?>">allergen &amp; Halal guide</a>, or <a href="<?= e(url('custom-cakes')) ?>">request a custom cake quote</a>.</p>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php render('footer'); ?>
