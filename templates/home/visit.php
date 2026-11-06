<?php
$todayKey = day_key(new DateTime());
$mapQuery = rawurlencode(setting('store_name') . ', ' . store_address_line());
?>
<?php if (multi_branch()): ?>
<?php // "Visit us in Greenway" names one shop: with two shops the heading speaks of both.
$visitTitle = setting('home_visit_title');
if (stripos($visitTitle, (string) main_branch()['suburb']) !== false) {
    $visitTitle = 'Visit our shops';
} ?>
<section class="section visit">
    <div class="container visit__grid">
        <div class="visit__info">
            <h2 class="section-title"><?= e($visitTitle) ?></h2>
            <div class="visit__shops">
                <?php foreach (active_branches() as $shop): ?>
                    <div class="shop-card">
                        <h3 class="shop-card__name"><?= icon('store', '', 20) ?> <a href="<?= e(branch_url($shop)) ?>"><?= e($shop['name']) ?> shop</a></h3>
                        <p class="shop-card__addr"><?= e(branch_address_line($shop)) ?></p>
                        <div class="shop-card__actions">
                            <a class="btn btn--primary btn--sm" href="<?= e(branch_maps_url($shop)) ?>" target="_blank" rel="noopener"><?= icon('pin', '', 16) ?> Directions</a>
                            <a class="btn btn--outline btn--sm" href="tel:<?= e(phone_e164(branch_phone($shop))) ?>"><?= icon('phone', '', 16) ?> <?= e(branch_phone($shop)) ?></a>
                            <?php if (($shopEmail = branch_email_shown($shop)) !== ''): ?>
                                <a class="btn btn--outline btn--sm btn--icon" href="mailto:<?= e($shopEmail) ?>" aria-label="Email the <?= e($shop['name']) ?> shop: <?= e($shopEmail) ?>" title="<?= e($shopEmail) ?>"><?= icon('mail', '', 18) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="visit__hours info-card">
            <h3>Opening hours <small>both shops</small></h3>
            <table class="hours-table">
                <caption class="sr-only">Opening hours</caption>
                <tbody>
                    <?php foreach (opening_hours() as $day => $slot): ?>
                        <tr <?= $day === $todayKey ? 'class="is-today"' : '' ?>>
                            <th scope="row"><?= e(DAY_NAMES[$day]) ?><?= $day === $todayKey ? ' <span>Today</span>' : '' ?></th>
                            <td><?= $slot ? e(format_time($slot[0]) . ' to ' . format_time($slot[1])) : 'Closed' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php else: ?>
<section class="section visit">
    <div class="container visit__grid">
        <div class="visit__info">
            <h2 class="section-title"><?= e(setting('home_visit_title')) ?></h2>
            <p class="visit__address"><?= icon('pin', '', 20) ?> <?= e(store_address_line()) ?></p>

            <table class="hours-table">
                <caption class="sr-only">Opening hours</caption>
                <tbody>
                    <?php foreach (opening_hours() as $day => $slot): ?>
                        <tr <?= $day === $todayKey ? 'class="is-today"' : '' ?>>
                            <th scope="row"><?= e(DAY_NAMES[$day]) ?><?= $day === $todayKey ? ' <span>Today</span>' : '' ?></th>
                            <td><?= $slot ? e(format_time($slot[0]) . ' to ' . format_time($slot[1])) : 'Closed' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="visit__actions">
                <a class="btn btn--primary" href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= icon('pin', '', 18) ?> Get Directions</a>
                <a class="btn btn--outline" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> Call Us</a>
            </div>
        </div>
        <div class="visit__map">
            <iframe title="Map showing <?= e(setting('store_name')) ?>" src="https://www.google.com/maps?q=<?= e($mapQuery) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <a class="visit__map-bar" href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener">
                <span class="visit__map-pin" aria-hidden="true"><?= icon('pin', '', 20) ?></span>
                <span class="visit__map-text"><strong><?= e(setting('store_short_name', setting('store_name'))) ?></strong><span><?= e(store_address_line()) ?></span></span>
                <span class="visit__map-go"><?= icon('arrow', '', 18) ?><span class="sr-only">Open in Google Maps</span></span>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>
