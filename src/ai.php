<?php
declare(strict_types=1);

// AI SEO writer (Admin > AI SEO agent and the product editor).
//
// It uses free AI services that speak the OpenAI "chat completions" format (Groq, Google Gemini,
// OpenRouter, Cerebras, Mistral, AgentRouter or any other). The owner adds one or more with their
// own API key; they are tried in order, and when one fails (limit reached, down, wrong key) the
// next one writes instead. Keys are stored encrypted like the email password.
//
// The AI is not trained once: every request sends it what it needs to know, so it always uses
// today's data. That is the shop profile the owner writes, facts read from the website (shops,
// opening hours, delivery, notice, categories, FAQs), two of our own cake descriptions as a style
// example, and the SEO rules below. AI only ever runs in the admin panel, never for customers,
// so the website itself stays as fast as before.

/** Services with their address, a good free model and where to get a key. */
const AI_SERVICES = [
    'groq'        => ['name' => 'Groq', 'url' => 'https://api.groq.com/openai/v1', 'model' => 'llama-3.3-70b-versatile', 'keys' => 'https://console.groq.com/keys'],
    'gemini'      => ['name' => 'Google Gemini', 'url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => 'gemini-2.5-flash', 'keys' => 'https://aistudio.google.com/apikey'],
    'openrouter'  => ['name' => 'OpenRouter', 'url' => 'https://openrouter.ai/api/v1', 'model' => 'meta-llama/llama-3.3-70b-instruct:free', 'keys' => 'https://openrouter.ai/settings/keys'],
    'cerebras'    => ['name' => 'Cerebras', 'url' => 'https://api.cerebras.ai/v1', 'model' => 'llama-3.3-70b', 'keys' => 'https://cloud.cerebras.ai'],
    'mistral'     => ['name' => 'Mistral', 'url' => 'https://api.mistral.ai/v1', 'model' => 'mistral-small-latest', 'keys' => 'https://console.mistral.ai/api-keys'],
    'agentrouter' => ['name' => 'AgentRouter', 'url' => 'https://agentrouter.org/v1', 'model' => '', 'keys' => 'https://agentrouter.org'],
    'custom'      => ['name' => 'Other (OpenAI compatible)', 'url' => '', 'model' => '', 'keys' => ''],
];

/** Fields the AI writes for a cake (also the fields kept in the version history). */
const AI_PRODUCT_FIELDS = ['focus_keyword', 'meta_title', 'meta_description', 'short_description', 'description', 'search_words', 'image_alt'];

/** Placeholders the website fills in, so the text always shows today's phone, address and notice. */
const AI_PLACEHOLDERS = ['{store_phone}', '{store_address}', '{min_lead_hours}'];

// ---------------------------------------------------------------------------
// Services
// ---------------------------------------------------------------------------

/** The services the owner added, in the order they are tried. */
function ai_providers(): array
{
    $list = json_decode(setting('ai_providers', '[]'), true);
    return is_array($list) ? array_values(array_filter($list, 'is_array')) : [];
}

function ai_providers_save(array $list): void
{
    setting_save('ai_providers', json_encode(array_values($list), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function ai_provider(string $id): ?array
{
    foreach (ai_providers() as $p) {
        if (($p['id'] ?? '') === $id) {
            return $p;
        }
    }
    return null;
}

/** True when at least one service is switched on and has a key. */
function ai_ready(): bool
{
    return (bool) array_filter(ai_providers(), static fn ($p) => !empty($p['enabled']) && ($p['key'] ?? '') !== '');
}

/** Remembers how a service did last time (shown on the AI page) and rests it for a minute after "too many requests". */
function ai_note_result(string $id, bool $ok, string $message, int $ms, bool $limited = false): void
{
    $list = ai_providers();
    foreach ($list as &$p) {
        if (($p['id'] ?? '') === $id) {
            $p['last_at'] = date('Y-m-d H:i:s');
            $p['last_ok'] = $ok;
            $p['last_message'] = str_limit($message, 300);
            $p['last_ms'] = $ms;
            $p['rest_until'] = $limited ? time() + 60 : 0;
        }
    }
    unset($p);
    ai_providers_save($list);
}

/** The error text from a service's answer (OpenAI and Gemini shapes). */
function ai_error_text(int $status, string $body, string $curlError): string
{
    if ($status === 0) {
        return 'Could not reach the service: ' . ($curlError ?: 'no answer');
    }
    $data = json_decode($body, true);
    $data = is_array($data) && array_is_list($data) ? ($data[0] ?? []) : $data;
    $message = is_array($data) ? ($data['error']['message'] ?? $data['message'] ?? (is_string($data['error'] ?? null) ? $data['error'] : null)) : null;
    $hint = match (true) {
        $status === 401, $status === 403 => 'The API key was not accepted. ',
        $status === 404 => 'Model or address not found. ',
        $status === 429 => 'Free limit reached for now. ',
        $status >= 500  => 'The service is having problems. ',
        default         => '',
    };
    return $hint . 'HTTP ' . $status . ($message ? ': ' . str_limit(trim((string) $message), 220) : '');
}

/** One request to one service. Returns the answer text; throws with a readable error. */
function ai_call(array $p, array $messages, int $maxTokens = 2500, float $temperature = 0.6, int $timeout = 50): string
{
    $key = secret_decrypt((string) ($p['key'] ?? ''));
    if ($key === '' || trim((string) ($p['model'] ?? '')) === '') {
        throw new RuntimeException('Add the API key and the model first.');
    }
    $body = json_encode(['model' => $p['model'], 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $maxTokens], JSON_UNESCAPED_UNICODE);
    [$status, $response, $error] = http_request('POST', rtrim((string) $p['url'], '/') . '/chat/completions',
        ['Content-Type: application/json', 'Authorization: Bearer ' . $key], $body, $timeout);
    if ($status !== 200) {
        throw new RuntimeException(ai_error_text($status, $response, $error), $status);
    }
    $data = json_decode($response, true);
    $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    // Some free "thinking" models put their notes first.
    $text = trim((string) preg_replace('#<think>.*?</think>#s', '', $text));
    if ($text === '') {
        throw new RuntimeException('The service sent an empty answer (finish reason: ' . ($data['choices'][0]['finish_reason'] ?? 'unknown') . ').');
    }
    return $text;
}

/**
 * Asks the services in order until one answers. $accept may reject an answer (for example, not
 * valid JSON), and then the next service is asked too.
 * Returns ['text', 'service', 'model', 'failed' => [service => error]]; throws when none worked.
 */
function ai_chat(array $messages, int $maxTokens = 2500, float $temperature = 0.6, ?callable $accept = null): array
{
    $failed = [];
    $usable = array_values(array_filter(ai_providers(), static fn ($p) => !empty($p['enabled']) && ($p['key'] ?? '') !== ''));
    if (!$usable) {
        throw new RuntimeException('No AI service is set up yet. Add one in Admin > AI SEO agent.');
    }
    // Services resting after "too many requests" go last, not out.
    usort($usable, static fn ($a, $b) => ((int) (($a['rest_until'] ?? 0) > time())) <=> ((int) (($b['rest_until'] ?? 0) > time())));
    foreach ($usable as $p) {
        $started = microtime(true);
        try {
            $text = ai_call($p, $messages, $maxTokens, $temperature);
            if ($accept && !$accept($text)) {
                throw new RuntimeException('The answer was not in the right format.');
            }
            ai_note_result($p['id'], true, 'Worked', (int) ((microtime(true) - $started) * 1000));
            return ['text' => $text, 'service' => $p['name'], 'model' => $p['model'], 'failed' => $failed];
        } catch (Throwable $e) {
            ai_note_result($p['id'], false, $e->getMessage(), (int) ((microtime(true) - $started) * 1000), $e->getCode() === 429);
            $failed[$p['name']] = $e->getMessage();
        }
    }
    throw new RuntimeException('Every AI service failed. ' . implode(' | ', array_map(static fn ($n, $m) => $n . ': ' . $m, array_keys($failed), $failed)));
}

/** The models a service offers (for the model list on the AI page). */
function ai_models(array $p): array
{
    [$status, $response] = http_request('GET', rtrim((string) $p['url'], '/') . '/models', ['Authorization: Bearer ' . secret_decrypt((string) ($p['key'] ?? ''))], null, 20);
    if ($status !== 200) {
        return [];
    }
    $ids = array_map(static fn ($m) => preg_replace('#^models/#', '', (string) ($m['id'] ?? '')), (array) (json_decode($response, true)['data'] ?? []));
    $ids = array_values(array_unique(array_filter($ids)));
    sort($ids);
    return $ids;
}

/** The Test button: lists the models and asks for a one-word answer. */
function ai_test(array $p): array
{
    $models = ai_models($p);
    $started = microtime(true);
    try {
        $answer = ai_call($p, [['role' => 'user', 'content' => 'Reply with the single word OK.']], 20, 0, 30);
        $ms = (int) ((microtime(true) - $started) * 1000);
        ai_note_result($p['id'], true, 'Test passed', $ms);
        return ['ok' => true, 'message' => 'Working. It answered "' . str_limit($answer, 30) . '" in ' . number_format($ms / 1000, 1) . ' seconds.', 'models' => $models];
    } catch (Throwable $e) {
        ai_note_result($p['id'], false, $e->getMessage(), (int) ((microtime(true) - $started) * 1000), $e->getCode() === 429);
        $tip = $models && !in_array($p['model'], $models, true) ? ' The model "' . $p['model'] . '" is not in this service\'s list: choose one from the list.' : '';
        return ['ok' => false, 'message' => $e->getMessage() . $tip, 'models' => $models];
    }
}

/** The first {...} object in an answer (models sometimes add ```json fences or a sentence). */
function ai_json(string $text): ?array
{
    $text = trim((string) preg_replace('/^```(?:json)?|```$/m', '', $text));
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end < $start) {
        return null;
    }
    $data = json_decode(substr($text, $start, $end - $start + 1), true);
    return is_array($data) ? $data : null;
}

// ---------------------------------------------------------------------------
// What the AI knows about the shop
// ---------------------------------------------------------------------------

/** Facts read from the website every time, so they are never out of date. */
function ai_shop_facts(): string
{
    $lines = [
        'Shop name: ' . setting('store_name') . ' (short name: ' . setting('store_short_name') . ')',
        'Website: ' . url(),
        'Baking in Canberra since: ' . setting('store_founded'),
        'All cakes are made with 100% Halal ingredients.',
    ];
    foreach (active_branches() as $shop) {
        $lines[] = 'Shop: ' . $shop['name'] . ', ' . branch_address_line($shop) . ', phone ' . branch_phone($shop);
    }
    $lines[] = 'Opening hours: ' . implode('; ', array_map(static fn ($g) => $g['days'] . ' ' . $g['hours'], opening_hours_grouped()));
    $lines[] = 'Order online at least ' . notice_label((int) setting('min_lead_hours', '24')) . ' ahead; custom cakes need ' . setting('custom_lead_days', '7') . ' days.';
    $lines[] = setting_bool('delivery_enabled')
        ? 'Pickup from the shop, or delivery in Canberra and Queanbeyan' . (setting('delivery_fee') !== '' ? ' (fee ' . money(setting('delivery_fee')) . ')' : '') . '.'
        : 'Pickup from the shop only.';
    if (setting('google_rating') !== '') {
        $lines[] = 'Google rating: ' . setting('google_rating') . '/5';
    }
    foreach (['hero_title', 'hero_subtitle', 'seo_home_description'] as $key) {
        if (trim(setting($key)) !== '') {
            $lines[] = 'Website text: ' . trim(strip_tags(setting($key)));
        }
    }
    $lines[] = 'Cake categories: ' . implode(', ', array_column(get_categories(), 'name'));
    foreach (array_slice(get_faqs(), 0, 10) as $faq) {
        $lines[] = 'FAQ: ' . $faq['question'] . ' ' . str_limit(content_text((string) $faq['answer']), 260);
    }
    return implode("\n", $lines);
}

/** The SEO rules every answer follows. */
function ai_seo_rules(): string
{
    $short = setting('store_short_name');
    return <<<TXT
You are the senior SEO copywriter of {$short}, a cake shop in Canberra, Australia. You write cake pages that rank on Google for local searches, read well in AI answers (ChatGPT, Gemini, Perplexity) and turn visitors into orders.

Rules:
- Australian English (flavour, colour, personalise, favourite). Warm, clear and specific. No hype words such as "delve", "elevate", "indulge", "unleash", "tantalising", "symphony" or "look no further".
- Only use facts from SHOP PROFILE, SHOP FACTS, CAKE DETAILS and NOTES. Never invent ingredients, allergens, awards, prices, sizes, delivery areas, discounts or reviews. When a detail is unknown, leave it out. Do not write prices: they change.
- focus_keyword: what a Canberra customer types into Google for this cake. 2 to 5 words, lowercase, built from the words of the cake's web address plus "canberra" (for example "red velvet cake canberra").
- meta_title: 45 to 60 characters. Start with the focus keyword in Title Case, then " | {$short}" when it fits.
- meta_description: 130 to 155 characters. Use the focus keyword once, one real selling point (taste, sizes or servings, 100% Halal, made fresh) and a call to action such as "Order online for pickup or delivery."
- short_description: one appetising sentence of 80 to 160 characters. Plain text.
- description: HTML using only <p>, <h3>, <ul>, <li>, <strong> and <a>. 200 to 350 words. Use the focus keyword in the first two sentences and once more later, never stuffed. Sections: what the cake is (taste, texture, layers, finish), sizes and servings (only the given ones), an <h3> "Perfect for" list of occasions, and an <h3> about pickup and delivery in Canberra. Add at most two links, only to addresses listed under LINKS, with natural link text. You may use these placeholders, which the website fills in: {store_phone}, {store_address}, {min_lead_hours} (as "{min_lead_hours} hours").
- search_words: 6 to 10 lowercase phrases people search for, comma separated: other names, flavours, occasions and "canberra" variants (for example "chocolate birthday cake canberra").
- image_alt: 8 to 15 words describing what the main photo of this cake most likely shows. Do not start with "image of" or "photo of".

Answer with one JSON object only, no other text, with exactly these keys: focus_keyword, meta_title, meta_description, short_description, description, search_words, image_alt.
TXT;
}

/** Pages the AI may link to from a cake description: its category, a few cakes beside it, custom cakes, contact. */
function ai_product_links(?int $categoryId, ?int $productId): array
{
    $links = [];
    foreach (get_categories() as $cat) {
        if ((int) $cat['id'] === (int) $categoryId) {
            $links[category_url($cat)] = $cat['name'];
        }
    }
    foreach (array_slice(array_filter($categoryId ? get_products($categoryId) : [], static fn ($p) => (int) $p['id'] !== (int) $productId), 0, 5) as $p) {
        $links[product_url($p)] = $p['name'];
    }
    $links[url('custom-cakes')] = 'Custom cakes';
    $links[url('contact')] = 'Contact us';
    return $links;
}

/** Two of our own cake descriptions (the longest ones nearby) so the AI writes in the shop's style. */
function ai_style_examples(?int $categoryId, ?int $productId): string
{
    $rows = db_all('SELECT name, description FROM products WHERE is_active = 1 AND id <> ? AND description IS NOT NULL ORDER BY (category_id = ?) DESC, CHAR_LENGTH(description) DESC LIMIT 2', [(int) $productId, (int) $categoryId]);
    return implode("\n\n", array_map(static fn ($r) => $r['name'] . ': ' . str_limit(content_text((string) $r['description']), 700), $rows));
}

/** Cuts text at a word (or sentence) so it fits in $max characters. */
function ai_cut(string $text, int $max): string
{
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $part = mb_substr($text, 0, $max + 1);
    $sentence = mb_strrpos($part, '. ');
    if ($sentence !== false && $sentence > $max * 0.6) {
        return mb_substr($part, 0, $sentence + 1);
    }
    return rtrim(mb_substr($part, 0, (int) mb_strrpos($part, ' ')), ' ,;:-');
}

/** Keeps only links to the given addresses (the AI may not invent pages); other links become plain text. */
function ai_keep_links(string $html, array $allowed): string
{
    return (string) preg_replace_callback('#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is', static function ($m) use ($allowed) {
        $href = html_entity_decode($m[2], ENT_QUOTES);
        return in_array($href, $allowed, true) ? '<a href="' . e($href) . '">' . $m[3] . '</a>' : $m[3];
    }, $html);
}

/** Tidies what the AI wrote so it fits the fields and the checklist. */
function ai_clean_fields(array $raw, array $links): array
{
    $text = static fn ($key): string => trim(strip_tags(is_array($raw[$key] ?? null) ? implode(', ', $raw[$key]) : (string) ($raw[$key] ?? '')));
    $title = $text('meta_title');
    while (mb_strlen($title) > 60 && str_contains($title, ' | ')) {
        $title = mb_substr($title, 0, (int) mb_strrpos($title, ' | '));
    }
    $html = (string) ($raw['description'] ?? '');
    // Only the website's own placeholders survive; anything else in {braces} is dropped.
    $html = (string) preg_replace_callback('/\{[a-z_:\-]+\}/i', static fn ($m) => in_array($m[0], AI_PLACEHOLDERS, true) ? $m[0] : '', $html);
    $html = clean_html(ai_keep_links(str_limit($html, 20000), array_keys($links)));
    return [
        'focus_keyword'     => str_limit(trim(mb_strtolower($text('focus_keyword')), " \"'."), 80),
        'meta_title'        => ai_cut($title, 60),
        'meta_description'  => ai_cut($text('meta_description'), 160),
        'short_description' => ai_cut($text('short_description'), 255),
        'description'       => $html,
        'search_words'      => clean_search_words($text('search_words')),
        'image_alt'         => ai_cut($text('image_alt'), 160),
    ];
}

/**
 * Writes the SEO fields for a cake. $p holds what is known (name, slug, category_id,
 * short_description, description, focus_keyword, is_enquiry, id); $sizes rows with label and
 * servings; $options names; $notes what the owner typed for the AI.
 * Returns the fields plus 'service' (which AI wrote it) and 'failed' (services that did not).
 */
function ai_write_product(array $p, array $sizes, array $options, string $notes = ''): array
{
    $name = trim((string) ($p['name'] ?? ''));
    if ($name === '') {
        throw new RuntimeException('Type the cake name first.');
    }
    $categoryId = (int) ($p['category_id'] ?? 0) ?: null;
    $category = $categoryId ? (string) db_value('SELECT name FROM categories WHERE id = ?', [$categoryId]) : '';
    $links = ai_product_links($categoryId, (int) ($p['id'] ?? 0) ?: null);
    $sizeLines = array_map(static fn ($s) => '- ' . trim((string) $s['label']) . (trim((string) ($s['servings'] ?? '')) !== '' ? ' (' . trim((string) $s['servings']) . ')' : ''), $sizes);

    $details = implode("\n", array_filter([
        'Name: ' . $name,
        'Web address: ' . url('cakes/' . (($p['slug'] ?? '') ?: slugify($name))),
        $category !== '' ? 'Category: ' . $category : null,
        !empty($p['is_enquiry']) ? 'Price on request: customers ask for a quote; no fixed sizes.' : null,
        $sizeLines ? "Sizes:\n" . implode("\n", $sizeLines) : null,
        $options ? 'Options customers can add: ' . implode(', ', $options) : null,
        trim((string) ($p['focus_keyword'] ?? '')) !== '' ? 'Current focus keyword: ' . $p['focus_keyword'] : null,
        trim((string) ($p['short_description'] ?? '')) !== '' ? 'Current short description: ' . $p['short_description'] : null,
        trim((string) ($p['description'] ?? '')) !== '' ? 'Current description (keep its facts, improve the writing): ' . str_limit(content_text((string) $p['description']), 2500) : null,
    ]));
    $profile = trim(setting('ai_shop_profile'));
    $user = "SHOP PROFILE (written by the owner):\n" . ($profile !== '' ? $profile : '(none yet)')
        . "\n\nSHOP FACTS (from the website):\n" . ai_shop_facts()
        . "\n\nSTYLE EXAMPLES (our own pages):\n" . (ai_style_examples($categoryId, (int) ($p['id'] ?? 0) ?: null) ?: '(none)')
        . "\n\nLINKS:\n" . implode("\n", array_map(static fn ($u, $l) => '- ' . $u . ' (' . $l . ')', array_keys($links), $links))
        . "\n\nCAKE DETAILS:\n" . $details
        . "\n\nNOTES FROM THE SHOP ABOUT THIS CAKE:\n" . (trim($notes) !== '' ? trim($notes) : '(none)');

    $answer = ai_chat([['role' => 'system', 'content' => ai_seo_rules()], ['role' => 'user', 'content' => $user]], 2500, 0.6,
        static fn ($text) => ($d = ai_json($text)) !== null && trim((string) ($d['description'] ?? '')) !== '' && trim((string) ($d['meta_title'] ?? '')) !== '');
    return ai_clean_fields(ai_json($answer['text']), $links) + ['service' => $answer['service'] . ' (' . $answer['model'] . ')', 'failed' => $answer['failed']];
}

/**
 * Writes and saves the SEO fields of a saved cake (the "many cakes" button). The text it replaces
 * is kept in the version history, so it can be restored from the product page.
 * Returns the new fields and score.
 */
function ai_rewrite_product(int $id, string $who): array
{
    $product = db_one('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$product) {
        throw new RuntimeException('That cake was not found.');
    }
    $sizes = get_product_sizes($id);
    $options = array_column(db_all('SELECT name FROM product_options WHERE product_id = ? AND is_active = 1 ORDER BY sort_order', [$id]), 'name');
    $fields = array_intersect_key(ai_write_product($product, $sizes, $options), array_flip(AI_PRODUCT_FIELDS));
    if (!$product['image']) {
        $fields['image_alt'] = $product['image_alt'];
    }
    seo_history_save($id, $product, $fields, 'ai', $who);
    db_run('UPDATE products SET ' . implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($fields))) . ' WHERE id = ?', [...array_values($fields), $id]);
    $saved = db_one('SELECT * FROM products WHERE id = ?', [$id]);
    [$passed, $total, $level] = seo_score(product_seo_checks($saved, (int) db_value('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$id]), $sizes));
    return ['score' => $passed . '/' . $total, 'level' => $level, 'title' => $saved['meta_title']];
}

/** A first draft of the shop profile, written by the AI from the website's own text. */
function ai_profile_draft(): string
{
    $answer = ai_chat([
        ['role' => 'system', 'content' => 'You help a cake shop describe itself for its SEO writer. Australian English. Only use the facts given. Plain text, no markdown headings.'],
        ['role' => 'user', 'content' => "From these website facts, write a shop profile of 120 to 220 words for our SEO writer: who we are, where we are and which suburbs we serve, what we are known for, our kinds of cakes, how ordering, pickup and delivery work, and the tone to write in. Then a line \"Always mention:\" with 3 to 5 short points.\n\n" . ai_shop_facts()],
    ], 900, 0.5);
    return trim($answer['text']);
}
