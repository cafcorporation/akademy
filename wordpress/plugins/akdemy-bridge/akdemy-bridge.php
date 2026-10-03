<?php
/**
 * Plugin Name: Akdemy Bridge
 * Description: REST bridge that lets Claude build and manage LearnPress courses for Akdemy AI.
 * Version: 0.2.0
 * Author: CAF Corporation
 * Requires Plugins: learnpress
 */

if (!defined('ABSPATH')) {
    exit;
}

const AKDEMY_BRIDGE_VERSION = '0.2.0';

// Pretty permalinks are required for /wp-json/ and clean course URLs.
register_activation_hook(__FILE__, function () {
    if (!get_option('permalink_structure')) {
        update_option('permalink_structure', '/%postname%/');
    }
    flush_rewrite_rules();
});

const AKDEMY_BRIDGE_FILE = __FILE__;

/** True when an official LearnPress add-on (e.g. bundled with Eduma) is active — Akdemy's own fallback then steps aside. */
function akdemy_addon_active(string $plugin_file): bool
{
    return in_array($plugin_file, (array) get_option('active_plugins', []), true);
}

require_once __DIR__ . '/includes/class-importer.php';
require_once __DIR__ . '/includes/brand.php';
require_once __DIR__ . '/includes/insights.php';
require_once __DIR__ . '/includes/certificates.php';
require_once __DIR__ . '/includes/archive-banner.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/backgrounds.php';
require_once __DIR__ . '/includes/checkout.php';
require_once __DIR__ . '/includes/course-labels.php';
require_once __DIR__ . '/includes/locale.php';
require_once __DIR__ . '/includes/course-rules.php';
require_once __DIR__ . '/includes/manual.php';

// Stripe gateway extends a LearnPress class → load once LearnPress is available.
add_action('plugins_loaded', function () {
    if (class_exists('LP_Gateway_Abstract') && !akdemy_addon_active('learnpress-stripe/learnpress-stripe.php')) {
        require_once __DIR__ . '/includes/class-gateway-stripe.php';
    }
}, 20);

/**
 * WP-CLI: wp akdemy import <file.json>
 * Publishes a package built by tools/build-course.mjs. Re-running replaces the previous import.
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('akdemy import', function ($args) {
        [$file] = $args;
        $pkg = json_decode((string) file_get_contents($file), true);
        if (!is_array($pkg) || empty($pkg['key'])) {
            WP_CLI::error("Not a course package: $file");
        }
        $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
        wp_set_current_user((int) ($admins[0] ?? 0));
        // LearnPress grants course caps to roles only when activated from wp-admin; do it if missing.
        if (!current_user_can('edit_lp_courses') && function_exists('learn_press_add_user_roles')) {
            learn_press_add_user_roles();
            wp_set_current_user(get_current_user_id());
        }
        // LearnPress abilities require an MCP API-key context; WP-CLI already has full server access.
        \LearnPress\MCP\Auth\AuthContext::set_api_key_auth(0, get_current_user_id(), 'read_write');
        try {
            $course_id = (new Akdemy_Importer(fn ($m) => WP_CLI::log($m)))->import($pkg);
        } catch (Throwable $e) {
            WP_CLI::error($e->getMessage());
        }
        WP_CLI::success('Published: ' . get_permalink($course_id));
    });

    /** wp akdemy update-course <file.json> — refresh only the course page (overview, title), keeping IDs. */
    WP_CLI::add_command('akdemy update-course', function ($args) {
        [$file] = $args;
        $pkg = json_decode((string) file_get_contents($file), true);
        if (!is_array($pkg) || empty($pkg['key'])) {
            WP_CLI::error("Not a course package: $file");
        }
        $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
        wp_set_current_user((int) ($admins[0] ?? 0)); // media uploads need a user
        try {
            $course_id = (new Akdemy_Importer(fn ($m) => WP_CLI::log($m)))->update_course($pkg);
        } catch (Throwable $e) {
            WP_CLI::error($e->getMessage());
        }
        WP_CLI::success('Updated: ' . get_permalink($course_id));
    });
}

add_action('rest_api_init', function () {
    // Health check: confirms auth works and LearnPress is active.
    register_rest_route('akdemy/v1', '/ping', [
        'methods'             => 'GET',
        'permission_callback' => fn () => current_user_can('edit_posts'),
        'callback'            => function () {
            return [
                'bridge'      => AKDEMY_BRIDGE_VERSION,
                'user'        => wp_get_current_user()->user_login,
                'wordpress'   => get_bloginfo('version'),
                'learnpress'  => defined('LEARNPRESS_VERSION') ? LEARNPRESS_VERSION : null,
                'courses'     => (int) (wp_count_posts('lp_course')->publish ?? 0),
            ];
        },
    ]);
});
