<?php
/**
 * Akdemy brand layer: CAF Corporation colours/typography and the Akdemy logo in the header.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    // akdemy-brand.css is written against Twenty Twenty-Five's markup; Eduma gets its own layer.
    if (get_template() === 'eduma') {
        // Eduma's typography options name the font but don't load it — load Inter like caf-corporation.com.
        wp_enqueue_style('akdemy-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap', [], null);
        foreach (['akdemy-components', 'akdemy-eduma'] as $sheet) {
            $file = __DIR__ . "/../assets/$sheet.css";
            if (is_readable($file)) {
                wp_enqueue_style($sheet, plugins_url("assets/$sheet.css", AKDEMY_BRIDGE_FILE), ['akdemy-inter'], (string) filemtime($file));
            }
        }
        return;
    }
    if (get_template() !== 'twentytwentyfive') {
        return;
    }
    // Same typeface as caf-corporation.com
    wp_enqueue_style(
        'akdemy-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'akdemy-brand',
        plugins_url('assets/akdemy-brand.css', AKDEMY_BRIDGE_FILE),
        ['akdemy-inter'],
        (string) filemtime(__DIR__ . '/../assets/akdemy-brand.css')
    );
}, 100); // after theme + LearnPress styles

// Slug-based body class (akdemy-page-home, akdemy-page-insights…) for page-specific styling.
add_filter('body_class', function ($classes) {
    if (is_page()) {
        $classes[] = 'akdemy-page-' . sanitize_html_class(get_post_field('post_name', get_queried_object_id()));
    }
    return $classes;
});

// Show the Akdemy logo (Settings › site_logo) instead of the plain site title in the header.
add_filter('render_block_core/site-title', function ($html, $block) {
    $logo_id = (int) get_option('site_logo');
    if (!$logo_id || is_admin()) {
        return $html;
    }
    $img = wp_get_attachment_image($logo_id, 'medium', false, [
        'alt'     => get_bloginfo('name'),
        'loading' => 'eager',
    ]);
    return sprintf(
        '<a class="akdemy-header-logo" href="%s" rel="home">%s</a>',
        esc_url(home_url('/')),
        $img
    );
}, 10, 2);
