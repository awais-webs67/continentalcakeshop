<?php /** @var array $result @var string $filter @var int $unread */ ?>
<div class="page-head">
    <div><h1>Messages</h1><p>Messages sent from the contact page.</p></div>
    <?php if ($unread): ?>
    <div class="page-head__actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn--secondary" type="submit"><?= icon('check', '', 18) ?> Mark all as read</button></form>
    </div>
    <?php endif; ?>
</div>
<section class="card">
    <nav class="tabs">
        <a href="<?= e(admin_url('messages')) ?>" class="<?= $filter === '' ? 'is-active' : '' ?>">All</a>
        <a href="<?= e(admin_url('messages', ['filter' => 'unread'])) ?>" class="<?= $filter === 'unread' ? 'is-active' : '' ?>">Unread<?php if ($unread): ?><span><?= (int) $unread ?></span><?php endif; ?></a>
        <a href="<?= e(admin_url('messages', ['filter' => 'replied'])) ?>" class="<?= $filter === 'replied' ? 'is-active' : '' ?>">Replied</a>
    </nav>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('mail', '', 32) ?><p>No messages here.</p></div>
    <?php else: ?>
        <div class="message-list">
        <?php foreach ($result['rows'] as $m): ?>
            <?php $back = admin_url('messages') . page_link($result['page']); ?>
            <article class="message<?= $m['is_read'] ? '' : ' message--unread' ?>">
                <header class="message__head">
                    <span class="message__avatar" style="background:<?= e(avatar_colour($m['name'])) ?>"><?= e(mb_strtoupper(mb_substr($m['name'], 0, 1))) ?></span>
                    <div>
                        <strong><?= e($m['name']) ?></strong>
                        <div class="muted"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' &middot; <a href="tel:' . e(preg_replace('/[^\d+]/', '', $m['phone'])) . '">' . e($m['phone']) . '</a>' : '' ?></div>
                    </div>
                    <div class="message__meta">
                        <?= $m['replied_at'] ? status_badge('read', 'Replied') : ($m['is_read'] ? '' : status_badge('unread', 'New')) ?>
                        <span class="muted"><?= e(time_ago($m['created_at'])) ?></span>
                    </div>
                </header>
                <p class="message__body"><?= nl2br(e($m['message'])) ?></p>
                <footer class="message__actions">
                    <a class="btn btn--primary btn--sm" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: your message to ' . setting('store_short_name')) ?>"><?= icon('mail', '', 16) ?> Reply by email</a>
                    <?php foreach ([['replied', 'Mark as replied'], ['read', $m['is_read'] ? 'Mark unread' : 'Mark read']] as [$action, $label]): ?>
                        <?php if ($action === 'replied' && $m['replied_at']) continue; ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="<?= $action ?>"><button class="btn btn--ghost btn--sm" type="submit"><?= e($label) ?></button></form>
                    <?php endforeach; ?>
                    <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="_back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                </footer>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
