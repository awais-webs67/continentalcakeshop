<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$crumbs = [['Home', url()], ['About Us', url('about')]];
$page = [
    'title'       => 'About Us | ' . setting('store_name'),
    'description' => 'The Continental Cake Shop is an independent cake shop in Greenway, Canberra, baking fresh cakes with 100% Halal ingredients since ' . setting('store_founded') . '.',
    'canonical'   => url('about'),
    'schema'      => [breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title">About us</h1>
        <p class="page-lead">An independent cake shop in Greenway, baking for Canberra since <?= e(setting('store_founded')) ?>.</p>
    </div>
</section>

<section class="section section--compact">
    <div class="container about-layout">
        <div class="about-copy">
            <h2 class="section-title">Our story</h2>
            <p>The Continental Cake Shop opened in March 2015. It was started by a small team: a cake business owner, a manager and a pastry chef, who between them had nearly ten years of experience working in well-known cake shops.</p>
            <p>Working inside the franchise model, they saw how often customers were let down and how little room there was to do things properly. So they opened their own shop, where every cake could be made the way they believed it should be.</p>
            <p>Today we bake everyday treats and celebration cakes in our Greenway shop. Our cake mix is made in house to our own recipe, and every product uses 100% Halal ingredients. Bring us a photo or an idea and we will make it for you.</p>
            <ul class="tick-list tick-list--lg">
                <li><?= icon('check', '', 20) ?> Fresh cakes made in our shop</li>
                <li><?= icon('check', '', 20) ?> 100% Halal ingredients</li>
                <li><?= icon('check', '', 20) ?> Gluten-free, eggless and vegan options</li>
                <li><?= icon('check', '', 20) ?> Free message on every cake</li>
            </ul>
            <div class="about-actions">
                <a class="btn btn--primary" href="<?= e(url('shop')) ?>">Shop Cakes <?= icon('arrow', '', 18) ?></a>
                <a class="btn btn--outline" href="<?= e(url('custom-cakes')) ?>">Custom Cakes</a>
            </div>
        </div>
        <div class="about-media">
            <picture>
                <source srcset="<?= e(asset('img/shop-front.webp')) ?>" type="image/webp">
                <img src="<?= e(asset('img/shop-front.jpg')) ?>" alt="The Continental Cake Shop in Greenway, Canberra" width="948" height="1024" loading="lazy">
            </picture>
        </div>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <div class="stats">
            <div><strong><?= e(setting('store_founded')) ?></strong><span>Baking in Canberra since</span></div>
            <div><strong><?= e(setting('google_rating')) ?>/5</strong><span>Google rating</span></div>
            <div><strong>100%</strong><span>Halal ingredients</span></div>
            <div><strong><?= (int) db_value('SELECT COUNT(*) FROM products WHERE is_active = 1') ?></strong><span>Cakes on our menu</span></div>
        </div>
    </div>
</section>

<?php render('footer'); ?>
