<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$product = get_product_by_slug(input('slug'));
$sizes = $product ? get_product_sizes((int) $product['id']) : [];
$enquiry = $product && (int) $product['is_enquiry'] === 1;
if (!$product || (!$sizes && !$enquiry)) {
    not_found();
}

$related = get_related_products($product);
$options = $enquiry ? [] : get_product_options((int) $product['id']);
$soldOut = product_sold_out($product);
$paused = ordering_paused();
$leadHours = $product['lead_time_hours'] !== null ? (int) $product['lead_time_hours'] : (int) setting('min_lead_hours', '24');
$prices = array_column($sizes, 'price');
$selected = $sizes[0] ?? null;
$deliveryOn = setting_bool('delivery_enabled');
$sizeText = implode(', ', array_map(static fn ($s) => $s['label'] . ($s['servings'] ? ' (' . strtolower($s['servings']) . ')' : ''), $sizes));

$crumbs = [
    ['Home', url()],
    ['Shop', url('shop')],
    [$product['category_name'], url('shop/' . $product['category_slug'])],
    [$product['name'], product_url($product)],
];

$page = [
    'title'       => $product['meta_title'] ?: $product['name'] . ' | Order Online in Canberra | ' . setting('store_short_name'),
    'description' => $product['meta_description'] ?: ($enquiry
        ? sprintf('%s made to order in Greenway, Canberra. 100%% Halal. Send us your details for a quote.', $product['name'])
        : sprintf(
            '%s in %s from %s. 100%% Halal. Order online for pickup in Greenway%s.',
            $product['name'], $sizeText, money(min($prices)), $deliveryOn ? ' or delivery across Canberra' : ''
        )),
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
                <p class="product-info__price" data-price><?= $enquiry ? 'Price on request' : e(money($selected['price'])) ?></p>
                <?php if ($product['short_description']): ?>
                    <p class="product-info__lead"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <?php if ($enquiry): ?>
                <div class="enquiry-box">
                    <p>This cake is made to order. Tell us the date, size and any changes you would like and we will send you a quote.</p>
                    <div class="enquiry-box__actions">
                        <a class="btn btn--primary btn--lg" href="<?= e(enquiry_url($product)) ?>"><?= icon('mail', '', 20) ?> Enquire about this cake</a>
                        <a class="btn btn--outline btn--lg" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 20) ?> <?= e(setting('store_phone')) ?></a>
                    </div>
                </div>
                <?php elseif ($soldOut || $paused): ?>
                <div class="enquiry-box enquiry-box--notice">
                    <p><strong><?= $soldOut ? 'Sold out' . (product_back_label($product) !== '' ? ', back ' . e(product_back_label($product)) : ' for now') : 'Online orders are paused' ?></strong><br>
                    <?= $soldOut ? 'This cake is not available to order online right now. Call us and we will tell you when it is back, or have a look at our other cakes.' : e(ordering_paused_message()) ?></p>
                    <div class="enquiry-box__actions">
                        <a class="btn btn--primary btn--lg" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 20) ?> <?= e(setting('store_phone')) ?></a>
                        <a class="btn btn--outline btn--lg" href="<?= e(url('shop/' . $product['category_slug'])) ?>">More <?= e(strtolower($product['category_name'])) ?></a>
                    </div>
                </div>
                <?php else: ?>
                <form class="product-form" method="post" action="<?= e(url('cart')) ?>" data-product-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <fieldset class="field-group">
                        <legend class="field-label">Choose a size</legend>
                        <div class="size-options">
                            <?php foreach ($sizes as $i => $size): ?>
                                <label class="size-option">
                                    <input type="radio" name="size_id" value="<?= (int) $size['id'] ?>" data-price="<?= e(money($size['price'])) ?>" data-amount="<?= e(number_format((float) $size['price'], 2, '.', '')) ?>" <?= $i === 0 ? 'checked' : '' ?> required>
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

                    <?php if ($options): ?>
                        <fieldset class="field-group">
                            <legend class="field-label">Options <span class="field-hint">Optional</span></legend>
                            <div class="option-list">
                                <?php foreach ($options as $option): ?>
                                    <label class="option-check">
                                        <input type="checkbox" name="options[]" value="<?= (int) $option['id'] ?>" data-extra="<?= e(number_format((float) $option['price'], 2, '.', '')) ?>">
                                        <span class="option-check__box" aria-hidden="true"><?= icon('check', '', 14) ?></span>
                                        <span class="option-check__name"><?= e($option['name']) ?></span>
                                        <span class="option-check__price"><?= (float) $option['price'] > 0 ? '+' . e(money($option['price'])) : 'Free' ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php endif; ?>

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
                <?php endif; ?>

                <ul class="product-facts">
                    <li><?= icon('calendar', '', 20) ?><span><strong>Order <?= e(notice_label($leadHours)) ?> ahead.</strong> Choose your date at checkout, up to <?= (int) setting('max_advance_days', '60') ?> days in advance.</span></li>
                    <li><?= icon('store', '', 20) ?><span><strong>Free pickup</strong> from <?= e(store_address_line()) ?> at a time you choose.</span></li>
                    <?php if ($deliveryOn): ?>
                        <li><?= icon('truck', '', 20) ?><span><strong>Delivery</strong> <?= e(lcfirst(setting('delivery_note', 'All over Canberra'))) ?><?= (float) setting('delivery_fee') > 0 ? ' for ' . e(money(setting('delivery_fee'))) : '' ?>. We confirm the time with you.</span></li>
                    <?php endif; ?>
                    <li><?= icon('halal', '', 20) ?><span><strong>100% Halal</strong> ingredients<?= (int) $product['allow_message'] === 1 ? ', with free writing on the cake' : '' ?>.</span></li>
                </ul>

                <div class="product-accordion">
                    <?php if ($product['description']): ?>
                        <details open>
                            <summary>About this cake <?= icon('plus', 'faq-item__icon', 18) ?></summary>
                            <div class="prose"><?= rich_text($product['description']) ?></div>
                        </details>
                    <?php endif; ?>
                    <details>
                        <summary>Dietary and allergens <?= icon('plus', 'faq-item__icon', 18) ?></summary>
                        <div class="prose">
                            <p>Made with 100% Halal ingredients. Gluten-free, eggless and vegan versions may be possible on request, so please call <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a> before you order.</p>
                            <p>Our kitchen handles milk, eggs, wheat, soy and nuts, so we cannot guarantee any cake is free from traces. <a href="<?= e(url('allergens')) ?>">Read our allergen information</a>.</p>
                        </div>
                    </details>
                    <details>
                        <summary><?= $deliveryOn ? 'Pickup, delivery and storage' : 'Pickup and storage' ?> <?= icon('plus', 'faq-item__icon', 18) ?></summary>
                        <div class="prose">
                            <p>Collect from <?= e(store_address_line()) ?> at the time you choose at checkout.<?= $deliveryOn ? ' For delivery, choose a date and a preferred time of day and we will email you the delivery time.' : '' ?></p>
                            <p>Carry the box flat and keep the cake in the fridge until you are ready to serve. Need to change the date? Call us as early as possible.</p>
                        </div>
                    </details>
                </div>
            </div>
        </div>

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
