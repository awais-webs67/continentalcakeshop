<?php
declare(strict_types=1);

// Telling search engines about changes. The sitemap is built from the database on every visit,
// so a new, changed, hidden or deleted cake is right in it at once. On top of that the cron job
// (and the "Submit everything now" button in Admin > AI SEO agent) compares the sitemap with the
// last one it sent and:
//  - sends the changed addresses (also removed ones, so they drop out) to IndexNow, which passes
//    them to Bing, Yandex, Naver and Seznam. ChatGPT search and Copilot use Bing's index.
//  - submits the sitemap to Google Search Console when it is connected with a service account
//    (Google has no instant ping any more; it reads the submitted sitemap again by itself).

/** Every address in the sitemap: [address, last changed, change frequency, priority, photos]. */
function sitemap_entries(): array
{
    $latestProduct = (string) db_value('SELECT MAX(updated_at) FROM products WHERE is_active = 1');
    $photo = static fn (?string $image, string $title): ?array => $image ? [product_image_url($image), $title] : null;
    $galleryPhotos = array_map(static fn ($g) => [product_image_url($g['image']), $g['caption']], get_gallery());
    $latestPhoto = (string) db_value('SELECT MAX(created_at) FROM gallery_images WHERE is_active = 1');
    $latestBranch = (string) db_value('SELECT MAX(updated_at) FROM branches WHERE is_active = 1');
    $latestStoreSetting = (string) db_value("SELECT MAX(updated_at) FROM settings WHERE setting_key IN ('store_street', 'store_phone', 'store_founded', 'shop_photo', 'custom_lead_days')");
    $latestPage = (string) db_value('SELECT MAX(updated_at) FROM pages WHERE is_active = 1');

    $entries = [
        [url(), $latestProduct, 'daily', '1.0', array_filter([$photo(setting('hero_image'), setting('hero_title'))])],
        [url('shop'), $latestProduct, 'weekly', '0.9', []],
        [url('custom-cakes'), $latestPhoto ?: $latestStoreSetting ?: null, 'weekly', '0.8', setting('custom_show_gallery', '1') === '1' ? array_slice($galleryPhotos, 0, (int) setting('custom_gallery_count', '8')) : []],
        [url('custom-cake-gallery'), $latestPhoto ?: null, 'weekly', '0.8', $galleryPhotos],
        [url('about'), $latestStoreSetting ?: null, 'monthly', '0.5', [[shop_photo()['absolute'], setting('store_name') . ' shop front']]],
        [url('contact'), $latestBranch ?: $latestStoreSetting ?: null, 'monthly', '0.6', []],
        [url('faq'), $latestPage ?: $latestStoreSetting ?: null, 'monthly', '0.6', []],
    ];
    // One page per open shop, with its photo, when there is more than one shop.
    if (multi_branch()) {
        foreach (active_branches() as $shop) {
            $shopPhoto = !empty($shop['photo']) ? product_image_url($shop['photo']) : ((int) $shop['is_main'] ? shop_photo()['absolute'] : null);
            $entries[] = [branch_url($shop), $shop['updated_at'], 'monthly', '0.8', $shopPhoto ? [[$shopPhoto, setting('store_name') . ' ' . $shop['suburb']]] : []];
        }
    }
    foreach (db_all('SELECT slug, title, type, occasion, updated_at FROM pages WHERE is_active = 1') as $content) {
        $entries[] = $content['type'] === 'landing'
            ? [url($content['slug']), $content['updated_at'], 'weekly', '0.8', array_map(static fn ($g) => [product_image_url($g['image']), $g['caption']], gallery_for_occasion($content['occasion'] ?? null, 8))]
            : [url($content['slug']), $content['updated_at'], 'yearly', '0.3', []];
    }
    foreach (get_categories() as $cat) {
        $entries[] = [category_url($cat), $cat['updated_at'], 'weekly', '0.8', array_filter([$photo($cat['image'] ?: $cat['cover_image'], $cat['name'] . ' from ' . setting('store_short_name'))])];
    }
    $extraPhotos = [];
    foreach (db_all('SELECT product_id, image, alt FROM product_images ORDER BY sort_order, id') as $row) {
        $extraPhotos[$row['product_id']][] = [product_image_url($row['image']), $row['alt'] ?: ''];
    }
    foreach (get_products() as $product) {
        $photos = array_merge(array_filter([$photo($product['image'], product_image_alt($product))]), array_map(
            static fn ($p) => [$p[0], $p[1] !== '' ? $p[1] : $product['name']], $extraPhotos[$product['id']] ?? []
        ));
        $entries[] = [product_url($product), $product['updated_at'], 'weekly', '0.7', $photos];
    }
    return $entries;
}

/**
 * Addresses that changed since the last check: new, changed (later "last changed" date) and
 * removed ones. Returns [changed addresses, new snapshot].
 */
function sitemap_changes(array $entries, array $snapshot): array
{
    $now = [];
    foreach ($entries as [$loc, $updated]) {
        $now[$loc] = (string) $updated;
    }
    $changed = [];
    foreach ($now as $loc => $updated) {
        if (!array_key_exists($loc, $snapshot) || $snapshot[$loc] !== $updated) {
            $changed[] = $loc;
        }
    }
    foreach (array_keys($snapshot) as $loc) {
        if (!array_key_exists($loc, $now)) {
            $changed[] = (string) $loc;
        }
    }
    return [$changed, $now];
}

/** True when the website address is a real public one (not localhost), so search engines can visit it. */
function site_is_public(): bool
{
    $host = (string) parse_url((string) config('app_url'), PHP_URL_HOST);
    return str_starts_with((string) config('app_url'), 'https://') && str_contains($host, '.')
        && !filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/(^|\.)(localhost|test|local)$/', $host);
}

// ---------------------------------------------------------------------------
// IndexNow (Bing and the others)
// ---------------------------------------------------------------------------

/** The site's IndexNow key, made once. It is public on purpose: it is served at /{key}.txt. */
function indexnow_key(): string
{
    $key = setting('indexnow_key');
    if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
        $key = bin2hex(random_bytes(16));
        setting_save('indexnow_key', $key);
    }
    return $key;
}

/** Sends up to 10,000 addresses to IndexNow. Returns [ok, message]. */
function indexnow_submit(array $urls): array
{
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) {
        return [true, 'Nothing to send.'];
    }
    $key = indexnow_key();
    $body = json_encode([
        'host'        => parse_url(url(), PHP_URL_HOST),
        'key'         => $key,
        'keyLocation' => url($key . '.txt'),
        'urlList'     => array_slice($urls, 0, 10000),
    ], JSON_UNESCAPED_SLASHES);
    [$status, $response, $error] = http_request('POST', 'https://api.indexnow.org/indexnow', ['Content-Type: application/json; charset=utf-8'], $body, 20);
    return match (true) {
        $status === 200, $status === 202 => [true, count($urls) . ' addresses sent to Bing (IndexNow).'],
        $status === 0   => [false, 'Bing (IndexNow) could not be reached: ' . $error],
        $status === 403 => [false, 'Bing (IndexNow) could not read the key file ' . url($key . '.txt') . '. It must open on the live website.'],
        $status === 422 => [false, 'Bing (IndexNow) refused the addresses: they must be on ' . parse_url(url(), PHP_URL_HOST) . '.'],
        $status === 429 => [false, 'Bing (IndexNow) asked us to slow down. It will be tried again later.'],
        default         => [false, 'Bing (IndexNow) answered HTTP ' . $status . ' ' . str_limit(trim(strip_tags($response)), 120)],
    };
}

// ---------------------------------------------------------------------------
// Google Search Console (service account)
// ---------------------------------------------------------------------------

/** The saved service account key (client_email, private_key), or null. */
function gsc_account(): ?array
{
    $json = secret_decrypt(setting('gsc_service_account'));
    $data = $json !== '' ? json_decode($json, true) : null;
    return is_array($data) && !empty($data['client_email']) && !empty($data['private_key']) ? $data : null;
}

/** Checks a downloaded service account key file. Returns [data, error]. */
function gsc_parse_account(string $json): array
{
    $data = json_decode($json, true);
    if (!is_array($data) || ($data['type'] ?? '') !== 'service_account' || empty($data['client_email']) || empty($data['private_key'])) {
        return [null, 'That is not a Google service account key. Download the JSON key of the service account again.'];
    }
    if (!openssl_pkey_get_private((string) $data['private_key'])) {
        return [null, 'The private key in that file cannot be read. Download a new JSON key.'];
    }
    return [array_intersect_key($data, array_flip(['type', 'client_email', 'private_key', 'token_uri', 'project_id'])), null];
}

/** The signed request (JWT) that a service account swaps for an access token. */
function gsc_jwt(array $account, string $tokenUrl, ?int $now = null): string
{
    $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    $now ??= time();
    $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode([
        'iss' => $account['client_email'], 'scope' => 'https://www.googleapis.com/auth/webmasters',
        'aud' => $tokenUrl, 'iat' => $now, 'exp' => $now + 3600,
    ], JSON_UNESCAPED_SLASHES));
    if (!openssl_sign($unsigned, $signature, (string) $account['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('The service account key could not sign the request.');
    }
    return $unsigned . '.' . $b64($signature);
}

/** A one-hour access token for the Search Console API. */
function gsc_token(array $account): string
{
    static $tokens = [];
    if (isset($tokens[$account['client_email']])) {
        return $tokens[$account['client_email']];
    }
    $tokenUrl = (string) ($account['token_uri'] ?? '') ?: 'https://oauth2.googleapis.com/token';
    [$status, $body, $error] = http_request('POST', $tokenUrl, ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => gsc_jwt($account, $tokenUrl)]), 20);
    $data = json_decode($body, true) ?: [];
    if ($status !== 200 || empty($data['access_token'])) {
        throw new RuntimeException('Google did not accept the service account: ' . ($data['error_description'] ?? $data['error'] ?? ($error ?: 'HTTP ' . $status)));
    }
    return $tokens[$account['client_email']] = (string) $data['access_token'];
}

/** A Search Console API call. Returns the decoded answer; throws with Google's message on an error. */
function gsc_api(string $method, string $path, ?array $account = null): array
{
    $account ??= gsc_account();
    if (!$account) {
        throw new RuntimeException('Google Search Console is not connected.');
    }
    [$status, $body, $error] = http_request($method, 'https://www.googleapis.com/webmasters/v3/' . $path,
        ['Authorization: Bearer ' . gsc_token($account), 'Content-Length: 0'], $method === 'GET' ? null : '', 20);
    $data = json_decode($body, true) ?: [];
    if ($status < 200 || $status >= 300) {
        $message = $data['error']['message'] ?? ($error ?: 'HTTP ' . $status);
        if ($status === 403) {
            $message = 'The service account ' . $account['client_email'] . ' is not a user of this Search Console property yet. Add it in Search Console > Settings > Users and permissions as Owner. (' . $message . ')';
        }
        throw new RuntimeException($message);
    }
    return $data;
}

/**
 * The Search Console property for this website: the one saved, else the one the service account
 * can see that matches the website (a domain property "sc-domain:..." or the https:// address).
 */
function gsc_property(?array $account = null): string
{
    $saved = setting('gsc_property');
    if ($saved !== '') {
        return $saved;
    }
    $host = preg_replace('/^www\./', '', (string) parse_url(url(), PHP_URL_HOST));
    $sites = array_column(gsc_api('GET', 'sites', $account)['siteEntry'] ?? [], 'siteUrl');
    foreach (['sc-domain:' . $host, rtrim(url(), '/') . '/', 'https://' . $host . '/', 'https://www.' . $host . '/'] as $candidate) {
        if (in_array($candidate, $sites, true)) {
            setting_save('gsc_property', $candidate);
            return $candidate;
        }
    }
    throw new RuntimeException($sites
        ? 'The service account sees ' . implode(', ', $sites) . ' but not ' . $host . '. Add it to the right property in Search Console.'
        : 'The service account does not see any Search Console property yet. In Search Console > Settings > Users and permissions, add ' . ($account ?? gsc_account())['client_email'] . ' as Owner.');
}

/** Submits (or re-submits) the sitemap to Google. Returns [ok, message]. */
function gsc_submit_sitemap(): array
{
    try {
        $property = gsc_property();
        gsc_api('PUT', 'sites/' . rawurlencode($property) . '/sitemaps/' . rawurlencode(url('sitemap.xml')));
        setting_save('gsc_last_submit', date('Y-m-d H:i:s'));
        return [true, 'Sitemap submitted to Google Search Console.'];
    } catch (Throwable $e) {
        return [false, 'Google: ' . $e->getMessage()];
    }
}

/** What Google reports about the sitemap: last read, errors, addresses submitted and indexed. */
function gsc_sitemap_status(): ?array
{
    try {
        return gsc_api('GET', 'sites/' . rawurlencode(gsc_property()) . '/sitemaps/' . rawurlencode(url('sitemap.xml')));
    } catch (Throwable) {
        return null;
    }
}

// ---------------------------------------------------------------------------
// The check itself (cron job and the button)
// ---------------------------------------------------------------------------

/** Keeps the last 20 results for the admin page. */
function search_engine_log(string $message, bool $ok): void
{
    $log = json_decode(setting('search_engine_log', '[]'), true) ?: [];
    array_unshift($log, ['at' => date('Y-m-d H:i:s'), 'ok' => $ok, 'text' => $message]);
    setting_save('search_engine_log', json_encode(array_slice($log, 0, 20), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/**
 * Sends what changed in the sitemap ($everything: every address) to Bing and Google.
 * Returns [all went well, one-line report]. Google gets the sitemap at most once an hour, unless $everything.
 */
function search_engines_sync(bool $everything = false): array
{
    if (!site_is_public()) {
        return [false, 'The website is not on a public https address yet, so nothing was sent.'];
    }
    $snapshot = json_decode(setting('sitemap_snapshot', '{}'), true) ?: [];
    [$changed, $now] = sitemap_changes(sitemap_entries(), $snapshot);
    if ($everything) {
        $changed = array_values(array_unique(array_merge(array_keys($now), $changed)));
    }
    $pendingGoogle = setting('gsc_pending') === '1';
    if (!$changed && !$pendingGoogle) {
        return [true, 'No changes to send.'];
    }
    $report = [];
    $allOk = true;
    if ($changed) {
        setting_save('sitemap_snapshot', json_encode($now, JSON_UNESCAPED_SLASHES));
        if (setting('indexnow_enabled', '1') === '1') {
            [$ok, $message] = indexnow_submit($changed);
            search_engine_log($message, $ok);
            $report[] = $message;
            $allOk = $allOk && $ok;
        }
    }
    if (gsc_account()) {
        $last = strtotime(setting('gsc_last_submit', '2000-01-01'));
        if ($everything || time() - $last >= 3600) {
            [$ok, $message] = gsc_submit_sitemap();
            setting_save('gsc_pending', $ok ? '0' : '1');
            search_engine_log($message, $ok);
            $report[] = $message;
            $allOk = $allOk && $ok;
        } else {
            setting_save('gsc_pending', '1');
            $report[] = 'Google: sitemap will be submitted again within the hour.';
        }
    }
    return [$allOk, $report ? implode(' ', $report) : count($changed) . ' changed addresses noted (sending is switched off).'];
}

/** Cron job: runs every time the cron job runs (every 15 minutes). */
function search_engine_task(DateTimeImmutable $now): string
{
    return 'Search engines: ' . search_engines_sync()[1];
}

// ---------------------------------------------------------------------------
// llms.txt: a plain summary of the shop for AI assistants (ChatGPT, Perplexity, Claude, Gemini)
// ---------------------------------------------------------------------------

function llms_txt(): string
{
    $lines = ['# ' . setting('store_name'), ''];
    $lines[] = '> ' . (setting('seo_home_description') ?: 'Independent cake shop in Greenway, Canberra (established March 2015), baking fresh cakes, tortes, cheesecakes and custom celebration cakes with 100% Halal ingredients.');
    $lines[] = '';
    foreach (active_branches() as $shop) {
        $lines[] = '- Shop: ' . $shop['name'] . ', ' . branch_address_line($shop) . (branch_phone($shop) !== '' ? ', phone ' . branch_phone($shop) : '');
    }
    if (setting('store_founded') !== '') {
        $lines[] = '- Established: ' . (setting('store_founded') === '2015' ? 'March 2015' : setting('store_founded')) . (setting('store_abn') !== '' ? ' · ABN ' . setting('store_abn') : '');
    }
    $lines[] = '- Opening hours: ' . implode('; ', array_map(static fn ($g) => $g['days'] . ' ' . $g['hours'], opening_hours_grouped()));
    $deliveryFee = (float) setting('delivery_fee', '0');
    $postcodes = trim(setting('delivery_postcodes'));
    $deliveryDetail = setting_bool('delivery_enabled')
        ? ' or delivery across Canberra & Queanbeyan' . ($postcodes !== '' ? ' (postcodes ' . $postcodes . ')' : '') . ($deliveryFee > 0 ? ' for ' . money($deliveryFee) : '')
        : '';
    $lines[] = '- Order online for pickup' . $deliveryDetail . ', at least ' . notice_label((int) setting('min_lead_hours', '24')) . ' ahead for menu cakes and at least ' . days_label((int) setting('custom_lead_days', '3')) . ' ahead for custom designs (2 to 8+ weeks recommended for tiered wedding cakes).';
    $lines[] = '- Dietary & Halal standard: Every product is baked in our Greenway kitchen with 100% Halal ingredients (zero alcohol, no animal shortening, and Halal-suitable gelatine and vanilla). Eggless, vegan and gluten-free options are available on request.';
    $lines[] = '';
    $lines[] = '## Pages';
    foreach ([['shop', 'All cakes with sizes and prices'], ['custom-cakes', 'Order a custom birthday or wedding cake'], ['custom-cake-gallery', 'Custom cake photo gallery'], ['about', 'About the shop and our Greenway kitchen'], ['contact', 'Contact, opening hours and directions'], ['faq', 'Questions and answers']] as [$path, $label]) {
        $lines[] = '- [' . $label . '](' . url($path) . ')';
    }
    foreach (db_all("SELECT slug, title FROM pages WHERE is_active = 1 ORDER BY type, title") as $page) {
        $lines[] = '- [' . $page['title'] . '](' . url($page['slug']) . ')';
    }
    // Short answers to common questions, the way an assistant would quote them.
    $faqs = get_faqs();
    if ($faqs) {
        $lines[] = '';
        $lines[] = '## Questions and answers';
        foreach ($faqs as $faq) {
            $lines[] = '- ' . trim($faq['question']) . ' ' . str_limit(content_text((string) $faq['answer']), 500);
        }
    }
    $byCategory = [];
    foreach (get_products() as $product) {
        $byCategory[$product['category_id']][] = $product;
    }
    foreach (get_categories() as $cat) {
        if (empty($byCategory[$cat['id']])) {
            continue;
        }
        $lines[] = '';
        $lines[] = '## [' . $cat['name'] . '](' . category_url($cat) . ')';
        foreach ($byCategory[$cat['id']] as $product) {
            $price = !empty($product['is_enquiry']) || empty($product['price_from']) ? 'price on request' : 'from ' . money($product['price_from']);
            $lines[] = '- [' . $product['name'] . '](' . product_url($product) . '): ' . $price . (trim((string) $product['short_description']) !== '' ? '. ' . trim((string) $product['short_description']) : '');
        }
    }
    return implode("\n", $lines) . "\n";
}
