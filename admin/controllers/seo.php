<?php
// SEO tools for moving from the old website: a launch checklist, a check of every old
// address, the log of addresses that were not found (with one-click redirects) and the redirects list.
require_admin();

/** True when an address inside the site shows a real page (used to check redirects). */
$pageExists = static function (string $path): bool {
    $path = trim(parse_url($path, PHP_URL_PATH) ?: $path, '/');
    $base = trim((string) parse_url(url(), PHP_URL_PATH), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = trim(substr($path, strlen($base)), '/');
    }
    if (in_array($path, ['', 'shop', 'custom-cakes', 'about', 'contact', 'faq', 'cart', 'login', 'register'], true)) {
        return true;
    }
    if (preg_match('#^shop/([a-z0-9-]+)$#', $path, $m)) {
        return (bool) get_category_by_slug($m[1]);
    }
    if (preg_match('#^cakes/([a-z0-9-]+)$#', $path, $m)) {
        return (bool) get_product_by_slug($m[1]);
    }
    return preg_match('/^[a-z0-9-]+$/', $path) && get_page($path) !== null;
};

if (is_post()) {
    $action = input('action');
    if ($action === 'redirect') {
        $from = '/' . trim((string) parse_url(input('from'), PHP_URL_PATH), '/');
        $to = trim(input('to'));
        $to = preg_match('#^https?://#', $to) ? $to : '/' . trim((string) parse_url($to, PHP_URL_PATH), '/');
        if ($from === '/' || $to === '' || $to === '/' . trim($from, '/')) {
            flash('error', 'Choose the page to send this address to.');
        } else {
            add_seo_redirect($from, $to);
            db_run('DELETE FROM not_found_log WHERE path = ?', [$from]);
            flash('success', $from . ' now redirects to ' . $to . '.');
        }
    } elseif ($action === 'suggest_keywords') {
        $count = db_run("UPDATE products SET focus_keyword = LOWER(CONCAT(name, ' canberra')) WHERE (focus_keyword IS NULL OR focus_keyword = '') AND is_active = 1")->rowCount();
        flash('success', $count . ' cakes got a suggested focus keyword. Open each cake to check it and follow its checklist.');
    } elseif ($action === 'ignore') {
        db_run('DELETE FROM not_found_log WHERE path = ?', [input('path')]);
        flash('success', 'Removed from the list.');
    } elseif ($action === 'remove_redirect') {
        $lines = array_filter(preg_split('/\R/', setting('seo_redirects')), static fn ($l) => trim($l) !== '' && trim($l) !== trim(input('line')));
        setting_save('seo_redirects', implode("\n", $lines));
        flash('success', 'Redirect removed.');
    }
    redirect(admin_url('seo'));
}

$appUrl = (string) config('app_url');
$weakProducts = 0;
$noPhoto = 0;
foreach (db_all('SELECT p.*, (SELECT COUNT(*) FROM product_images i WHERE i.product_id = p.id) AS photo_count FROM products p WHERE p.is_active = 1') as $p) {
    [, , $level] = seo_score(product_seo_checks($p, (int) $p['photo_count']));
    $weakProducts += $level === 'poor' ? 1 : 0;
    $noPhoto += $p['image'] ? 0 : 1;
}
$landingCount = (int) db_value("SELECT COUNT(*) FROM pages WHERE type = 'landing' AND is_active = 1");
$checks = [
    ['Website address uses https', str_starts_with($appUrl, 'https://'), 'Set app_url in config/config.php to https://www.continentalcakeshop.com.au once SSL is active, the same address as the old site.'],
    ['Google Search Console is connected', setting('seo_google_verification') !== '', 'Add the verification code in Settings > Social & SEO, then submit ' . url('sitemap.xml') . ' in Search Console.'],
    ['Google Analytics is connected', setting('seo_ga4_id') !== '', 'Add your G- measurement ID in Settings > Social & SEO.'],
    ['Homepage title has "Cake Shop Canberra"', (bool) preg_match('/cake shop canberra/i', setting('seo_home_title')), 'The old site ranks for "Cake Shop Canberra". Start the homepage title with it in Settings > Social & SEO.'],
    ['Google reviews link is set', filter_var(setting('google_reviews_url'), FILTER_VALIDATE_URL) !== false, 'Paste your Google reviews link in Homepage > Google reviews. It is also used for review request emails.'],
    ['Shop location on the map', setting('store_latitude') !== '' && setting('store_longitude') !== '', 'Add the latitude and longitude in Settings > Shop details so Google can place the shop.'],
    ['Landing pages published', $landingCount >= 3, 'Publish the birthday, wedding and baby shower pages in Pages. The old gallery addresses point to them.'],
    ['Every cake has a photo', $noPhoto === 0, $noPhoto . ' visible cakes have no photo. Cakes with photos get more clicks and show in Google Images.'],
    ['No cakes with a weak SEO score', $weakProducts === 0, $weakProducts . ' visible cakes score below half on the SEO checklist. Open them from Products and use "Fill in for me".'],
];

$oldPages = [];
foreach (array_keys(LEGACY_REDIRECTS + admin_redirects()) as $old) {
    $target = legacy_redirect_target($old);
    $oldPages[] = ['from' => '/' . $old, 'to' => $target, 'ok' => $target !== null && (preg_match('#^https?://#', $target) && !str_starts_with($target, url()) ? true : $pageExists($target))];
}
$missing = db_all('SELECT * FROM not_found_log ORDER BY hits DESC, last_seen DESC LIMIT 50');
$redirects = array_values(array_filter(array_map('trim', preg_split('/\R/', setting('seo_redirects')))));
$targets = array_merge(
    [['', 'Homepage'], ['shop', 'Shop'], ['custom-cakes', 'Custom cakes'], ['about', 'About'], ['contact', 'Contact'], ['faq', 'FAQ']],
    array_map(static fn ($c) => ['shop/' . $c['slug'], 'Category: ' . $c['name']], get_categories()),
    array_map(static fn ($p) => [$p['slug'], 'Page: ' . $p['title']], get_landing_pages())
);
admin_view('seo', compact('checks', 'oldPages', 'missing', 'redirects', 'targets') + ['title' => 'SEO']);
