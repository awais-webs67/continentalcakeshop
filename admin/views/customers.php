<?php /** @var array $result @var int $guestOrders */ ?>
<div class="page-head">
    <div><h1>Customers</h1><p>People who created an account on the website. <?= $guestOrders ?> orders were placed as a guest.</p></div>
</div>
<section class="card">
    <form class="toolbar" method="get" action="<?= e(admin_url('customers')) ?>">
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone">
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('user', '', 32) ?><p>No customer accounts found.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Paid total</th><th class="num">Joined</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <tr>
                    <td><a class="row-link" href="<?= e(admin_url('customers/view', ['id' => $u['id']])) ?>"><?= e($u['name']) ?></a><div class="muted"><?= e($u['email']) ?></div></td>
                    <td><?= e($u['phone'] ?: '-') ?></td>
                    <td><?= (int) $u['order_count'] ?><?php if ($u['last_order_at']): ?><div class="muted">Last <?= e(time_ago($u['last_order_at'])) ?></div><?php endif; ?></td>
                    <td><?= e(money($u['spent'])) ?></td>
                    <td class="num muted"><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
