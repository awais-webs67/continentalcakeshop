<?php
declare(strict_types=1);

// Keyword research: what people really type into Google, taken from Google's search suggestions
// (the autocomplete list, most searched first). Google does not share search numbers, but the
// order of its suggestions follows how often people search them. Used by the AI writer and by
// "Keyword ideas" in the product editor. Answers are cached for 14 days in storage/cache.

/** Searches that are not people looking to buy a cake from us: other cities, other shops, recipes. */
const KEYWORD_OTHER_PLACES = ['sydney', 'melbourne', 'brisbane', 'perth', 'adelaide', 'hobart', 'darwin', 'gold coast', 'newcastle', 'wollongong', 'geelong', 'sunshine coast', 'cairns', 'townsville', 'goulburn', 'auckland', 'nz', 'uk', 'usa', 'india', 'pakistan', 'dubai', 'singapore', 'malaysia'];
const KEYWORD_SKIP = ['recipe', 'recipes', 'how to', 'how do', 'what is', "what's", 'ideas', 'images', 'image', 'drawing', 'clipart', 'png', 'calories', 'nutrition', 'history', 'meaning', 'origin', 'song', 'emoji', 'tin', 'pan', 'mix', 'easy', 'homemade', 'woolworths', 'coles', 'costco', 'aldi', 'iga', 'kmart', 'cheesecake shop', 'michel', 'bakers delight', 'cheesecake factory', 'youtube', 'reddit', 'pinterest', 'tiktok', 'vs', 'recept', 'receta', 'rezept', 'con', 'jam', 'no bake', 'how', 'why', 'does', 'do', 'can', 'should', 'is', 'are', 'without'];

/** Words that make a search local to the shop. */
const KEYWORD_LOCAL = '/\b(canberra|near me|act|queanbeyan|tuggeranong|belconnen|gungahlin|woden|weston creek|greenway|civic|molonglo)\b/i';

/** Google's suggestions for one search (Australian results), cached. */
function keyword_suggestions(string $query): array
{
    $query = mb_strtolower(trim(preg_replace('/\s+/', ' ', $query)));
    if ($query === '') {
        return [];
    }
    $dir = STORAGE_PATH . '/cache/keywords';
    $file = $dir . '/' . md5($query) . '.json';
    if (is_file($file) && filemtime($file) > time() - 14 * 86400) {
        $cached = json_decode((string) file_get_contents($file), true);
        if (is_array($cached)) {
            return $cached;
        }
    }
    [$status, $body] = http_request('GET', 'https://suggestqueries.google.com/complete/search?' . http_build_query([
        'client' => 'firefox', 'hl' => 'en', 'gl' => 'au', 'ie' => 'utf-8', 'oe' => 'utf-8', 'q' => $query,
    ]), ['User-Agent: Mozilla/5.0'], null, 8);
    $data = $status === 200 ? json_decode($body, true) : null;
    if (!is_array($data) || !is_array($data[1] ?? null)) {
        return [];   // not cached: tried again next time
    }
    $list = array_values(array_filter(array_map(static fn ($s) => mb_strtolower(trim((string) $s)), $data[1])));
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($file, json_encode($list));
    return $list;
}

/** True when a suggestion is someone looking for this kind of cake (not a recipe, another city or another shop). */
function keyword_is_useful(string $phrase, array $words): bool
{
    $padded = ' ' . $phrase . ' ';
    foreach ([...KEYWORD_SKIP, ...KEYWORD_OTHER_PLACES] as $bad) {
        if (str_contains($padded, ' ' . $bad . ' ')) {
            return false;
        }
    }
    foreach ($words as $word) {
        if (str_contains($padded, ' ' . $word . ' ') || str_contains($padded, ' ' . $word . 's ')) {
            return true;
        }
    }
    return false;
}

/**
 * Real searches for a cake: its name, with "canberra" and "near me", and its category with
 * "canberra". Most searched and local first. Returns [['phrase' => ..., 'local' => bool], ...].
 */
function keyword_ideas(string $name, string $category = '', int $limit = 12): array
{
    $name = mb_strtolower(trim($name));
    $category = mb_strtolower(trim($category));
    if ($name === '') {
        return [];
    }
    $stop = ['the', 'and', 'with', 'for', 'our', 'cake', 'cakes', 'of', 'a', 'in'];
    $words = array_values(array_filter(preg_split('/[^a-z0-9]+/', $name . ' ' . $category), static fn ($w) => strlen($w) > 2 && !in_array($w, $stop, true)));
    $base = preg_match('/\b(cake|cakes|torte|tortes|cheesecake|cheesecakes|cupcakes?|slices?|desserts?)\b/', $name) ? $name : $name . ' cake';
    $seeds = array_values(array_unique(array_filter([$base . ' canberra', $base, $base . ' near me', $category !== '' ? $category . ' canberra' : ''])));

    $scores = [];
    foreach ($seeds as $s => $seed) {
        foreach (keyword_suggestions($seed) as $position => $phrase) {
            if (!keyword_is_useful($phrase, $words) || mb_strlen($phrase) > 70) {
                continue;
            }
            $score = 100 - $position * 6 - $s * 4 + (preg_match(KEYWORD_LOCAL, $phrase) ? 15 : 0);
            $scores[$phrase] = max($scores[$phrase] ?? 0, $score);
        }
    }
    arsort($scores);
    $ideas = [];
    foreach (array_slice(array_keys($scores), 0, $limit) as $phrase) {
        $ideas[] = ['phrase' => (string) $phrase, 'local' => (bool) preg_match(KEYWORD_LOCAL, (string) $phrase)];
    }
    return $ideas;
}
