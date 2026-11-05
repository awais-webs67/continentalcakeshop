<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Main menu (Admin > Menu)
// ---------------------------------------------------------------------------
// The menu is saved as JSON in the nav_menu setting, so no database table is needed.
// Each item: label, link, new_tab, children (one level of sub items).
// A link is a site path ("/", "/shop", "/shop/tortes"), a full https:// address,
// "" (no link: the item only opens its sub menu) or "@categories" (every cake
// category, kept up to date automatically, plus "View all cakes").

const MENU_MAX_TOP = 8;
const MENU_MAX_CHILDREN = 15;
const MENU_LABEL_MAX = 40;
const MENU_AUTO_CATEGORIES = '@categories';

function default_nav_menu(): array
{
    return [
        ['label' => 'Home', 'link' => '/', 'new_tab' => false, 'children' => []],
        ['label' => 'Our Range', 'link' => '/shop', 'new_tab' => false, 'children' => [
            ['label' => 'All cake categories', 'link' => MENU_AUTO_CATEGORIES, 'new_tab' => false],
        ]],
        ['label' => 'Custom Cakes', 'link' => '/custom-cakes', 'new_tab' => false, 'children' => [
            ['label' => 'Order a Custom Cake', 'link' => '/custom-cakes', 'new_tab' => false],
            ['label' => 'Our Custom Cake Gallery', 'link' => '/custom-cake-gallery', 'new_tab' => false],
        ]],
        ['label' => 'About Us', 'link' => '/about', 'new_tab' => false, 'children' => []],
        ['label' => 'Contact', 'link' => '/contact', 'new_tab' => false, 'children' => []],
    ];
}

/** The menu as saved by the admin (or the default one). Not yet turned into web addresses. */
function nav_menu_saved(): array
{
    $saved = json_decode(setting('nav_menu'), true);
    return is_array($saved) && $saved ? $saved : default_nav_menu();
}

/** Cleans one link typed or picked in the admin. Returns null when it cannot be used. */
function menu_clean_link(string $link): ?string
{
    $link = trim($link);
    if ($link === '' || $link === MENU_AUTO_CATEGORIES) {
        return $link;
    }
    if (preg_match('#^https?://#i', $link)) {
        return safe_web_url($link) ? $link : null;
    }
    if (preg_match('#^(mailto:|tel:)[^\s"<>]+$#i', $link)) {
        return $link;
    }
    // A site path such as /about or shop/tortes or /faq#delivery. Nothing that could be a script.
    $link = '/' . ltrim($link, '/');
    return preg_match('#^/[A-Za-z0-9/_\-.?=&%\#]*$#', $link) && !str_starts_with($link, '//') ? $link : null;
}

/**
 * Cleans the menu posted by the admin form: rows in order, each with a level (0 top, 1 sub item).
 * A sub item belongs to the nearest top item above it. Returns [menu, errors].
 */
function menu_from_rows(array $rows): array
{
    $menu = [];
    $errors = [];
    foreach ($rows as $row) {
        $label = str_limit(trim((string) ($row['label'] ?? '')), MENU_LABEL_MAX);
        $rawLink = (string) ($row['link'] ?? '');
        if ($rawLink === '__custom') {
            $rawLink = (string) ($row['url'] ?? '');
        }
        $link = menu_clean_link($rawLink);
        $isChild = ($row['level'] ?? '0') === '1' && $menu;
        if ($label === '' && $link === MENU_AUTO_CATEGORIES) {
            $label = 'All cake categories';
        }
        if ($label === '') {
            continue; // An empty row is how the admin removes an item.
        }
        if ($link === null) {
            $errors[] = '"' . $label . '": that link cannot be used. Pick a page, or type a full address starting with https://';
            continue;
        }
        if ($link === MENU_AUTO_CATEGORIES && !$isChild) {
            $errors[] = '"' . $label . '": the automatic category list can only be a sub menu item. Tick "Sub item" for it.';
            continue;
        }
        $item = ['label' => $label, 'link' => $link, 'new_tab' => !empty($row['new_tab'])];
        if ($isChild) {
            $parent = count($menu) - 1;
            if (count($menu[$parent]['children']) >= MENU_MAX_CHILDREN) {
                $errors[] = '"' . $menu[$parent]['label'] . '" can have up to ' . MENU_MAX_CHILDREN . ' sub items.';
                continue;
            }
            $menu[$parent]['children'][] = $item;
        } else {
            if (count($menu) >= MENU_MAX_TOP) {
                $errors[] = 'The top menu can have up to ' . MENU_MAX_TOP . ' items, so it fits on one line. Make the others sub items.';
                continue;
            }
            $menu[] = $item + ['children' => []];
        }
    }
    foreach ($menu as $item) {
        if ($item['link'] === '' && !$item['children']) {
            $errors[] = '"' . $item['label'] . '" has no link and no sub items, so it would do nothing. Give it a link or add sub items under it.';
        }
    }
    if (!$menu) {
        $errors[] = 'The menu needs at least one item.';
    }
    return [$menu, $errors];
}

/** A stored link as a web address. */
function menu_href(string $link): string
{
    if ($link === '' || preg_match('#^(https?://|mailto:|tel:)#i', $link)) {
        return $link;
    }
    return $link === '/' ? url() : url(ltrim($link, '/'));
}

/** True when the visitor is on the page this link points to. */
function menu_link_active(string $link): bool
{
    if ($link === '' || $link[0] !== '/') {
        return false;
    }
    $path = (string) parse_url($link, PHP_URL_PATH);
    if ($path === '/') {
        return current_path() === '/';
    }
    $current = current_path();
    if ($path === '/shop' && str_starts_with($current, '/cakes/')) {
        return true; // A cake page sits under "all cakes".
    }
    return $current === rtrim($path, '/') || str_starts_with($current, rtrim($path, '/') . '/');
}

/**
 * The menu ready to show: web addresses filled in, the automatic category list expanded,
 * and links to categories that are hidden or have no cakes left out.
 */
function nav_menu(): array
{
    static $built = null;
    if ($built !== null) {
        return $built;
    }
    $categories = get_categories();
    $liveCategories = array_column($categories, 'slug');
    $build = static function (array $item) use ($liveCategories): ?array {
        $link = menu_clean_link((string) ($item['link'] ?? ''));
        if ($link === null) {
            return null;
        }
        if (preg_match('#^/shop/([a-z0-9-]+)$#', $link, $m) && !in_array($m[1], $liveCategories, true)) {
            return null;
        }
        return [
            'label'   => (string) ($item['label'] ?? ''),
            'link'    => $link,
            'href'    => menu_href($link),
            'new_tab' => !empty($item['new_tab']) || preg_match('#^https?://#i', $link) && !str_starts_with($link, rtrim(url(), '/')),
            'active'  => menu_link_active($link),
            'all'     => false,
        ];
    };

    $built = [];
    foreach (nav_menu_saved() as $top) {
        $item = $build($top);
        if (!$item || $item['link'] === MENU_AUTO_CATEGORIES) {
            continue;
        }
        $children = [];
        foreach ((array) ($top['children'] ?? []) as $child) {
            if (($child['link'] ?? '') === MENU_AUTO_CATEGORIES) {
                foreach ($categories as $cat) {
                    $children[] = ['label' => $cat['name'], 'link' => '/shop/' . $cat['slug'], 'href' => category_url($cat), 'new_tab' => false, 'active' => menu_link_active('/shop/' . $cat['slug']), 'all' => false];
                }
                $children[] = ['label' => 'View all cakes', 'link' => '/shop', 'href' => url('shop'), 'new_tab' => false, 'active' => false, 'all' => true];
                continue;
            }
            if ($builtChild = $build($child)) {
                $children[] = $builtChild;
            }
        }
        if ($item['link'] === '' && !$children) {
            continue;
        }
        $item['children'] = $children;
        $item['active'] = $item['active'] || (bool) array_filter($children, static fn ($c) => $c['active']);
        $built[] = $item;
    }
    return $built;
}

/** Choices for the link picker in Admin > Menu, grouped. */
function menu_link_choices(): array
{
    $pages = [
        '/' => 'Home',
        '/shop' => 'All cakes (Our Range)',
        '/custom-cakes' => 'Order a custom cake',
        '/custom-cake-gallery' => 'Custom cake gallery',
        '/about' => 'About us',
        '/contact' => 'Contact',
        '/faq' => 'FAQ',
        '/account' => 'My account',
        '/cart' => 'Cart',
    ];
    $categories = [];
    foreach (db_all('SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name') as $cat) {
        $categories['/shop/' . $cat['slug']] = $cat['name'];
    }
    $more = [];
    try {
        foreach (db_all('SELECT slug, title FROM pages WHERE is_active = 1 ORDER BY type, sort_order, title') as $p) {
            $more['/' . $p['slug']] = $p['title'];
        }
    } catch (PDOException) {
    }
    return [
        'Pages' => $pages,
        'Automatic' => [MENU_AUTO_CATEGORIES => 'All cake categories (automatic)', '' => 'No link (only opens its sub menu)'],
        'Cake categories' => $categories,
        'Your pages (Admin > Pages)' => $more,
    ];
}
