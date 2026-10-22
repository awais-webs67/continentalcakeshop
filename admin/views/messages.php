<?php /** @var array $result @var string $filter @var string $q @var int $unread */ ?>
<div class="page-head">
    <div><h1>Messages</h1><p>Conversations with customers. They write from the contact page, their order page or the "Reply to us" button in our emails.</p></div>
</div>
<section class="card">
    <nav class="tabs" data-live="tabs">
        <?php foreach (['' => 'Open', 'unread' => 'Unread', 'closed' => 'Closed', 'all' => 'All'] as $key => $label): ?>
            <a href="<?= e(admin_url('messages', $key !== '' ? ['filter' => $key] : [])) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?><?php if ($key === 'unread' && $unread): ?><span><?= (int) $unread ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e(admin_url('messages')) ?>">
        <?php if ($filter !== ''): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or subject">
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <div data-live="list">
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('mail', '', 32) ?><p>No conversations here.</p></div>
    <?php else: ?>
        <div class="inbox">
        <?php foreach ($result['rows'] as $t): ?>
            <a class="inbox__row<?= $t['unread_admin'] ? ' is-unread' : '' ?>" href="<?= e(admin_url('messages/view', ['id' => $t['id']])) ?>">
                <span class="message__avatar" style="background:<?= e(avatar_colour($t['customer_name'])) ?>"><?= e(mb_strtoupper(mb_substr($t['customer_name'], 0, 1))) ?></span>
                <span class="inbox__main">
                    <span class="inbox__top"><strong><?= e($t['customer_name']) ?></strong><span class="muted"><?= e(time_ago($t['last_message_at'])) ?></span></span>
                    <span class="inbox__subject"><?= e($t['subject']) ?><?= $t['status'] === 'closed' ? ' ' . status_badge('inactive', 'Closed') : '' ?></span>
                    <span class="inbox__preview"><?= $t['last_sender'] === 'shop' ? '<em>You:</em> ' : '' ?><?= e(mb_strimwidth((string) $t['last_body'], 0, 120, '...')) ?></span>
                </span>
                <?php if ($t['unread_admin']): ?><span class="badge badge--new">New</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
    </div>
</section>
