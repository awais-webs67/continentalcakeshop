<?php
/** @var array $person @var array $threads @var array $threadsById @var array $messages @var array $orders @var array $requests
 *  @var array $choices @var string $selected @var bool $isOpen @var ?string $customerLink @var string $self */
$phone = preg_replace('/[^\d+]/', '', (string) $person['phone']);
$lastThread = $messages ? (int) end($messages)['thread_id'] : 0;
// Topics with at least one message (an order's chat is made with its confirmation email, empty until someone writes).
$topicCount = count(array_filter($threads, static fn ($t) => $t['last_message_at'] !== null));
$topicText = $topicCount . ' ' . ($topicCount === 1 ? 'topic' : 'topics');
?>
<a class="crumb" href="<?= e(admin_url('messages')) ?>"><?= icon('arrow', '', 14) ?> Messages</a>
<div class="page-head">
    <div><h1><?= e($person['name']) ?></h1><p><?= e($person['email']) ?> &middot; <?= status_badge($isOpen ? 'active' : 'inactive', $isOpen ? 'Open' : 'Closed') ?> &middot; <?= e($topicText) ?></p></div>
    <div class="page-head__actions">
        <?php if ($customerLink): ?><a class="btn btn--secondary" href="<?= e($customerLink) ?>"><?= icon('user', '', 18) ?> Customer page</a><?php endif; ?>
        <?php if ($threads): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="email" value="<?= e($person['email']) ?>"><input type="hidden" name="action" value="status"><button class="btn btn--secondary" type="submit"><?= $isOpen ? 'Close chat' : 'Open again' ?></button></form><?php endif; ?>
    </div>
</div>
<div class="grid-main">
    <section class="card chat-card" data-chat-screen>
        <header class="chat-card__head">
            <a class="chat-card__back" href="<?= e(admin_url('messages')) ?>" aria-label="Back to Messages"><?= icon('arrow', '', 22) ?></a>
            <span class="message__avatar" style="background:<?= e(avatar_colour($person['name'])) ?>"><?= e(mb_strtoupper(mb_substr($person['name'], 0, 1))) ?></span>
            <div class="chat-card__who"><strong><?= e($person['name']) ?></strong><span><?= e($topicText) ?> &middot; <?= e($person['email']) ?></span></div>
            <?php if ($phone !== ''): ?><a class="icon-button" href="tel:<?= e($phone) ?>" aria-label="Call <?= e($person['name']) ?>"><?= icon('phone', '', 19) ?></a><?php endif; ?>
            <?php if ($customerLink): ?><a class="icon-button" href="<?= e($customerLink) ?>" aria-label="Customer page"><?= icon('user', '', 19) ?></a><?php endif; ?>
        </header>
        <div class="card__body chat-admin" data-chat data-chat-url="<?= e($self) ?>" data-chat-threads=",<?= e(implode(',', array_keys($threadsById))) ?>," data-chat-email="<?= e($person['email']) ?>" data-last-id="<?= (int) ($messages ? end($messages)['id'] : 0) ?>" data-last-thread="<?= $lastThread ?>" aria-live="polite">
            <?php if (!$messages): ?><p class="hint chat-admin__empty">No messages with <?= e($person['name']) ?> yet. Choose what it is about below and write the first one; they get it by email with a button to reply.</p><?php endif; ?>
            <?= person_chat_html($messages, $threadsById) ?>
        </div>
        <form class="card__body form-stack chat-admin__form" id="reply" method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-chat-form>
            <?= csrf_field() ?><input type="hidden" name="email" value="<?= e($person['email']) ?>"><input type="hidden" name="action" value="reply">
            <label class="chat-about"><span><?= icon('tag', '', 15) ?> Reply about</span>
                <select class="input" name="about" data-chat-about>
                    <?php foreach ($choices as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= selected($value === $selected) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p class="chat-picked" data-chat-picked hidden><?= icon('file', '', 16) ?> <span data-chat-picked-name></span> <button type="button" data-chat-unpick aria-label="Remove the file"><?= icon('close', '', 16) ?></button></p>
            <div class="chat-admin__row">
                <label class="chat-attach" title="Send a photo or PDF">
                    <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,image/heic,application/pdf" data-chat-file>
                    <?= icon('clip', '', 21) ?><span class="sr-only">Send a photo or PDF</span>
                </label>
                <label class="sr-only" for="reply-body">Reply to <?= e($person['name']) ?></label>
                <textarea class="input chat-admin__input" id="reply-body" name="body" rows="1" placeholder="Reply to <?= e($person['name']) ?>" data-autogrow></textarea>
                <button class="chat-admin__send" type="submit" aria-label="Send reply"><?= icon('arrow', '', 21) ?></button>
            </div>
            <p class="field-error" data-chat-error hidden></p>
            <div class="form-actions chat-admin__actions">
                <label class="inline-check"><input type="checkbox" name="close" value="1"> Close after sending</label>
                <span class="chat-admin__keys">They get it by email too, unless the chat is open on their side &middot; Enter sends, Shift+Enter for a new line</span>
                <span class="live-dot" title="New messages appear here automatically"><span></span> Live</span>
            </div>
            <p class="hint" data-chat-note hidden></p>
        </form>
    </section>
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Customer</h2><?php if ($customerLink): ?><a class="link-btn" href="<?= e($customerLink) ?>">All details</a><?php endif; ?></div>
            <div class="card__body"><dl class="dl">
                <dt>Name</dt><dd><?= e($person['name']) ?></dd>
                <dt>Email</dt><dd><?= e($person['email']) ?></dd>
                <?php if ($phone !== ''): ?><dt>Phone</dt><dd><a href="tel:<?= e($phone) ?>"><?= e($person['phone']) ?></a></dd><?php endif; ?>
            </dl></div>
        </section>
        <?php if ($orders): ?>
        <section class="card">
            <div class="card__head"><h2>Orders <span class="count"><?= count($orders) ?></span></h2></div>
            <ul class="mini-list">
                <?php foreach (array_slice($orders, 0, 8) as $o): ?>
                    <li><a href="<?= e(admin_url('orders/view', ['id' => $o['id']])) ?>"><strong><?= e($o['order_number']) ?></strong><span><?= e(order_when($o)) ?> &middot; <?= e(money($o['total'])) ?></span></a><?= status_badge($o['order_status'], order_status_label($o)) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
        <?php if ($requests): ?>
        <section class="card">
            <div class="card__head"><h2>Custom cake requests <span class="count"><?= count($requests) ?></span></h2></div>
            <ul class="mini-list">
                <?php foreach ($requests as $r): ?>
                    <li><a href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><strong><?= e($r['occasion']) ?> cake</strong><span>For <?= e(date('j M Y', strtotime($r['event_date']))) ?></span></a><?= status_badge($r['status'], CUSTOM_STATUSES[$r['status']]) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
        <p class="hint">All of <?= e($person['name']) ?>'s messages are here: the contact page, each order and custom cake, and their emails. Each one shows what it was about.</p>
    </div>
</div>
