<?php /** @var array $result @var string $filter @var string $q @var int $unread @var bool $inboxOn @var array $inboxShops */ ?>
<div class="page-head">
    <div><h1>Messages</h1><p>One chat per customer, with everything they sent: the contact page, their orders, custom cakes<?= $inboxOn ? ' and their emails' : '' ?>.</p></div>
    <div class="page-head__actions">
        <?php if ($inboxOn): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="check_inbox"><button class="btn btn--secondary" type="submit"><?= icon('mail', '', 18) ?> Check emails now</button></form>
        <?php elseif ((current_admin()['role'] ?? '') === 'owner' && !$inboxShops): ?>
            <a class="btn btn--secondary" href="<?= e(admin_url('branches')) ?>" title="Show customers' emails here too: Branches > shop > Email"><?= icon('mail', '', 18) ?> Get emails here too</a>
        <?php endif; ?>
    </div>
</div>
<?php if ($inboxShops): ?>
<div class="inbox-switches">
    <?php foreach ($inboxShops as $shop): $on = inbox_enabled($shop); $account = inbox_account($shop); ?>
        <form method="post" class="inbox-switch<?= $on ? ' is-on' : '' ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="inbox_switch"><input type="hidden" name="branch_id" value="<?= (int) $shop['id'] ?>"><input type="hidden" name="on" value="<?= $on ? '0' : '1' ?>">
            <span class="inbox-switch__text"><?= icon('mail', '', 17) ?> <strong>Customers' emails in Messages<?= count(branches()) > 1 ? ' · ' . e($shop['name']) : '' ?></strong><small><?= e($account['address']) ?> &middot; <?= $on ? 'On: new emails come in here' : 'Off: emails stay in your email app only' ?></small></span>
            <button class="switch" type="submit" role="switch" aria-checked="<?= $on ? 'true' : 'false' ?>" aria-label="<?= $on ? 'Turn off' : 'Turn on' ?> customers' emails in Messages"><span class="switch__track"></span><span><?= $on ? 'On' : 'Off' ?></span></button>
        </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<section class="card">
    <nav class="tabs" data-live="tabs">
        <?php foreach (['' => 'Open', 'unread' => 'Unread', 'closed' => 'Closed', 'all' => 'All'] as $key => $label): ?>
            <a href="<?= e(admin_url('messages', $key !== '' ? ['filter' => $key] : [])) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?><?php if ($key === 'unread' && $unread): ?><span><?= (int) $unread ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e(admin_url('messages')) ?>">
        <?php if ($filter !== ''): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, phone or order number">
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <div data-live="list">
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('mail', '', 32) ?><p>No chats here.</p></div>
    <?php else: ?>
        <div class="inbox">
        <?php foreach ($result['rows'] as $p): ?>
            <a class="inbox__row<?= $p['unread'] ? ' is-unread' : '' ?>" href="<?= e(admin_url('messages/view', ['email' => $p['email']])) ?>">
                <span class="message__avatar" style="background:<?= e(avatar_colour($p['name'])) ?>"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                <span class="inbox__main">
                    <span class="inbox__top"><strong><?= e($p['name']) ?></strong><span class="muted"><?= e(time_ago($p['last_at'])) ?></span></span>
                    <span class="inbox__subject"><?= branch_tag((int) $p['branch_id']) ?> <?= e($p['topic']) ?><?= (int) $p['topics'] > 1 ? ' <span class="muted">&middot; ' . (int) $p['topics'] . ' topics</span>' : '' ?><?= !(int) $p['open_topics'] ? ' ' . status_badge('inactive', 'Closed') : '' ?></span>
                    <span class="inbox__preview"><?= ($p['last']['sender'] ?? '') === 'shop' ? '<em>You:</em> ' : '' ?><?= e(mb_strimwidth((string) ($p['last']['preview'] ?? ''), 0, 120, '...')) ?></span>
                </span>
                <?php if ($p['unread']): ?><span class="badge badge--new">New</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
    </div>
</section>
