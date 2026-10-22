<?php
// Homepage categories, in the style chosen in Admin > Homepage:
//   tiles   - photo grid; the first category is large and the rest fill the rows evenly,
//             whatever the number of categories
//   circles - round photos in the theme colour that glide past like a news ticker,
//             with "View all categories" at the end
$photos = get_gallery(12);
$tiles = array_map(static fn ($cat) => [
    'name'  => $cat['name'],
    'meta'  => ($cat['price_from'] !== null ? 'From ' . money($cat['price_from']) . ' · ' : '') . (int) $cat['product_count'] . ' cakes',
    'link'  => category_url($cat),
    'image' => $cat['image'] ?: $cat['cover_image'],
    'alt'   => ($cat['image'] && !empty($cat['image_alt'])) ? $cat['image_alt'] : $cat['name'] . ' from ' . setting('store_short_name'),
], get_categories());
$tiles[] = [
    'name'  => 'Custom Cakes',
    'meta'  => 'Birthdays, weddings and baby showers',
    'link'  => url('custom-cakes'),
    'image' => ($photos[9] ?? $photos[0] ?? null)['image'] ?? null,
    'alt'   => 'Custom cake by ' . setting('store_short_name'),
];
$style = setting('home_categories_style', 'tiles') === 'circles' ? 'circles' : 'tiles';

/**
 * Column and row spans so the grid always ends on a full row. Large screens use 4 columns with
 * the first tile 2 x 2; phones and tablets use 2 columns with the first tile full width.
 */
$spans = static function (int $count): array {
    $out = array_fill(0, $count, ['lg' => 1, 'rows' => 1, 'sm' => 1]);
    $out[0] = ['lg' => $count === 1 ? 4 : 2, 'rows' => $count === 1 ? 1 : 2, 'sm' => 2];
    $rest = $count - 1;
    if ($rest === 1) {
        $out[1] = ['lg' => 2, 'rows' => 2, 'sm' => 2];
    } elseif ($rest === 2) {
        $out[1]['lg'] = $out[2]['lg'] = 2;
    } elseif ($rest === 3) {
        $out[3]['lg'] = 2;
    } elseif ($rest > 4) {
        $left = ($rest - 4) % 4;
        $last = $count - $left;
        if ($left === 1) {
            $out[$last]['lg'] = 4;
        } elseif ($left === 2) {
            $out[$last]['lg'] = $out[$last + 1]['lg'] = 2;
        } elseif ($left === 3) {
            $out[$last]['lg'] = 2;
        }
    }
    if ($rest > 0 && $rest % 2 === 1) {
        $out[$count - 1]['sm'] = 2; // odd number after the first: the last one goes full width
    }
    return $out;
};
$layout = $spans(count($tiles));
?>
<section class="section<?= $style === 'circles' ? ' section--cat-circles' : '' ?>">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title"><?= e(setting('home_categories_title')) ?></h2>
                <?php if (setting('home_categories_text') !== ''): ?><p class="section-sub"><?= e(setting('home_categories_text')) ?></p><?php endif; ?>
            </div>
            <?php if ($style === 'tiles'): ?><a class="link-arrow" href="<?= e(url('shop')) ?>">View all cakes <?= icon('arrow', '', 18) ?></a><?php endif; ?>
        </div>

        <?php if ($style === 'circles'): ?>
            <div class="cat-ticker" data-ticker style="--ticker-speed:<?= 34 ?>">
                <div class="cat-ticker__viewport" data-ticker-viewport>
                    <div class="cat-ticker__track" data-ticker-track>
                        <?php foreach ([false, true] as $copy): // the list twice, so the loop has no gap ?>
                            <ul class="cat-ticker__list"<?= $copy ? ' aria-hidden="true"' : '' ?>>
                                <?php foreach ($tiles as $tile): ?>
                                    <li>
                                        <a class="cat-circle" href="<?= e($tile['link']) ?>"<?= $copy ? ' tabindex="-1"' : '' ?>>
                                            <span class="cat-circle__ring"><img src="<?= e(product_image_url($tile['image'])) ?>" alt="<?= $copy ? '' : e($tile['alt']) ?>" width="240" height="240" loading="lazy" decoding="async"></span>
                                            <span class="cat-circle__name"><?= e($tile['name']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a class="cat-ticker__all" href="<?= e(url('shop')) ?>">
                    <span class="cat-ticker__all-ring"><?= icon('arrow', '', 26) ?></span>
                    <span class="cat-circle__name">View all<span class="cat-ticker__all-more"> categories</span></span>
                </a>
            </div>
        <?php else: ?>
            <div class="tile-grid">
                <?php foreach ($tiles as $i => $tile): ?>
                    <a class="tile<?= $i === 0 ? ' tile--feature' : ($layout[$i]['sm'] === 2 ? ' tile--wide-sm' : '') ?>" href="<?= e($tile['link']) ?>" style="--span-lg:<?= $layout[$i]['lg'] ?>;--rows-lg:<?= $layout[$i]['rows'] ?>;--span-sm:<?= $layout[$i]['sm'] ?>">
                        <img src="<?= e(product_image_url($tile['image'])) ?>" alt="<?= e($tile['alt']) ?>" width="1000" height="800" loading="lazy" decoding="async">
                        <span class="tile__body">
                            <span class="tile__name"><?= e($tile['name']) ?></span>
                            <span class="tile__meta"><?= e($tile['meta']) ?></span>
                        </span>
                        <span class="tile__arrow" aria-hidden="true"><?= icon('arrow', '', 20) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
