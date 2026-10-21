<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$user = require_login();

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'profile') {
        $input = ['name' => str_limit(input('name'), 120), 'phone' => str_limit(input('phone'), 40)];
        $errors = [];
        if ($input['name'] === '') {
            $errors['name'] = 'Please enter your name.';
        }
        if ($input['phone'] !== '' && !valid_au_phone($input['phone'])) {
            $errors['phone'] = 'Please enter an Australian phone number.';
        }
        if ($errors) {
            fail_form($errors, $input, 'account#details');
        }
        db_run('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$input['name'], $input['phone'] ?: null, $user['id']]);
        flash('success', 'Your details have been saved.');
        redirect('account#details');
    }

    if ($action === 'reorder') {
        $order = db_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [(int) input('order_id'), $user['id']]);
        if (!$order) {
            redirect('account');
        }
        $before = cart_count();
        $skipped = cart_reorder($order);
        if (cart_count() > $before) {
            flash('success', 'We added the cakes from ' . $order['order_number'] . ' to your cart at today\'s prices.' . ($skipped ? ' Not available any more: ' . implode(', ', array_unique($skipped)) . '.' : ''));
            redirect('cart');
        }
        flash('error', 'Sorry, the cakes from that order are not available to order online right now.');
        redirect('account');
    }

    if ($action === 'password') {
        $hash = (string) db_value('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = (string) ($_POST['new_password'] ?? '');
        $errors = [];
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
            $errors['current_password'] = 'Your current password is not correct.';
        }
        if (mb_strlen($new) < PASSWORD_MIN_LENGTH) {
            $errors['new_password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }
        if ($errors) {
            fail_form($errors, [], 'account#details');
        }
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('success', 'Your password has been changed.');
        redirect('account#details');
    }
    redirect('account');
}

$errors = take_form_errors();
$orders = get_user_orders((int) $user['id']);
// Conversations with messages, linked to the account or sent from the same email.
$threads = db_all(
    'SELECT t.*, (SELECT body FROM thread_messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) AS last_body
     FROM threads t WHERE (t.user_id = ? OR t.customer_email = ?) AND t.last_message_at IS NOT NULL ORDER BY t.last_message_at DESC LIMIT 20',
    [$user['id'], $user['email']]
);
$progress = ['new', 'confirmed', 'in_progress', 'ready', 'completed'];

$page = ['title' => 'My Account | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container account-head">
        <div>
            <h1 class="page-title">Hi, <?= e(strtok($user['name'], ' ')) ?></h1>
            <p class="page-lead"><?= e($user['email']) ?></p>
        </div>
        <form method="post" action="<?= e(url('logout')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn--outline btn--sm" type="submit">Log Out</button>
        </form>
    </div>
</section>

<section class="section section--compact">
    <div class="container account-layout">
        <div class="account-orders">
            <h2 class="account-title">Your orders</h2>
            <?php if (!$orders): ?>
                <div class="empty-state empty-state--left">
                    <p>You have not placed any orders with this account yet.</p>
                    <a class="btn btn--primary" href="<?= e(url('shop')) ?>">Shop Cakes</a>
                </div>
            <?php endif; ?>
            <?php foreach ($orders as $order): ?>
                <?php $step = array_search($order['order_status'], $progress, true); ?>
                <article class="order-card">
                    <div class="order-card__top">
                        <div>
                            <h3><?= e($order['order_number']) ?></h3>
                            <p><?= e(date('j M Y', strtotime($order['created_at']))) ?> &middot; <?= (int) $order['item_count'] ?> item<?= (int) $order['item_count'] === 1 ? '' : 's' ?> &middot; <?= e(money($order['total'])) ?></p>
                        </div>
                        <div class="order-card__actions">
                            <a class="btn btn--outline btn--sm" href="<?= e(order_url($order)) ?>">View Order</a>
                            <?php if (($order['channel'] ?? 'online') !== 'quote'): ?>
                                <form method="post" action="<?= e(url('account')) ?>">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="reorder"><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button class="btn btn--primary btn--sm" type="submit"><?= icon('bag', '', 16) ?> Order again</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="order-card__pickup"><?= icon('calendar', '', 18) ?> <?= $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup' ?>: <strong><?= e(order_when($order)) ?></strong></p>
                    <?php if ($order['order_status'] === 'cancelled'): ?>
                        <p class="status status--cancelled">Cancelled</p>
                    <?php else: ?>
                        <ol class="order-progress" aria-label="Order progress">
                            <?php foreach ($progress as $i => $status): ?>
                                <li class="<?= $step !== false && $i <= $step ? 'is-done' : '' ?> <?= $i === $step ? 'is-current' : '' ?>"><?= e(order_status_label($order, $status)) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <p class="order-card__payment">Payment: <span class="status status--<?= e($order['payment_status']) ?>"><?= e(PAYMENT_STATUSES[$order['payment_status']]) ?></span></p>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="account-side" id="details">
            <div class="info-card" id="messages">
                <h2>Messages</h2>
                <?php if (!$threads): ?>
                    <p class="field-help">No messages yet. Use "Message us" on an order, or our <a href="<?= e(url('contact')) ?>">contact form</a>.</p>
                <?php else: ?>
                    <ul class="thread-list">
                        <?php foreach ($threads as $t): ?>
                            <li><a href="<?= e(thread_url($t)) ?>"><strong><?= e($t['subject']) ?><?= $t['unread_customer'] ? ' <span class="badge-dot">New reply</span>' : '' ?></strong><span><?= e(mb_strimwidth((string) $t['last_body'], 0, 70, '...')) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <form class="info-card" method="post" action="<?= e(url('account')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <h2>Your details</h2>
                <div class="field">
                    <label class="field-label" for="name">Full name</label>
                    <input class="input" id="name" name="name" type="text" autocomplete="name" value="<?= e(old('name', $user['name'])) ?>" <?= invalid_attr($errors, 'name') ?>>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="field">
                    <label class="field-label" for="phone">Mobile number</label>
                    <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone', (string) $user['phone'])) ?>" <?= invalid_attr($errors, 'phone') ?>>
                    <?= field_error($errors, 'phone') ?>
                </div>
                <button class="btn btn--primary btn--block" type="submit">Save Details</button>
            </form>

            <form class="info-card" method="post" action="<?= e(url('account')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <h2>Change password</h2>
                <div class="field">
                    <label class="field-label" for="current_password">Current password</label>
                    <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" <?= invalid_attr($errors, 'current_password') ?>>
                    <?= field_error($errors, 'current_password') ?>
                </div>
                <div class="field">
                    <label class="field-label" for="new_password">New password</label>
                    <input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password" <?= invalid_attr($errors, 'new_password') ?>>
                    <?= field_error($errors, 'new_password') ?>
                </div>
                <button class="btn btn--outline btn--block" type="submit">Change Password</button>
            </form>
        </aside>
    </div>
</section>

<?php forget_input(); render('footer'); ?>
