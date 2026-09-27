<?php
/**
 * Eduma layout for Akdemy: slim top heading, full-width course grid, Akdemy primary menu.
 * Run: powershell -File scripts\wp-eduma.ps1 eval-file wordpress\setup\eduma-layout.php
 */
$mods = [
    // Slim banner (Eduma default 90/90px)
    'thim_top_heading_padding'         => ['top' => '48px', 'bottom' => '48px'],
    // Course archive: no sidebar, grid of 3
    'thim_learnpress_cate_layout'      => 'full-content',
    'thim_learnpress_cate_layout_grid' => 'grid_courses',
    'thim_learnpress_cate_grid_column' => '3',
    // Header menu colours — white over the dark banner, ink on the sticky white bar
    'thim_main_menu_text_color'              => '#FFFFFF',
    'thim_main_menu_text_hover_color'        => '#F5A011',
    'thim_sticky_main_menu_text_color'       => '#1a1a1a',
    'thim_sticky_main_menu_text_hover_color' => '#F5A011',
];
foreach ($mods as $k => $v) {
    set_theme_mod($k, $v);
}

// Classic menu (Eduma uses the "primary" location, not block navigation)
$name = 'Akdemy main';
$menu = wp_get_nav_menu_object($name);
$menu_id = $menu ? $menu->term_id : wp_create_nav_menu($name);
foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
    wp_delete_post($item->ID, true); // rebuild idempotently (menu items only)
}
$courses = get_permalink((int) get_option('learn_press_courses_page_id'));
$items = [
    ['Home', home_url('/'), false],
    ['Courses', $courses, false],
    ['Insights', home_url('/insights/'), false],
    ['Kpex Platform', 'https://caf-corporation.com/', true],
    ['Who We Are', 'https://caf-corporation.com/services', true],
];
foreach ($items as $i => [$title, $url, $external]) {
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'    => $title,
        'menu-item-url'      => $url,
        'menu-item-status'   => 'publish',
        'menu-item-position' => $i + 1,
        'menu-item-target'   => $external ? '_blank' : '',
    ]);
}
$locations = get_theme_mod('nav_menu_locations', []);
$locations['primary'] = $menu_id;
set_theme_mod('nav_menu_locations', $locations);

echo "layout mods set; menu #$menu_id with " . count($items) . " items → primary\n";
