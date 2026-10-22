<?php /** @var array $thread @var array $messages @var ?array $order @var ?array $request */ ?>
<a class="crumb" href="<?= e(admin_url('messages')) ?>"><?= icon('arrow', '', 14) ?> Messages</a>
<div class="page-head">
    <div><h1><?= e($thread['subject']) ?></h1><p><?= e($thread['customer_name']) ?> &middot; <?= status_badge($thread['status'] === 'open' ? 'active' : 'inactive', ucfirst($thread['status'])) ?></p></div>
    <div class="page-head__actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $thread['id'] ?>"><input type="hidden" name="action" value="status"><button class="btn btn--secondary" type="submit"><?= $thread['status'] === 'open' ? 'Close conversation' : 'Open again' ?></button></form>
    </div>
</div>
<div class="grid-main">
    <section class="card chat-card">
        <div class="card__body chat-admin" data-chat data-chat-url="<?= e(admin_url('messages/view', ['id' => $thread['id']])) ?>" data-last-id="<?= (int) ($messages ? end($messages)['id'] : 0) ?>" aria-live="polite">
            <?php if (!$messages): ?><p class="hint chat-admin__empty">No messages yet. Write the first one below; the customer gets it by email with a button to reply.</p><?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <?= chat_bubble_html($m, $thread, 'admin') ?>
            <?php endforeach; ?>
        </div>
        <form class="card__body form-stack chat-admin__form" id="reply" method="post" data-chat-form>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $thread['id'] ?>"><input type="hidden" name="action" value="reply">
            <label class="sr-only" for="reply-body">Reply to <?= e($thread['customer_name']) ?></label>
            <textarea class="input chat-admin__input" id="reply-body" name="body" rows="2" required placeholder="Reply to <?= e($thread['customer_name']) ?>. They get it by email too, unless the chat is open on their side." data-autogrow></textarea>
            <p class="field-error" data-chat-error hidden></p>
            <div class="form-actions chat-admin__actions">
                <button class="btn btn--primary" type="submit"><?= icon('mail', '', 18) ?> Send reply</button>
                <label class="inline-check"><input type="checkbox" name="close" value="1"> Close after sending</label>
                <span class="chat-admin__keys">Enter sends &middot; Shift+Enter for a new line</span>
                <span class="live-dot" title="New messages appear here automatically"><span></span> Live</span>
            </div>
            <p class="hint" data-chat-note hidden></p>
        </form>
    </section>
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Customer</h2></div>
            <div class="card__body"><dl class="dl">
                <dt>Name</dt><dd><?= e($thread['customer_name']) ?></dd>
                <dt>Email</dt><dd><a href="mailto:<?= e($thread['customer_email']) ?>"><?= e($thread['customer_email']) ?></a></dd>
                <?php if ($thread['customer_phone']): ?><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $thread['customer_phone'])) ?>"><?= e($thread['customer_phone']) ?></a></dd><?php endif; ?>
                <?php if ($order): ?><dt>Order</dt><dd><a href="<?= e(admin_url('orders/view', ['id' => $order['id']])) ?>"><?= e($order['order_number']) ?></a><br><span class="muted"><?= e(order_when($order)) ?></span></dd><?php endif; ?>
                <?php if ($request): ?><dt>Request</dt><dd><a href="<?= e(admin_url('custom-requests/view', ['id' => $request['id']])) ?>"><?= e($request['occasion']) ?> cake</a><br><span class="muted"><?= e(date('j M Y', strtotime($request['event_date']))) ?></span></dd><?php endif; ?>
            </dl></div>
        </section>
        <p class="hint">The customer sees this conversation at a private link in their emails<?= $thread['user_id'] ? ' and in their account' : '' ?>.</p>
    </div>
</div>
