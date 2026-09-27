<?php
/**
 * CAF Corporation brand on Eduma (same values as caf-corporation.com): Inter, #F5A011 / #585856,
 * Akdemy logos, slim course-archive banner image. Then rebuilds Eduma's compiled options CSS
 * exactly like pressing "Publish" in the Customizer.
 * Run: powershell -File scripts\wp-eduma.ps1 eval-file wordpress\setup\eduma-brand.php
 */
$brand = 'C:/Users/CarlosFuenmayor/Akdemy-AI/brand/';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/** Upload once (keyed by file hash) and return the attachment id. */
function akdemy_media(string $file): int
{
    $hash = md5_file($file);
    $ids  = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1,
                       'fields' => 'ids', 'meta_key' => '_akdemy_media_hash', 'meta_value' => $hash]);
    if ($ids) {
        return (int) $ids[0];
    }
    $tmp = wp_tempnam(basename($file));
    copy($file, $tmp);
    $id = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], 0);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    update_post_meta($id, '_akdemy_media_hash', $hash);
    return (int) $id;
}

$logo_white = akdemy_media($brand . 'Logo CAF Akdemy White-Yellow.png'); // over dark headers
$logo_gray  = akdemy_media($brand . 'Logo CAF Akdemy.png');              // sticky white header
$banner     = akdemy_media($brand . 'hero-course-classroom.png');        // provisional — Carlos sends final

$inter = fn (string $variant, string $size = '', string $color = '', string $lh = '') => array_filter([
    'font-family' => 'Inter', 'variant' => $variant, 'font-size' => $size, 'line-height' => $lh, 'color' => $color,
]);

$mods = [
    // Colours (caf-corporation.com)
    'thim_body_primary_color'   => '#F5A011',
    'thim_body_secondary_color' => '#585856',
    'thim_button_text_color'    => '#FFFFFF',
    'thim_button_hover_color'   => '#d4880a',
    // Typography — Inter everywhere, CAF weights
    'thim_font_body'            => $inter('regular', '15px', '#3a3a38', '1.7em'),
    'thim_font_title'           => $inter('800', '', '#1a1a1a'),
    'thim_font_button'          => $inter('700'),
    'thim_main_menu'            => $inter('600', '14px'),
    'thim_main_menu_font_weight'=> 600,
    // Logos
    'thim_logo'                 => $logo_white,
    'thim_logo_mobile'          => $logo_white,
    'thim_sticky_logo'          => $logo_gray,
    'thim_sticky_logo_mobile'   => $logo_gray,
    'thim_width_logo'           => '170px',
    // Slim course-archive banner ("top heading")
    'thim_learnpress_cate_top_image'  => $banner,
    'thim_learnpress_single_top_image'=> $banner,
    'thim_learnpress_cate_sub_title'  => 'Practical CAPEX estimating courses built on Kpex — AACE- and PMI-aligned, in English and Spanish.',
];
foreach ($mods as $k => $v) {
    set_theme_mod($k, $v);
}
echo 'theme mods set: ' . count($mods) . "\n";

// Rebuild Eduma's compiled CSS (Thim Core's customize_save_after handler).
do_action('customize_save_after', null);
echo 'options stylesheet: ' . (class_exists('Thim_Core_Customizer') ? Thim_Core_Customizer::get_stylesheet_uri() : get_theme_mod('thim_style_options_uri', '?')) . "\n";
