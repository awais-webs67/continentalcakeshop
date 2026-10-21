<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'add') {
        $error = cart_add_error((int) input('size_id'));
        $added = $error === null && cart_add((int) input('size_id'), (int) input('qty', '1'), input('message'), (array) ($_POST['options'] ?? []));
        flash($added ? 'success' : 'error', $added ? 'Added to your cart.' : ($error ?? 'Sorry, that cake is no longer available.'));
        redirect($added ? 'cart' : 'shop');
    }
    if ($action === 'addons') {
        foreach ((array) ($_POST['addon'] ?? []) as $addonId => $qty) {
            cart_set_addon((int) $addonId, (int) $qty);
        }
        flash('success', 'Your extras have been updated.');
    }
    if ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $key => $qty) {
            cart_update((string) $key, (int) $qty);
        }
        foreach ((array) ($_POST['size'] ?? []) as $key => $sizeId) {
            cart_change_size((string) $key, (int) $sizeId);
        }
        flash('success', 'Your cart has been updated.');
    }
    if ($action === 'remove') {
        cart_remove(input('key'));
        flash('success', 'Item removed from your cart.');
    }
    redirect('cart');
}

$items = cart_items();
$addonLines = $items ? cart_addon_lines() : [];
$addons = $items ? available_addons() : [];
$chosenAddons = $_SESSION['cart_addons'] ?? [];
$subtotal = cart_subtotal(array_merge($items, $addonLines));

$page = [
    'title'   => 'Your Cart | ' . setting('store_short_name'),
    'noindex' => true,
];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container">
        <h1 class="page-title">Your cart</h1>
    </div>
</section>

<section class="section section--compact">
    <div class="container">
        <?php if ($items && ordering_paused()): ?>
            <div class="alert alert--info"><?= e(ordering_paused_message()) ?></div>
        <?php endif; ?>
        <?php if (!$items): ?>
            <div class="empty-state">
                <?= icon('bag', '', 44) ?>
                <h2>Your cart is empty</h2>
                <p>Have a look at our cakes and pick something delicious.</p>
                <a class="btn btn--primary" href="<?= e(url('shop')) ?>">Shop Cakes</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-main">
                <form class="cart-list" method="post" action="<?= e(url('cart')) ?>" id="cart-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <a class="cart-item__media" href="<?= e(url('cakes/' . $item['slug'])) ?>">
                                <img src="<?= e(product_image_url($item['image'])) ?>" alt="" width="120" height="120" loading="lazy">
                            </a>
                            <div class="cart-item__info">
                                <h2 class="cart-item__title"><a href="<?= e(url('cakes/' . $item['slug'])) ?>"><?= e($item['name']) ?></a></h2>
                                <?php if (count($item['sizes']) > 1): ?>
                                    <label class="cart-item__size">
                                        <span>Size</span>
                                        <select name="size[<?= e($item['key']) ?>]" data-auto-submit>
                                            <?php foreach ($item['sizes'] as $size): ?>
                                                <option value="<?= (int) $size['id'] ?>" <?= (int) $size['id'] === (int) $item['size_id'] ? 'selected' : '' ?>><?= e($size['label']) ?> (<?= e(money($size['price'])) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                <?php else: ?>
                                    <p class="cart-item__meta"><?= e($item['size_label']) ?> &middot; <?= e(money($item['price'])) ?></p>
                                <?php endif; ?>
                                <?php if ($item['options'] !== ''): ?>
                                    <p class="cart-item__options"><?= icon('check', '', 14) ?> <?= e($item['options']) ?></p>
                                <?php endif; ?>
                                <?php if ($item['message'] !== ''): ?>
                                    <p class="cart-item__message"><?= icon('pen', '', 14) ?> &ldquo;<?= e($item['message']) ?>&rdquo;</p>
                                <?php endif; ?>
                                <div class="cart-item__controls">
                                    <div class="qty qty--sm" data-qty>
                                        <button type="button" class="qty__btn" data-qty-step="-1" aria-label="Decrease quantity"><?= icon('minus', '', 16) ?></button>
                                        <input class="qty__input" type="number" name="qty[<?= e($item['key']) ?>]" value="<?= (int) $item['qty'] ?>" min="0" max="<?= CART_MAX_QTY ?>" inputmode="numeric" aria-label="Quantity for <?= e($item['name']) ?>" data-auto-update>
                                        <button type="button" class="qty__btn" data-qty-step="1" aria-label="Increase quantity"><?= icon('plus', '', 16) ?></button>
                                    </div>
                                    <button class="link-btn" type="submit" form="remove-<?= e($item['key']) ?>"><?= icon('trash', '', 16) ?> Remove</button>
                                </div>
                            </div>
                            <p class="cart-item__total"><?= e(money($item['line_total'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <noscript><button class="btn btn--outline btn--sm" type="submit">Update cart</button></noscript>
                </form>

                <?php if ($addons): ?>
                    <form class="addons-box" method="post" action="<?= e(url('cart')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="addons">
                        <h2 class="addons-box__title"><?= icon('gift', '', 20) ?> <?= e(setting('addons_title', 'Add something extra')) ?></h2>
                        <div class="addons-list">
                            <?php foreach ($addons as $addon): $qty = (int) ($chosenAddons[(int) $addon['id']] ?? 0); ?>
                                <div class="addon-row<?= $qty > 0 ? ' is-chosen' : '' ?>">
                                    <div class="addon-row__info">
                                        <strong><?= e($addon['name']) ?></strong>
                                        <?php if ($addon['description']): ?><span><?= e($addon['description']) ?></span><?php endif; ?>
                                    </div>
                                    <span class="addon-row__price"><?= e(money($addon['price'])) ?></span>
                                    <div class="qty qty--sm" data-qty>
                                        <button type="button" class="qty__btn" data-qty-step="-1" aria-label="Fewer <?= e($addon['name']) ?>"><?= icon('minus', '', 16) ?></button>
                                        <input class="qty__input" type="number" name="addon[<?= (int) $addon['id'] ?>]" value="<?= $qty ?>" min="0" max="<?= (int) $addon['max_qty'] ?>" inputmode="numeric" aria-label="How many <?= e($addon['name']) ?>" data-auto-update>
                                        <button type="button" class="qty__btn" data-qty-step="1" aria-label="More <?= e($addon['name']) ?>"><?= icon('plus', '', 16) ?></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <noscript><button class="btn btn--outline btn--sm" type="submit">Save extras</button></noscript>
                    </form>
                <?php endif; ?>
                </div>

                <?php foreach ($items as $item): ?>
                    <form id="remove-<?= e($item['key']) ?>" method="post" action="<?= e(url('cart')) ?>" hidden>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="key" value="<?= e($item['key']) ?>">
                    </form>
                <?php endforeach; ?>

                <aside class="summary-card">
                    <h2 class="summary-card__title">Order summary</h2>
                    <dl class="summary-lines">
                        <?php if ($addonLines): ?>
                            <div><dt>Cakes</dt><dd><?= e(money(cart_subtotal($items))) ?></dd></div>
                            <div><dt>Extras</dt><dd><?= e(money(cart_subtotal($addonLines))) ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
                        <div><dt>Pickup</dt><dd>Free</dd></div>
                    </dl>
                    <div class="summary-total"><span>Total</span><strong><?= e(money($subtotal)) ?></strong></div>
                    <a class="btn btn--primary btn--lg btn--block" href="<?= e(url('checkout')) ?>">Checkout <?= icon('arrow', '', 18) ?></a>
                    <p class="summary-note"><?= icon('calendar', '', 16) ?> You will choose your pickup date and time on the next step.</p>
                    <a class="link-arrow summary-card__back" href="<?= e(url('shop')) ?>">Continue shopping</a>
                    <?php if (!is_logged_in()): ?>
                        <div class="account-prompt">
                            <?= icon('user', '', 20) ?>
                            <div>
                                <strong>Save your order history</strong>
                                <span><a href="<?= e(url('login') . '?next=checkout') ?>">Log in</a> or <a href="<?= e(url('register') . '?next=checkout') ?>">create an account</a> to keep track of your orders.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php render('footer'); ?>
