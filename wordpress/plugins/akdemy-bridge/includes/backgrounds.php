<?php
/**
 * Rotating backgrounds for the dark bands (course band, Eduma page headings, home hero).
 *
 * Pool = assets/backgrounds/*.jpg (built by tools/prepare-backgrounds.ps1 from brand/backgrounds/).
 * - Singular pages (a course, a page): stable per post → each course keeps its image.
 * - Everything else (archives): rotates daily.
 * Exposed as CSS variable --akdemy-band-img; the stylesheets use it under a CAF-dark scrim.
 */

if (!defined('ABSPATH')) {
    exit;
}

function akdemy_backgrounds(): array
{
    $files = glob(__DIR__ . '/../assets/backgrounds/*.jpg') ?: [];
    sort($files);
    return array_map(fn ($f) => plugins_url('assets/backgrounds/' . basename($f), AKDEMY_BRIDGE_FILE), $files);
}

function akdemy_band_image(): ?string
{
    $pool = akdemy_backgrounds();
    if (!$pool) {
        return null;
    }
    // ?akdemy_bg=N (1-based) previews a specific background — handy when reviewing new images.
    if (isset($_GET['akdemy_bg']) && isset($pool[(int) $_GET['akdemy_bg'] - 1])) {
        return $pool[(int) $_GET['akdemy_bg'] - 1];
    }
    $seed = is_singular() ? (int) get_queried_object_id() : (int) gmdate('z');
    return $pool[$seed % count($pool)];
}

add_action('wp_head', function () {
    $img = akdemy_band_image();
    if ($img) {
        printf("<style id=\"akdemy-band-img\">:root{--akdemy-band-img:url('%s');}</style>\n", esc_url($img));
    }
}, 50);
