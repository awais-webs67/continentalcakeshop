<?php /** @var array $result @var string $q @var string $type @var array $counts */
$customerLink = static fn (array $c): string => $c['kind'] === 'account'
    ? admin_url('customers/view', ['id' => $c['ckey']])
    : admin_url('customers/view', ['guest' => $c['ckey']]);
?>
<div class="page-head">
    <div><h1>Customers</h1><p>Everyone who has ordered: customers with an account and guests who checked out without one (including phone and shop orders).</p></div>
</div>
<section class="card">
    <nav class="tabs">
        <?php foreach (['all' => 'All customers', 'accounts' => 'With an account', 'guests' => 'Guests (no account)'] as $key => $label): ?>
            <a href="<?= e(admin_url('customers', array_filter(['type' => $key === 'all' ? null : $key, 'q' => $q ?: null]))) ?>" class="<?= $type === $key ? 'is-active' : '' ?>"><?= e($label) ?><span><?= (int) $counts[$key] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e(admin_url('customers')) ?>">
        <?php if ($type !== 'all'): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone">
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('user', '', 32) ?><p><?= $q !== '' ? 'No customers match this search.' : 'No customers here yet.' ?></p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Customer</th><th>Type</th><th>Phone</th><th>Orders</th><th>Paid total</th><th class="num">Last order</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $c): ?>
                <tr>
                    <td><a class="row-link" href="<?= e($customerLink($c)) ?>"><?= e($c['name'] ?: 'No name') ?></a><div class="muted"><?= e($c['email'] ?: 'No email') ?></div></td>
                    <td><?= $c['kind'] === 'account' ? status_badge('active', 'Account') : status_badge('pending', 'Guest') ?></td>
                    <td><?= e($c['phone'] ?: '-') ?></td>
                    <td><?= (int) $c['order_count'] ?></td>
                    <td><?= e(money($c['spent'])) ?></td>
                    <td class="num muted"><?= $c['last_order_at'] ? e(time_ago($c['last_order_at'])) : 'No orders yet' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
