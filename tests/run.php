<?php
declare(strict_types=1);

// Quick checks for the rules the shop depends on: prices, coupons, delivery areas,
// pickup times, safe HTML, Stripe signatures, SEO helpers and payments.
//
//   php tests/run.php
//
// Uses the database in config/config.php. The payments test makes one order called
// "Test Runner" and deletes it again at the end. Best run on a copy of the live database.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';

$passed = 0;
$failed = [];
function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo '.';
    } else {
        $failed[] = $name . ($detail !== '' ? ' (' . $detail . ')' : '');
        echo 'F';
    }
}
function same(string $name, mixed $expected, mixed $actual): void
{
    check($name, $expected === $actual, 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

// --- Helpers ---------------------------------------------------------------------------------
same('money formats', '$1,234.50', money(1234.5));
check('Australian mobile accepted', valid_au_phone('0412 345 678'));
check('+61 number accepted', valid_au_phone('+61 2 6293 9578'));
check('short number refused', !valid_au_phone('12345'));
same('order number found in a reply', 'CC-1042', order_number_in('Re: Update on order cc-1042 - The Continental Cake Shop'));
same('older order number still found', 'CCS-261113-58AF4', order_number_in('Re: Order CCS-261113-58AF4 confirmed'));
same('no order number in a plain subject', null, order_number_in('Question about cakes for 2026'));
check('next order number is short and in sequence', (bool) preg_match('/^CC-\d{4,}$/', $next = generate_order_number())
    && (int) substr($next, 3) === max(ORDER_NUMBER_START, (int) db_value("SELECT MAX(CAST(SUBSTRING(order_number, 4) AS UNSIGNED)) FROM orders WHERE order_number LIKE 'CC-%'") + 1), $next);
same('notice in days', '2 days', notice_label(48));
same('notice in hours', '6 hours', notice_label(6));
same('one hour of notice', '1 hour', notice_label(1));

// --- Safe HTML -------------------------------------------------------------------------------
$dirty = '<p onclick="x()">Hi<script>alert(1)</script></p><a href="javascript:alert(1)">x</a><img src="data:x" onerror="y"><p style="color:red;text-align:center">c</p>';
$clean = clean_html($dirty);
check('scripts removed', !str_contains($clean, 'script'));
check('event attributes removed', !preg_match('/on(click|error)/i', $clean));
check('javascript links removed', !str_contains($clean, 'javascript:'));
check('data images removed', !str_contains($clean, 'data:'));
check('text alignment kept', str_contains($clean, 'style="text-align: center"') && !str_contains($clean, 'color:red'));

// --- Coupons and totals ----------------------------------------------------------------------
$percent = ['discount_type' => 'percent', 'discount_value' => '10'];
$fixed = ['discount_type' => 'fixed', 'discount_value' => '50'];
same('10% of $80', 8.0, coupon_discount($percent, 80));
same('fixed discount never above the subtotal', 30.0, coupon_discount($fixed, 30));
$totals = order_totals(100, 'pickup', $percent);
same('pickup total with coupon', 90.0, $totals['total']);
same('pickup has no delivery fee', 0.0, $totals['delivery_fee']);

// --- Delivery areas --------------------------------------------------------------------------
$saved = setting('delivery_postcodes');
setting_save('delivery_postcodes', '2600-2620, 2900-2914');
check('postcode in range', delivery_postcode_allowed('2905'));
check('postcode outside range', !delivery_postcode_allowed('2000'));
check('bad postcode refused', !delivery_postcode_allowed('29a5'));
setting_save('delivery_postcodes', $saved);

// --- Opening hours ---------------------------------------------------------------------------
$hours = opening_hours();
$openDay = null;
for ($i = 1; $i <= 14 && !$openDay; $i++) {
    $day = new DateTimeImmutable("+{$i} days");
    if ($hours[day_key($day)] && !is_blocked_date($day->format('Y-m-d'))) {
        $openDay = $day;
    }
}
if ($openDay) {
    [$open, $close] = $hours[day_key($openDay)];
    $mid = new DateTimeImmutable($openDay->format('Y-m-d') . ' ' . $open);
    check('open during opening hours', today_status($mid->modify('+1 hour'))['open']);
    check('closed before opening', !today_status($mid->modify('-1 hour'))['open']);
    $slots = pickup_slots($openDay->format('Y-m-d'), 0);
    check('pickup times inside opening hours', $slots && $slots[0] >= substr($open, 0, 5) && end($slots) < substr($close, 0, 5), implode(',', array_slice($slots, 0, 3)));
}
same('no pickup times on a bad date', [], pickup_slots('2026-02-30', 0));

// --- SQL splitting for migrations ------------------------------------------------------------
same('semicolons inside quotes', 2, count(split_sql("INSERT INTO t VALUES ('a;b');\n-- comment;\nSELECT 1;")));

// --- Stripe webhook signature ----------------------------------------------------------------
$payload = '{"id":"evt_1"}';
$time = time();
$sig = hash_hmac('sha256', $time . '.' . $payload, 'whsec_test');
check('valid Stripe signature', stripe_verify_webhook($payload, "t={$time},v1={$sig}", 'whsec_test'));
check('wrong secret refused', !stripe_verify_webhook($payload, "t={$time},v1={$sig}", 'whsec_other'));
check('old signature refused', !stripe_verify_webhook($payload, 't=' . ($time - 3600) . ",v1={$sig}", 'whsec_test'));

// --- SEO -------------------------------------------------------------------------------------
check('automatic titles fit in 60 characters', mb_strlen(product_default_title(['name' => 'Double Chocolate Strawberry Torte With Cream'])) <= 60);
$checks = product_seo_checks(['name' => 'Red Velvet Cake', 'slug' => 'red-velvet-cake', 'focus_keyword' => 'red velvet cake canberra',
    'meta_title' => 'Red Velvet Cake Canberra | Order Online', 'meta_description' => '', 'description' => '', 'short_description' => '', 'image' => 'x.webp'], 0,
    [['label' => 'Midi', 'servings' => 'Serves 8', 'price' => 39.95], ['label' => 'Full', 'servings' => 'Serves 14', 'price' => 49.95]]);
check('keyword found in title', $checks[1][1]);
check('keyword found in default description', $checks[2][1]);
check('keyword words found in web address', $checks[3][1]);
check('default description is 120 to 160 chars', $checks[6][1]);
$defDesc = product_default_description(['name' => 'Red Velvet Cake', 'is_enquiry' => 0], [['label' => 'Midi', 'servings' => 'Serves 8', 'price' => 39.95], ['label' => 'Full', 'servings' => 'Serves 14', 'price' => 49.95]]);
check('default description does not echo title opening', !str_starts_with(strtolower($defDesc), 'red velvet cake canberra'));
$prodSchema = product_schema([
    'id' => 99, 'name' => 'Test Torte', 'slug' => 'test-torte', 'category_name' => 'Tortes',
    'description' => '<p>Full sponge layers with sour cherries and fresh cream baked in Greenway.</p>',
    'short_description' => 'Short note.', 'meta_description' => 'Tiny meta.', 'image' => 'x.webp', 'is_enquiry' => 0,
], [['label' => 'Midi', 'servings' => 'Serves 8', 'price' => 44.95]]);
check('product schema prefers full description over short meta_description', str_contains($prodSchema['description'], 'Full sponge layers'));
check('bakery schema includes menu link', (local_business_schema()['menu'] ?? '') === url('shop'));
check('bakery schema includes foundingDate', (local_business_schema()['foundingDate'] ?? '') !== '');
$wpSchema = webpage_schema('Wedding cakes in Canberra', url('wedding-cakes-canberra'), 'Custom Halal wedding cakes.', '2026-11-18 06:00:00');
check('webpage schema includes ISO dateModified and bakery reference', str_starts_with($wpSchema['dateModified'] ?? '', '2026-11-18') && ($wpSchema['about']['@id'] ?? '') === url('#bakery'));
same('days_label singular and plural', ['1 day', '3 days'], [days_label(1), days_label(3)]);
check('clean_html preserves table scope and nested {url:path/file} tokens', str_contains(clean_html('<table><thead><tr><th scope="col">Size</th></tr></thead></table><p><a href="{url:shop/tortes}">Tortes</a></p>'), 'scope="col"') && str_contains(clean_html('<p><a href="{url:shop/tortes}">Tortes</a></p>'), '{url:shop/tortes}'));
check('old addresses redirect', legacy_redirect_target('about-us') === url('about'));

// --- HTML sanitizer: only safe tags, attributes and links survive -----------------------------
$xss = [
    '<img src="/u/x.webp"/onerror="alert(1)">', '<a href="&#106;avascript:alert(1)">x</a>', '<a href="javascript&colon;alert(1)">x</a>',
    "<a href=\"java\tscript:alert(1)\">x</a>", '<svg onload=alert(1)>', '<p onmouseover="x()">p</p>', '<img src="data:image/png;base64,AA">',
    '<a href="javascript:alert(\'x\')">x</a>', '<img src=x alt="a"onerror=alert(1)>', '<iframe src="https://x.com"></iframe>',
];
$leaks = array_filter($xss, static fn ($h) => preg_match('/onerror|onload|onmouseover|javascript|data:|<svg|<iframe/i', clean_html($h)));
check('sanitizer blocks script tricks', !$leaks, implode(' | ', array_map('clean_html', $leaks)));
same('sanitizer keeps normal formatting', '<h2>Hi</h2><p style="text-align: center"><a href="{url:shop}">Shop</a> <strong>now</strong></p>', clean_html('<h2>Hi</h2><p style="text-align:center;color:red" class="x"><a href="{url:shop}" onclick="y()">Shop</a> <strong>now</strong></p>'));
check('only web links for settings', safe_web_url('https://g.page/x') && !safe_web_url('javascript://x/%0aalert(1)'));
same('first name with a link becomes "there"', 'there', email_first_name('https://evil.example/x Smith'));

// --- Rate limits count across sessions ------------------------------------------------------
$key = 'test-' . bin2hex(random_bytes(4));
$results = array_map(static fn () => rate_hit('tests', $key, 3, 60), range(1, 4));
same('fourth try is over a limit of three', [false, false, false, true], $results);
rate_clear('tests', $key);
check('limit starts again after clearing', !rate_hit('tests', $key, 3, 60));
rate_clear('tests', $key);

// --- Payments ledger (the test order is deleted afterwards) ----------------------------------
try {
    $order = create_order([
        'channel' => 'phone', 'name' => 'Test Runner', 'email' => '', 'phone' => '0400000000', 'fulfilment' => 'pickup',
        'pickup_date' => date('Y-m-d', strtotime('+3 days')), 'pickup_time' => '10:00', 'payment_method' => 'cash',
    ], [[
        'name' => 'Test cake', 'size_label' => 'Midi', 'options' => 'Eggless', 'price' => 50.0, 'qty' => 2, 'line_total' => 100.0,
    ]], null, ['subtotal' => 100.0, 'discount' => 0.0, 'delivery_fee' => 0.0, 'total' => 100.0]);
    record_payment($order, 40, 'cash', 'test-deposit-' . $order['id']);
    $order = get_order((int) $order['id']);
    same('deposit makes the order part paid', 'partially_paid', $order['payment_status']);
    same('balance after deposit', 60.0, order_balance($order));
    check('same payment is not counted twice', !record_payment($order, 40, 'cash', 'test-deposit-' . $order['id']));
    record_payment($order, 60, 'eftpos');
    same('fully paid', 'paid', get_order((int) $order['id'])['payment_status']);
    record_refund(get_order((int) $order['id']), 100, 'cash');
    same('full refund', 'refunded', get_order((int) $order['id'])['payment_status']);
    same('options saved on the line', 'Eggless', get_order_items((int) $order['id'])[0]['options']);
} finally {
    if (isset($order['id'])) {
        db_run('DELETE FROM orders WHERE id = ? AND customer_name = ?', [$order['id'], 'Test Runner']);
    }
}

// --- AI writer and search engines ---------------------------------------------------------------
same('AI answer inside a code fence is read', 'x', ai_json("Sure!\n```json\n{\"a\": \"x\"}\n```")['a'] ?? null);
check('AI answer without JSON is refused', ai_json('Sorry, I cannot help.') === null);
same('a retired model is replaced by the best chat model offered', 'openai/gpt-oss-120b',
    ai_pick_model(['whisper-large-v3', 'llama-3.1-8b-instant', 'meta-llama/llama-prompt-guard-2-86m', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b']));
same('Gemini moves to its newest Flash model', 'gemini-flash-latest', ai_pick_model(['gemini-3.8-flash-tts', 'gemini-flash-latest', 'gemini-3.8-flash']));
check('no model is picked from a list of speech models only', ai_pick_model(['whisper-large-v3', 'playai-tts']) === null);
check('keyword ideas keep buying searches', keyword_is_useful('black forest cake canberra', ['black', 'forest']) && keyword_is_useful('red velvet cakes near me', ['velvet']));
check('keyword ideas drop recipes, other cities and other shops', !keyword_is_useful('black forest cake recipe', ['black']) && !keyword_is_useful('black forest cake sydney', ['black']) && !keyword_is_useful('black forest cake woolworths', ['black']) && !keyword_is_useful('chocolate mud cake', ['black']));
check('thinking models are told apart (they use more tokens)', ai_model_thinks('openai/gpt-oss-120b') && ai_model_thinks('gemini-flash-latest') && !ai_model_thinks('llama-3.3-70b-versatile') && !ai_model_thinks('gemini-3.5-flash-lite'));
$choices = ai_model_choices([['id' => 'whisper-large-v3'], ['id' => 'llama-3.1-8b-instant'], ['id' => 'openai/gpt-oss-120b', 'context_window' => 131072], ['id' => 'meta-llama/llama-4-scout-17b-16e-instruct']]);
same('model list: recommended, then the fewest tokens, speech hidden', 'openai/gpt-oss-120b, meta-llama/llama-4-scout-17b-16e-instruct, llama-3.1-8b-instant | 1',
    implode(', ', array_column($choices['models'], 'id')) . ' | ' . $choices['hidden']);
same('links to unknown pages become text', '<p>See <a href="https://shop.test/cakes/a">A</a> and B.</p>',
    ai_keep_links('<p>See <a href="https://shop.test/cakes/a">A</a> and <a href="https://evil.test">B</a>.</p>', ['https://shop.test/cakes/a']));
$fields = ai_clean_fields([
    'focus_keyword' => ' "Red Velvet Cake Canberra" ', 'meta_title' => 'Red Velvet Cake Canberra | Fresh and Halal | The Continental Cake Shop',
    'meta_description' => str_repeat('Soft red velvet layers. ', 10), 'short_description' => 'A soft cake.',
    'description' => '<p>Call {store_phone} or {secret_token}.</p><script>x</script>', 'search_words' => ['Red Velvet', 'red velvet', 'velvet cake canberra'],
    'image_alt' => 'Red velvet cake',
], []);
same('AI keyword tidied', 'red velvet cake canberra', $fields['focus_keyword']);
check('AI title fits 60 characters', mb_strlen($fields['meta_title']) <= 60 && str_starts_with($fields['meta_title'], 'Red Velvet Cake Canberra'), $fields['meta_title']);
check('AI search description fits 160 characters', mb_strlen($fields['meta_description']) <= 160);
check('only the website placeholders are kept', str_contains($fields['description'], '{store_phone}') && !str_contains($fields['description'], 'secret_token') && !str_contains($fields['description'], 'script'));
same('AI search words tidied', 'red velvet, velvet cake canberra', $fields['search_words']);
[$changed, $snapshot] = sitemap_changes([['https://s.test/', '2026-01-02'], ['https://s.test/cakes/new', '2026-01-02'], ['https://s.test/cakes/same', '2026-01-01']],
    ['https://s.test/' => '2026-01-01', 'https://s.test/cakes/same' => '2026-01-01', 'https://s.test/cakes/gone' => '2026-01-01']);
same('sitemap changes: changed, new and removed addresses', ['https://s.test/', 'https://s.test/cakes/new', 'https://s.test/cakes/gone'], $changed);
same('sitemap snapshot has the current addresses', 3, count($snapshot));
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($key, $pem);
[$account] = gsc_parse_account(json_encode(['type' => 'service_account', 'client_email' => 'site@p.iam.gserviceaccount.com', 'private_key' => $pem, 'private_key_id' => 'x']));
check('service account key accepted', $account !== null && !isset($account['private_key_id']));
check('other JSON refused as a service account', gsc_parse_account('{"type":"authorized_user"}')[0] === null);
$jwt = explode('.', gsc_jwt($account, 'https://oauth2.googleapis.com/token', 1700000000));
$claims = json_decode(base64_decode(strtr($jwt[1], '-_', '+/')), true);
check('Google token request is signed with the key', openssl_verify($jwt[0] . '.' . $jwt[1], base64_decode(strtr($jwt[2], '-_', '+/') . str_repeat('=', (4 - strlen($jwt[2]) % 4) % 4)), openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256) === 1);
same('Google token request asks for Search Console only', 'https://www.googleapis.com/auth/webmasters', $claims['scope'] ?? null);

// --- Custom cake photos and answers for search ------------------------------------------------
check('a gallery photo outside the uploads folder is never copied', copy_gallery_photo(['id' => 1, 'image' => '../config/config.php']) === null);
$photos = custom_request_photos(['gallery_image' => 'custom/2026/11/gallery-1-a.webp', 'reference_image' => 'custom/2026/11/b.heic']);
check('request photos: the gallery cake first, a HEIC file as a link', count($photos) === 2 && $photos[0]['label'] === 'Chosen from your gallery' && $photos[0]['is_image'] && !$photos[1]['is_image']);
$order = array_map(static fn ($f) => $f['category'], landing_faqs(20));
check('landing page questions: custom cakes first, no payment or cancellation answers', ($order[0] ?? '') === 'Custom cakes' && !array_intersect($order, ['Payment', 'Cancellations']));
check('llms.txt has the answers', str_contains(llms_txt(), '## Questions and answers'));

echo "\n\n{$passed} passed";
if ($failed) {
    echo ', ' . count($failed) . " failed:\n - " . implode("\n - ", $failed) . "\n";
    exit(1);
}
echo ", all good.\n";
