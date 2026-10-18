<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'add') {
        $added = cart_add((int) input('size_id'), (int) input('qty', '1'), input('message'));
        flash($added ? 'success' : 'error', $added ? 'Added to your cart.' : 'Sorry, that cake is no longer available.');
        redirect($added ? 'cart' : 'shop');
    }
    if ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $key => $qty) {
            cart_update((string) $key, (int) $qty);
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
$subtotal = cart_subtotal($items);

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
        <?php if (!$items): ?>
            <div class="empty-state">
                <?= icon('bag', '', 44) ?>
                <h2>Your cart is empty</h2>
                <p>Have a look at our cakes and pick something delicious.</p>
                <a class="btn btn--primary" href="<?= e(url('shop')) ?>">Shop Cakes</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
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
                                <p class="cart-item__meta"><?= e($item['size_label']) ?><?= $item['servings'] ? ' &middot; ' . e($item['servings']) : '' ?> &middot; <?= e(money($item['price'])) ?> each</p>
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
                        <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
                        <div><dt>Pickup</dt><dd>Free</dd></div>
                    </dl>
                    <div class="summary-total"><span>Total</span><strong><?= e(money($subtotal)) ?></strong></div>
                    <a class="btn btn--primary btn--lg btn--block" href="<?= e(url('checkout')) ?>">Checkout <?= icon('arrow', '', 18) ?></a>
                    <p class="summary-note"><?= icon('calendar', '', 16) ?> You will choose your pickup date and time on the next step.</p>
                    <a class="link-arrow summary-card__back" href="<?= e(url('shop')) ?>">Continue shopping</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php render('footer'); ?>
