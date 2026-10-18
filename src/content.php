<?php
declare(strict_types=1);

// Editable content: policy pages and FAQs. Their HTML is written by the shop in the
// admin panel, and tokens like {store_phone} keep them in step with the settings.

const POLICY_PAGES = ['privacy-policy', 'terms', 'refund-policy', 'allergens'];

function get_page(string $slug): ?array
{
    return db_one('SELECT * FROM pages WHERE slug = ? AND is_active = 1', [$slug]);
}

function get_faqs(): array
{
    return db_all('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id');
}

/** Fills {token} placeholders in stored content with current settings. */
function render_content(string $html): string
{
    $tokens = [
        '{store_name}'       => e(setting('store_name')),
        '{store_phone}'      => '<a href="tel:' . e(setting('store_phone_e164')) . '">' . e(setting('store_phone')) . '</a>',
        '{store_email}'      => '<a href="mailto:' . e(setting('store_email')) . '">' . e(setting('store_email')) . '</a>',
        '{store_address}'    => e(store_address_line()),
        '{min_lead_hours}'   => e(setting('min_lead_hours', '24')),
        '{custom_lead_days}' => e(setting('custom_lead_days', '3')),
    ];
    $html = strtr($html, $tokens);
    return (string) preg_replace_callback('/\{url:([a-z0-9-]+)\}/', static fn ($m) => e(url($m[1])), $html);
}

/** Plain-text version of stored content, used for FAQ structured data. */
function content_text(string $html): string
{
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(render_content($html)), ENT_QUOTES, 'UTF-8')));
}
