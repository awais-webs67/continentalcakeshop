<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// A customer's conversation with the shop. The private link in our emails opens it,
// so no login is needed; logged-in customers also reach it from their account.

$thread = get_thread_by_token(input('token'));
if (!$thread) {
    not_found();
}
$self = thread_url($thread);

if (is_post()) {
    verify_csrf();
    $body = trim(str_limit(input('body'), 3000));
    if (is_spam_submission('thread', 10, 10)) {
        flash('error', 'Please wait a moment before sending another message.');
    } elseif (mb_strlen($body) < 2) {
        flash('error', 'Please write your message.');
        remember_input(['body' => $body]);
    } else {
        customer_message($thread, $body);
        forget_input();
        flash('success', 'Message sent. We will reply here and by email.');
    }
    redirect($self . '#reply');
}

db_run('UPDATE threads SET unread_customer = 0 WHERE id = ?', [$thread['id']]);
$messages = get_thread_messages((int) $thread['id']);
$order = $thread['order_id'] ? get_order((int) $thread['order_id']) : null;

$page = [
    'title'   => $thread['subject'] . ' | ' . setting('store_short_name'),
    'noindex' => true,
];
render('header', compact('page'));
?>

<section class="page-hero page-hero--slim">
    <div class="container">
        <p class="kicker">Messages</p>
        <h1 class="page-title"><?= e($thread['subject']) ?></h1>
        <?php if ($order): ?><p class="page-lead"><a class="link-arrow" href="<?= e(order_url($order)) ?>">View order <?= e($order['order_number']) ?> <?= icon('arrow', '', 16) ?></a></p><?php endif; ?>
    </div>
</section>

<section class="section section--compact">
    <div class="container chat">
        <div class="chat__list">
            <?php if (!$messages): ?>
                <p class="chat__empty"><?= icon('mail', '', 22) ?> Ask us anything about <?= e(strtolower($thread['subject'])) ?>. We usually reply within one business day.</p>
            <?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <div class="chat__msg chat__msg--<?= e($m['sender']) ?>">
                    <div class="chat__bubble"><?= nl2br(e($m['body'])) ?></div>
                    <p class="chat__meta"><?= $m['sender'] === 'shop' ? e(setting('store_short_name')) : 'You' ?> &middot; <?= e(date('j M Y, g:ia', strtotime($m['created_at']))) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="chat__form form-card" id="reply" method="post" action="<?= e($self) ?>">
            <?= csrf_field() ?>
            <?= spam_trap_fields() ?>
            <label class="field-label" for="body">Your message</label>
            <textarea class="input" id="body" name="body" rows="4" maxlength="3000" required placeholder="Type your message"><?= e(old('body')) ?></textarea>
            <div class="chat__actions">
                <button class="btn btn--primary" type="submit"><?= icon('mail', '', 18) ?> Send message</button>
                <span class="field-help">Or call us on <a href="tel:<?= e(setting('store_phone_e164')) ?>"><?= e(setting('store_phone')) ?></a></span>
            </div>
        </form>
    </div>
</section>

<?php forget_input(); render('footer'); ?>
