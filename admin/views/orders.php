<?php /** @var array $result @var array $views @var string $current @var array $viewCounts @var bool $filtersOn */
$viewIcons = ['action' => 'bell', 'today' => 'calendar', 'tomorrow' => 'clock', 'upcoming' => 'calendar', 'unpaid' => 'card', 'done' => 'check', 'cancelled' => 'close', 'all' => 'bag'];
?>
<div class="page-head">
    <div><h1>Orders</h1><p>Every order from the website, by phone and in the shop. Tap an order to open it.</p></div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 18) ?> Pickups &amp; deliveries</a>
        <a class="btn btn--primary" href="<?= e(admin_url('orders/new')) ?>"><?= icon('plus', '', 18) ?> New order</a>
    </div>
</div>

<nav class="view-cards" data-live="views" aria-label="Order views">
    <?php foreach ($views as $key => [$label, $help]): ?>
        <a href="<?= e(admin_url('orders', $key === 'all' ? [] : ['view' => $key])) ?>" class="view-card view-card--<?= e($key) ?><?= $current === $key ? ' is-active' : '' ?><?= $key === 'action' && $viewCounts[$key] ? ' has-alert' : '' ?>" title="<?= e($help) ?>"<?= $current === $key ? ' aria-current="page"' : '' ?>>
            <span class="view-card__icon"><?= icon($viewIcons[$key], '', 17) ?></span>
            <span class="view-card__label"><?= e($label) ?></span>
            <strong><?= $viewCounts[$key] ?></strong>
        </a>
    <?php endforeach; ?>
</nav>

<section class="card">
    <form class="order-filters" method="get" action="<?= e(admin_url('orders')) ?>">
        <?php if ($current !== 'all'): ?><input type="hidden" name="view" value="<?= e($current) ?>"><?php endif; ?>
        <div class="order-filters__search">
            <?= icon('search', '', 18) ?>
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search order number, name, email or phone" aria-label="Search orders">
            <button class="btn btn--primary btn--sm" type="submit">Search</button>
        </div>
        <details class="order-filters__more"<?= $filtersOn ? ' open' : '' ?>>
            <summary class="btn btn--ghost btn--sm"><?= icon('menu', '', 16) ?> Filters<?= $filtersOn ? ' <em>On</em>' : '' ?></summary>
            <div class="order-filters__grid">
                <label class="field"><span>Status</span>
                    <select class="input input--sm" name="status" data-auto-submit>
                        <option value="">Any status</option>
                        <?php foreach (ORDER_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($status === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="field"><span>Payment</span>
                    <select class="input input--sm" name="payment" data-auto-submit>
                        <option value="">Any payment</option>
                        <?php foreach (PAYMENT_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($payment === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="field"><span>Pickup or delivery</span>
                    <select class="input input--sm" name="fulfilment" data-auto-submit>
                        <option value="">Both</option>
                        <option value="pickup" <?= selected($fulfilment === 'pickup') ?>>Pickup</option>
                        <option value="delivery" <?= selected($fulfilment === 'delivery') ?>>Delivery</option>
                    </select>
                </label>
                <label class="field"><span>Came from</span>
                    <select class="input input--sm" name="channel" data-auto-submit>
                        <option value="">Anywhere</option>
                        <?php foreach (ORDER_CHANNELS as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($channel === $key) ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="field"><span>Date from</span><input class="input input--sm" type="date" name="from" value="<?= e($from) ?>" data-auto-submit></label>
                <label class="field"><span>Date to</span><input class="input input--sm" type="date" name="to" value="<?= e($to) ?>" data-auto-submit></label>
                <label class="field"><span>Sort by</span>
                    <select class="input input--sm" name="sort" data-auto-submit>
                        <option value="">Best for this view</option>
                        <option value="date" <?= selected($sort === 'date') ?>>Pickup date, soonest first</option>
                        <option value="placed" <?= selected($sort === 'placed') ?>>Newest placed first</option>
                    </select>
                </label>
                <?php if ($filtersOn || $q !== ''): ?><a class="btn btn--ghost btn--sm order-filters__clear" href="<?= e(admin_url('orders', $current !== 'all' ? ['view' => $current] : [])) ?>">Clear filters</a><?php endif; ?>
            </div>
        </details>
    </form>

    <div data-live="list">
        <div class="list-caption">
            <strong><?= e($views[$current][0]) ?></strong>
            <span><?= e($views[$current][1]) ?> &middot; <?= (int) $result['total'] ?> order<?= $result['total'] === 1 ? '' : 's' ?></span>
        </div>
        <?php if (!$result['rows']): ?>
            <div class="empty"><?= icon('bag', '', 32) ?><p><?= $current === 'action' ? 'Nothing needs doing right now.' : 'No orders match these filters.' ?></p></div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($result['rows'] as $o): ?>
                    <?php render_admin_partial('order-row', ['o' => $o, 'show' => 'date']); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php require __DIR__ . '/partials/pager.php'; ?>
    </div>
</section>
