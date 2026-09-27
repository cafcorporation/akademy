<?php
/**
 * Header menu mirroring caf-corporation.com: links + "Sign In" (ghost) + "Get Started" (primary).
 * Run: powershell -File scripts\wp.ps1 eval-file wordpress\setup\navigation.php
 */
$link = fn (string $label, string $url, string $class = '', bool $external = false) => sprintf(
    '<!-- wp:navigation-link %s /-->',
    wp_json_encode(array_filter([
        'label'         => $label,
        'url'           => $url,
        'kind'          => $external ? 'custom' : 'custom',
        'isTopLevelLink'=> true,
        'opensInNewTab' => $external,
        'className'     => $class,
    ]))
);

$courses = get_permalink((int) get_option('learn_press_courses_page_id'));
$profile = get_permalink((int) get_option('learn_press_profile_page_id'));

$content = implode("\n", [
    $link('Home', home_url('/')),
    $link('Courses', $courses),
    $link('Insights', home_url('/insights/')),
    $link('Kpex Platform', 'https://caf-corporation.com/', 'akdemy-nav-secondary', true),
    $link('Who We Are', 'https://caf-corporation.com/services', 'akdemy-nav-secondary', true),
    $link('Sign In', $profile, 'akdemy-nav-ghost'),
    $link('Get Started', $courses, 'akdemy-nav-primary'),
]);

$nav = get_posts(['post_type' => 'wp_navigation', 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids']);
$data = ['post_type' => 'wp_navigation', 'post_status' => 'publish', 'post_title' => 'Akdemy header', 'post_content' => $content];
$id = $nav ? wp_update_post(['ID' => $nav[0]] + $data) : wp_insert_post($data);
echo "wp_navigation #$id updated\n";
