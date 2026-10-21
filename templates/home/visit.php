<?php
$todayKey = day_key(new DateTime());
$mapQuery = rawurlencode(setting('store_name') . ', ' . store_address_line());
?>
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
