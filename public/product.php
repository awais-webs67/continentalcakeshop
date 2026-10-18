<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$product = get_product_by_slug(input('slug'));
$sizes = $product ? get_product_sizes((int) $product['id']) : [];
if (!$product || !$sizes) {
    not_found();
}

$related = get_related_products($product);
$leadHours = $product['lead_time_hours'] !== null ? (int) $product['lead_time_hours'] : (int) setting('min_lead_hours', '24');
$prices = array_column($sizes, 'price');
$selected = $sizes[0];

$crumbs = [
    ['Home', url()],
    ['Shop', url('shop')],
    [$product['category_name'], url('shop/' . $product['category_slug'])],
    [$product['name'], product_url($product)],
];

$page = [
    'title'       => $product['meta_title'] ?: $product['name'] . ' | Order Online in Canberra | ' . setting('store_short_name'),
    'description' => $product['meta_description'] ?: sprintf(
        '%s %s from %s. 100%% Halal. Order online and pick up from Greenway, Canberra.',
        $product['name'], '(' . implode(', ', array_column($sizes, 'label')) . ')', money(min($prices))
    ),
    'canonical'   => product_url($product),
    'image'       => product_image_url($product['image']),
    'type'        => 'product',
    'schema'      => [product_schema($product, $sizes), breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="section section--compact product-page">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>

        <div class="product-layout">
            <div class="product-gallery">
                <img src="<?= e(product_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" width="900" height="900" fetchpriority="high">
                <?php if ($product['badge']): ?>
                    <span class="product-card__badge"><?= e($product['badge']) ?></span>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <a class="product-info__category" href="<?= e(url('shop/' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
                <h1 class="product-info__title"><?= e($product['name']) ?></h1>
                <p class="product-info__price" data-price><?= e(money($selected['price'])) ?></p>
                <?php if ($product['short_description']): ?>
                    <p class="product-info__lead"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <form class="product-form" method="post" action="<?= e(url('cart')) ?>" data-product-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <fieldset class="field-group">
                        <legend class="field-label">Choose a size</legend>
                        <div class="size-options">
                            <?php foreach ($sizes as $i => $size): ?>
                                <label class="size-option">
                                    <input type="radio" name="size_id" value="<?= (int) $size['id'] ?>" data-price="<?= e(money($size['price'])) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                                    <span class="size-option__box">
                                        <span class="size-option__label"><?= e($size['label']) ?></span>
                                        <?php if ($size['servings']): ?>
                                            <span class="size-option__servings"><?= e($size['servings']) ?></span>
                                        <?php endif; ?>
                                        <span class="size-option__price"><?= e(money($size['price'])) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <?php if ((int) $product['allow_message'] === 1): ?>
                        <div class="field">
                            <label class="field-label" for="cake-message">Message on the cake <span class="field-hint">Free, optional</span></label>
                            <input class="input" id="cake-message" type="text" name="message" maxlength="<?= CAKE_MESSAGE_MAX ?>" placeholder="e.g. Happy Birthday Sarah" data-char-count>
                            <p class="field-help"><span data-char-left><?= CAKE_MESSAGE_MAX ?></span> characters left</p>
                        </div>
                    <?php endif; ?>

                    <div class="product-form__buy">
                        <div class="qty" data-qty>
                            <button type="button" class="qty__btn" data-qty-step="-1" aria-label="Decrease quantity"><?= icon('minus', '', 18) ?></button>
                            <input class="qty__input" type="number" name="qty" value="1" min="1" max="<?= CART_MAX_QTY ?>" inputmode="numeric" aria-label="Quantity">
                            <button type="button" class="qty__btn" data-qty-step="1" aria-label="Increase quantity"><?= icon('plus', '', 18) ?></button>
                        </div>
                        <button class="btn btn--primary btn--lg product-form__submit" type="submit"><?= icon('bag', '', 20) ?> Add to Cart</button>
                    </div>
                </form>

                <ul class="product-facts">
                    <li><?= icon('calendar', '', 20) ?><span><strong>Order <?= $leadHours >= 24 ? (int) ceil($leadHours / 24) . ' day' . ($leadHours >= 48 ? 's' : '') : $leadHours . ' hours' ?> ahead.</strong> Pick your pickup date and time at checkout.</span></li>
                    <li><?= icon('store', '', 20) ?><span><strong>Pickup</strong> from <?= e(store_address_line()) ?>.</span></li>
                    <li><?= icon('halal', '', 20) ?><span><strong>100% Halal</strong> ingredients.</span></li>
                    <li><?= icon('leaf', '', 20) ?><span><strong>Need gluten-free, eggless or vegan?</strong> Call <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a> before you order.</span></li>
                </ul>
            </div>
        </div>

        <?php if ($product['description']): ?>
            <div class="product-description">
                <h2>About this cake</h2>
                <?= nl2br(e($product['description'])) ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
<section class="section related">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">More <?= e(strtolower($product['category_name'])) ?></h2>
            <a class="link-arrow" href="<?= e(url('shop/' . $product['category_slug'])) ?>">View all <?= icon('arrow', '', 18) ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $product): ?>
                <?php render('partials/product-card', compact('product')); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php render('footer'); ?>
