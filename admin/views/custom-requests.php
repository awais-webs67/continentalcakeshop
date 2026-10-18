<?php /** @var array $result @var array $counts */ ?>
<div class="page-head">
    <div><h1>Custom cake requests</h1><p>Enquiries sent from the custom cakes page. Reply with a quote from each request.</p></div>
</div>
<section class="card">
    <nav class="tabs">
        <a href="<?= e(admin_url('custom-requests')) ?>" class="<?= $status === '' ? 'is-active' : '' ?>">All<span><?= array_sum($counts) ?></span></a>
        <?php foreach (CUSTOM_STATUSES as $key => $label): ?>
            <a href="<?= e(admin_url('custom-requests', ['status' => $key])) ?>" class="<?= $status === $key ? 'is-active' : '' ?>"><?= e($label) ?><span><?= (int) ($counts[$key] ?? 0) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e(admin_url('custom-requests')) ?>">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <input class="input grow" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, phone or occasion">
        <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <?php if (!$result['rows']): ?>
        <div class="empty"><?= icon('gift', '', 32) ?><p>No custom cake requests here yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Customer</th><th>Occasion</th><th>Needed by</th><th>Budget</th><th>Status</th><th class="num">Received</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <?php $days = (int) floor((strtotime($r['event_date']) - strtotime('today')) / 86400); ?>
                <tr>
                    <td><a class="row-link" href="<?= e(admin_url('custom-requests/view', ['id' => $r['id']])) ?>"><?= e($r['name']) ?></a><div class="muted"><?= e($r['phone']) ?></div></td>
                    <td><?= e($r['occasion']) ?><div class="muted"><?= e((string) $r['servings']) ?></div></td>
                    <td><?= e(date('D j M Y', strtotime($r['event_date']))) ?><div class="muted"><?= $days < 0 ? 'Date has passed' : ($days === 0 ? 'Today' : 'In ' . $days . ' day' . ($days === 1 ? '' : 's')) ?></div></td>
                    <td><?= e($r['budget'] ?: '-') ?><?php if ($r['quoted_price']): ?><div class="muted">Quoted <?= e(money($r['quoted_price'])) ?></div><?php endif; ?></td>
                    <td><?= status_badge($r['status'], CUSTOM_STATUSES[$r['status']]) ?></td>
                    <td class="num muted"><?= e(time_ago($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/pager.php'; ?>
</section>
