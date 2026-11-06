<?php /** @var array $stats */ ?>
<div class="page-head">
    <div><h1>Branches</h1><p>Your shops. Each one gets its own orders, custom cake requests and messages, by email and in its own login. Cakes, prices, hours, coupons and payments are the same for every shop.</p></div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('admins')) ?>#add-staff"><?= icon('user', '', 16) ?> Add a shop login</a>
        <a class="btn btn--primary" href="<?= e(admin_url('branches', ['new' => 1])) ?>"><?= icon('plus', '', 16) ?> Add a branch</a>
    </div>
</div>

<div class="branch-grid">
    <?php foreach (branches() as $b): $s = $stats[(int) $b['id']]; $email = branch_notify_email((int) $b['id']); ?>
        <section class="card branch-card<?= $b['is_active'] ? '' : ' is-off' ?>">
            <header class="branch-card__head">
                <div>
                    <h2><a href="<?= e(admin_url('branches', ['id' => $b['id']])) ?>"><?= e($b['name']) ?></a></h2>
                    <p><?= e(branch_address_line($b)) ?></p>
                </div>
                <?= $b['is_active'] ? status_badge('active', $b['is_main'] ? 'Main shop' : 'Live') : status_badge('inactive', 'Hidden') ?>
            </header>

            <div class="branch-card__stats">
                <div><span>Due today</span><strong><?= (int) $s['due_today'] ?></strong></div>
                <div><span>New orders</span><strong><?= (int) $s['new_orders'] ?></strong></div>
                <div><span>Requests</span><strong><?= (int) $s['requests'] ?></strong></div>
                <div><span>This month</span><strong><?= e(money((float) $s['month_sales'])) ?></strong></div>
            </div>

            <dl class="branch-card__facts">
                <div><dt>Phone</dt><dd><?= e(branch_phone($b)) ?><?= trim($b['phone']) === '' && !$b['is_main'] ? ' <span class="muted">(main shop number)</span>' : '' ?></dd></div>
                <div><dt>Email</dt><dd><?= branch_uses_own((int) $b['id'], 'email') ? e(branch_account_email((int) $b['id']) ?: 'Own account, not finished') : 'Shared account' ?><?= inbox_enabled($b) ? ' &middot; inbox in Messages' : '' ?></dd></div>
                <div><dt>Orders go to</dt><dd><?= $email !== '' ? e($email) : '<span class="muted">No email yet</span>' ?></dd></div>
                <div><dt>Shop logins</dt><dd><?php if (!$s['logins']): ?><span class="muted">None yet</span><?php else: ?><?= e(implode(', ', array_map(static fn ($l) => $l['name'] . ($l['permissions'] === null ? ' (manager)' : ''), $s['logins']))) ?><?php endif; ?></dd></div>
            </dl>
            <?php if (!$b['is_active'] && trim($b['street']) === ''): ?>
                <p class="branch-card__todo"><?= icon('info', '', 16) ?> Not finished: open Manage to add its address, phone, email and payments, then show it to customers.</p>
            <?php endif; ?>

            <footer class="branch-card__actions">
                <a class="btn btn--primary btn--sm" href="<?= e(admin_url('branches', ['id' => $b['id']])) ?>"><?= icon('pen', '', 15) ?> Manage</a>
                <form method="post" action="<?= e(admin_url('branch-view')) ?>"><?= csrf_field() ?><input type="hidden" name="branch" value="<?= (int) $b['id'] ?>"><input type="hidden" name="_back" value="<?= e(admin_url('orders')) ?>"><button class="btn btn--secondary btn--sm" type="submit"><?= icon('bag', '', 15) ?> Orders</button></form>
                <details class="row-menu">
                    <summary class="btn btn--ghost btn--sm" aria-label="More for <?= e($b['name']) ?>"><?= icon('menu', '', 16) ?> More</summary>
                    <div class="row-menu__panel">
                        <?php if ($b['is_active']): ?><a class="row-menu__item" href="<?= e(branch_url($b)) ?>" target="_blank" rel="noopener">View its page</a><?php endif; ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="test_email"><button class="row-menu__item" type="submit">Send a test email</button></form>
                        <?php if (!$b['is_main']): ?>
                            <form method="post" <?= $b['is_active'] ? 'data-confirm="Hide ' . e($b['name']) . ' from customers? Its orders stay in the panel."' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="row-menu__item" type="submit"><?= $b['is_active'] ? 'Hide from customers' : 'Show to customers' ?></button></form>
                        <?php endif; ?>
                    </div>
                </details>
            </footer>
        </section>
    <?php endforeach; ?>
</div>

<section class="card">
    <div class="card__head"><h2>How the shop logins work</h2></div>
    <div class="card__body">
        <ul class="help-list">
            <li><strong>Owner</strong> (you): every shop. Use the shop menu at the top to see one shop or all of them.</li>
            <li><strong>Branch manager</strong>: the same admin panel, but only their own shop's orders, pickups, requests, messages, sales and cakes. Shop settings (email, payments, page) stay with you.</li>
            <li><strong>Staff</strong>: only the pages you tick, for one shop or every shop.</li>
        </ul>
    </div>
</section>
