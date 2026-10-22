<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$product = get_product_by_slug(input('slug'));
$sizes = $product ? get_product_sizes((int) $product['id']) : [];
$enquiry = $product && (int) $product['is_enquiry'] === 1;
if (!$product || (!$sizes && !$enquiry)) {
    not_found();
}

$related = with_card_photos(get_related_products($product));
$options = $enquiry ? [] : get_product_options((int) $product['id']);
$soldOut = product_sold_out($product);
$paused = ordering_paused();
$leadHours = max((int) setting('min_lead_hours', '24'), (int) ($product['lead_time_hours'] ?? 0)); // same rule as cart_lead_hours()
$selected = $sizes[0] ?? null;
$deliveryOn = setting_bool('delivery_enabled');

$crumbs = [
    ['Home', url()],
    ['Shop', url('shop')],
    [$product['category_name'], url('shop/' . $product['category_slug'])],
    [$product['name'], product_url($product)],
];

$photos = array_merge(
    [['image' => $product['image'], 'alt' => product_image_alt($product)]],
    array_map(static fn ($i) => ['image' => $i['image'], 'alt' => $i['alt'] ?: $product['name'] . ' photo ' . ((int) $i['sort_order'] + 2)], get_product_images((int) $product['id']))
);
$photos = array_values(array_filter($photos, static fn ($p) => !empty($p['image']))) ?: [['image' => null, 'alt' => $product['name']]];

$page = [
    'title'       => $product['meta_title'] ?: product_default_title($product),
    'description' => $product['meta_description'] ?: product_default_description($product, $sizes),
    'canonical'   => product_url($product),
    'image'       => product_image_url($product['image']),
    'type'        => 'product',
    'schema'      => [product_schema($product, $sizes, array_column($photos, 'image')), breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="section section--compact product-page">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>

        <div class="product-layout">
            <div class="product-gallery" data-gallery>
                <div class="product-gallery__main">
                    <div class="product-gallery__slides" data-gallery-slides>
                        <?php foreach ($photos as $i => $photo): ?>
                            <img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['alt']) ?>" width="900" height="900" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($soldOut): ?>
                        <span class="product-card__badge product-card__badge--soldout">Sold out</span>
                    <?php elseif ($product['badge']): ?>
                        <span class="product-card__badge"><?= e($product['badge']) ?></span>
                    <?php endif; ?>
                    <?php if (count($photos) > 1): ?>
                        <button type="button" class="gallery-arrow gallery-arrow--prev" data-gallery-prev aria-label="Previous photo"><?= icon('chevron', '', 22) ?></button>
                        <button type="button" class="gallery-arrow gallery-arrow--next" data-gallery-next aria-label="Next photo"><?= icon('chevron', '', 22) ?></button>
                        <span class="gallery-count" aria-live="polite"><span data-gallery-index>1</span> / <?= count($photos) ?></span>
                    <?php endif; ?>
                    <?php if ($photos[0]['image']): ?>
                        <button type="button" class="gallery-zoom" data-gallery-zoom aria-label="See the photo larger"><?= icon('search', '', 20) ?></button>
                    <?php endif; ?>
                </div>
                <?php if (count($photos) > 1): ?>
                    <div class="product-gallery__thumbs" role="list">
                        <?php foreach ($photos as $i => $photo): ?>
                            <button type="button" class="product-gallery__thumb<?= $i === 0 ? ' is-active' : '' ?>" role="listitem" data-gallery-thumb="<?= $i ?>" aria-label="Show photo <?= $i + 1 ?> of <?= count($photos) ?>">
                                <img src="<?= e(product_image_url($photo['image'])) ?>" alt="" width="120" height="120" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <dialog class="lightbox" data-lightbox aria-label="<?= e($product['name']) ?> photos">
                    <button type="button" class="lightbox__close" data-lightbox-close aria-label="Close"><?= icon('close', '', 24) ?></button>
                    <img src="" alt="" data-lightbox-img>
                    <?php if (count($photos) > 1): ?>
                        <button type="button" class="gallery-arrow gallery-arrow--prev" data-lightbox-prev aria-label="Previous photo"><?= icon('chevron', '', 24) ?></button>
                        <button type="button" class="gallery-arrow gallery-arrow--next" data-lightbox-next aria-label="Next photo"><?= icon('chevron', '', 24) ?></button>
                    <?php endif; ?>
                </dialog>
            </div>

            <div class="product-info">
                <a class="product-info__category" href="<?= e(url('shop/' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
                <h1 class="product-info__title"><?= e($product['name']) ?></h1>
                <div class="product-info__price-row">
                    <p class="product-info__price" data-price><?= $enquiry ? 'Price on request' : e(money($selected['price'])) ?></p>
                    <?php if (!$enquiry && count($sizes) > 1): ?><span class="product-info__sizes"><?= count($sizes) ?> sizes from <?= e(money(min(array_column($sizes, 'price')))) ?></span><?php endif; ?>
                </div>
                <?php if (setting('google_rating') !== ''): $stars = (float) setting('google_rating'); ?>
                    <a class="product-rating" href="<?= e(setting('google_reviews_url') ?: url('about')) ?>"<?= setting('google_reviews_url') !== '' ? ' target="_blank" rel="noopener"' : '' ?>>
                        <span class="product-rating__stars" style="--rating:<?= e((string) $stars) ?>" aria-hidden="true">★★★★★</span>
                        <strong><?= e(setting('google_rating')) ?></strong>
                        <span><?= setting('google_review_count') !== '' ? e(setting('google_review_count')) . ' ' : '' ?>Google reviews</span>
                    </a>
                <?php endif; ?>
                <ul class="product-badges">
                    <li><?= icon('halal', '', 16) ?> 100% Halal</li>
                    <li><?= icon('cake', '', 16) ?> Baked fresh in Greenway</li>
                    <?php if ((int) $product['allow_message'] === 1): ?><li><?= icon('pen', '', 16) ?> Free writing</li><?php endif; ?>
                    <?php if (array_filter($options, static fn ($o) => stripos($o['name'], 'egg') !== false)): ?><li><?= icon('leaf', '', 16) ?> Eggless option</li><?php endif; ?>
                </ul>
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
                        <button class="btn btn--primary btn--lg product-form__submit" type="submit" data-buy-main><?= icon('bag', '', 20) ?> Add to Cart</button>
                    </div>
                </form>
                <div class="buy-bar" data-buy-bar hidden>
                    <span class="buy-bar__info"><strong><?= e($product['name']) ?></strong><span data-price-mirror><?= e(money($selected['price'])) ?></span></span>
                    <button class="btn btn--primary" type="button" data-buy-bar-btn><?= icon('bag', '', 18) ?> Add to Cart</button>
                </div>
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
