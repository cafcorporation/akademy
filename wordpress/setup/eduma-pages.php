<?php
/**
 * Adapts the Akdemy pages (built for Twenty Twenty-Five) to Eduma.
 *  - Home: no Eduma top heading / breadcrumbs, full width → our full-bleed hero is the header.
 *  - Insights: Eduma slim top heading (title + subtitle) + the insights grid only.
 * Run: powershell -File scripts\wp-eduma.ps1 eval-file wordpress\setup\eduma-pages.php
 */
$home     = get_page_by_path('home');
$insights = get_page_by_path('insights');
if (!$home || !$insights) {
    exit("home/insights pages missing\n");
}

foreach ([
    'thim_mtb_using_custom_heading'    => 1,
    'thim_mtb_hide_title_and_subtitle' => 1,
    'thim_mtb_hide_breadcrumbs'        => 1,
    'thim_mtb_no_padding'              => 1,
    'thim_mtb_custom_layout'           => 1,
    'thim_mtb_layout'                  => 'full-width',   // Eduma "content-wide": no container, no sidebar
] as $k => $v) {
    update_post_meta($home->ID, $k, $v);
}

update_post_meta($insights->ID, 'thim_mtb_using_custom_heading', 1);            // needed for image + subtitle
update_post_meta($insights->ID, 'thim_mtb_top_image', (int) get_theme_mod('thim_learnpress_cate_top_image'));
update_post_meta($insights->ID, 'thim_mtb_custom_layout', 1);
update_post_meta($insights->ID, 'thim_mtb_layout', 'full-content');
update_post_meta($insights->ID, 'thim_subtitle', 'Methodology, benchmarks and cost intelligence from the Kpex team — each article opens on caf-corporation.com.');

wp_update_post([
    'ID'           => $insights->ID,
    'post_content' => "<!-- wp:shortcode -->\n[akdemy_insights limit=\"24\" more=\"yes\"]\n<!-- /wp:shortcode -->",
]);

echo "home #{$home->ID}: heading hidden, full width · insights #{$insights->ID}: Eduma heading + grid\n";
