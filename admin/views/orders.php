<?php /** @var array $result @var array $views @var string $current @var array $viewCounts @var bool $filtersOn */
$link = static fn (string $key): string => admin_url('orders', $key === 'ongoing' ? [] : ['view' => $key]);
?>
<div class="page-head page-head--compact">
    <h1>Orders</h1>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= e(admin_url('pickups')) ?>"><?= icon('calendar', '', 17) ?> Pickups &amp; deliveries</a>
        <a class="btn btn--primary" href="<?= e(admin_url('orders/new')) ?>"><?= icon('plus', '', 17) ?> New order</a>
    </div>
</div>

<section class="card">
    <div data-live="tabs">
        <nav class="tabs order-tabs" aria-label="Orders">
            <?php $tabs = $viewCounts['action'] || $current === 'action' ? array_merge(['action'], ORDER_TABS) : ORDER_TABS; ?>
            <?php foreach ($tabs as $key): ?>
                <a href="<?= e($link($key)) ?>" class="<?= $current === $key ? 'is-active' : '' ?><?= $key === 'action' ? ' order-tabs__action' : '' ?>"<?= $current === $key ? ' aria-current="page"' : '' ?> title="<?= e($views[$key][1]) ?>"><?= e($views[$key][0]) ?> <span><?= $viewCounts[$key] ?></span></a>
            <?php endforeach; ?>
            <?php if (!in_array($current, $tabs, true)): ?><a href="<?= e($link($current)) ?>" class="is-active" aria-current="page"><?= e($views[$current][0]) ?> <span><?= $viewCounts[$current] ?></span></a><?php endif; ?>
        </nav>
    </div>
    <form class="order-filters" method="get" action="<?= e(admin_url('orders')) ?>">
        <?php if ($current !== 'ongoing'): ?><input type="hidden" name="view" value="<?= e($current) ?>"><?php endif; ?>
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
                        <option value="">Newest placed first</option>
                        <option value="date" <?= selected($sort === 'date') ?>>Pickup date, soonest first</option>
                        
                    </select>
                </label>
                <?php if ($filtersOn || $q !== ''): ?><a class="btn btn--ghost btn--sm order-filters__clear" href="<?= e($link($current)) ?>">Clear filters</a><?php endif; ?>
            </div>
        </details>
    </form>

    <div data-live="list">
        <?php if (!$result['rows']): ?>
            <div class="empty"><?= icon('bag', '', 32) ?><p><?= match (true) {
                $filtersOn || $q !== '' => 'No orders match these filters.',
                $current === 'action'   => 'Nothing needs doing right now.',
                $current === 'ongoing'  => 'No open orders. New orders show here.',
                $current === 'today'    => 'No pickups or deliveries today.',
                default                 => 'No orders here yet.',
            } ?></p></div>
        <?php else: ?>
            <div class="otable">
                <div class="otr otr--head" aria-hidden="true"><span>Order</span><span>Customer</span><span>Pickup</span><span>Status</span><span>Payment</span><span class="otr__total">Total</span><span></span></div>
                <?php foreach ($result['rows'] as $o): ?>
                    <?php render_admin_partial('order-trow', ['o' => $o]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php require __DIR__ . '/partials/pager.php'; ?>
    </div>
</section>
