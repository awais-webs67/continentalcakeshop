<?php
declare(strict_types=1);

// Inline SVG icons (Lucide, ISC licence). Inline icons need no extra requests and inherit text colour.

const ICON_PATHS = [
    'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'menu'     => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
    'close'    => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'bag'      => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    'pin'      => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'mail'     => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'chevron'  => '<path d="m9 18 6-6-6-6"/>',
    'down'     => '<path d="m6 9 6 6 6-6"/>',
    'check'    => '<path d="M20 6 9 17l-5-5"/>',
    'star'     => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',
    'halal'    => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/>',
    'leaf'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
    'pen'      => '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>',
    'store'    => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/>',
    'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
    'card'     => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
    'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
    'cake'     => '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v3"/><path d="M12 8v3"/><path d="M17 8v3"/><path d="M7 4h.01"/><path d="M12 4h.01"/><path d="M17 4h.01"/>',
    'trash'    => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
    'minus'    => '<path d="M5 12h14"/>',
    'plus'     => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
    'instagram'=> '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/>',
    'pinterest'=> '<path d="M8 20c1-3 1.5-5 2-7"/><path d="M10.7 14c.4 1 1.4 1.5 2.5 1.5 2.5 0 4.3-2.5 4.3-5.5A5.5 5.5 0 0 0 12 4.5C8.5 4.5 6.5 7 6.5 9.5c0 1.2.5 2.5 1.5 3"/>',
    'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    'chart'    => '<path d="M3 3v18h18"/><path d="M7 16v-5"/><path d="M12 16V8"/><path d="M17 16v-3"/>',
    'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    'tag'      => '<path d="M12.59 2.59A2 2 0 0 0 11.17 2H4a2 2 0 0 0-2 2v7.17a2 2 0 0 0 .59 1.42l8.7 8.7a2.43 2.43 0 0 0 3.42 0l6.58-6.58a2.43 2.43 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
    'megaphone' => '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
    'copy'     => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    'truck'    => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
    'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C9.5 3 11 5 12 8c1-3 2.5-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
];

function icon(string $name, string $class = '', int $size = 20): string
{
    $paths = ICON_PATHS[$name] ?? '';
    $fill = $name === 'star' ? 'currentColor' : 'none';
    return sprintf(
        '<svg class="icon %s" width="%d" height="%d" viewBox="0 0 24 24" fill="%s" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
        e($class), $size, $size, $fill, $paths
    );
}

/**
 * Wavy "icing drip" edge. Hangs downward from the top of its box and uses currentColor,
 * so the same shape works under the header and above the footer.
 */
function drip_svg(string $class = '', int $seed = 7): string
{
    $width = 1440;
    $base = 6;
    $state = $seed;
    $rand = static function () use (&$state): float {
        $state = ($state * 9301 + 49297) % 233280;
        return $state / 233280;
    };

    $segments = [];
    for ($x = 0; $x < $width;) {
        if ($rand() < 0.3) {
            $w = 20 + $rand() * 12;
            $depth = 22 + $rand() * 16;
        } else {
            $w = 48 + $rand() * 70;
            $depth = 5 + $rand() * 12;
        }
        $w = min($w, $width - $x);
        $segments[] = [$x, $w, $depth];
        $x += $w;
    }

    $d = "M0 0H{$width}V{$base}";
    foreach (array_reverse($segments) as [$x, $w, $depth]) {
        $y = $base + $depth;
        $d .= sprintf(
            'C%.1f %d %.1f %.1f %.1f %.1fC%.1f %.1f %.1f %d %.1f %d',
            $x + $w * 0.8, $base, $x + $w * 0.9, $y, $x + $w / 2, $y,
            $x + $w * 0.1, $y, $x + $w * 0.2, $base, $x, $base
        );
    }
    $d .= 'Z';

    return '<svg class="drip ' . e($class) . '" viewBox="0 0 ' . $width . ' 46" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . $d . '"/></svg>';
}

function google_logo(int $size = 20): string
{
    return '<svg class="google-logo" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
        . '<path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.7-.4-3.9z"/>'
        . '<path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>'
        . '<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2A12 12 0 0 1 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>'
        . '<path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3a12 12 0 0 1-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.7-.4-3.9z"/></svg>';
}

/** Five stars with the fill trimmed to the rating, e.g. 4.3 shows four and a third stars. */
function rating_stars(float $rating, int $size = 18): string
{
    $percent = max(0, min(100, $rating / 5 * 100));
    $stars = str_repeat(icon('star', '', $size), 5);
    return '<span class="rating-stars" role="img" aria-label="Rated ' . e(number_format($rating, 1)) . ' out of 5">'
        . '<span class="rating-stars__base">' . $stars . '</span>'
        . '<span class="rating-stars__fill" style="width:' . round($percent, 1) . '%">' . $stars . '</span></span>';
}

/** Stable soft colour for a reviewer's initial. */
function avatar_colour(string $name): string
{
    $colours = ['#1A8670', '#2F6F5E', '#5B4BB7', '#C46A1B', '#1F6FA8', '#8E3E86'];
    return $colours[crc32($name) % count($colours)];
}
