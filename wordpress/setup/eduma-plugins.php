<?php
/**
 * Installs + activates the Eduma-bundled plugins Akdemy needs, through Thim Core's own installer
 * (same path as the "Install" buttons in Eduma › Dashboard › Plugins; requires an active licence).
 * Run: powershell -File scripts\wp-eduma.ps1 eval-file wordpress\setup\eduma-plugins.php
 */
$wanted = ['elementor', 'thim-elementor-kit', 'learnpress-stripe', 'learnpress-certificates'];

$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) $admins[0]);
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

foreach ($wanted as $slug) {
    $plugin = Thim_Plugins_Manager::get_plugin_by_slug($slug);
    if (!$plugin) {
        echo "$slug: not offered by Eduma\n";
        continue;
    }
    $status = $plugin->get_status();
    if ($status === 'not_installed') {
        $ok = $plugin->install();
        echo "$slug: install " . ($ok ? 'OK' : 'FAILED ' . wp_json_encode($plugin->get_messages())) . "\n";
    }
    if (!$plugin->is_active()) {
        $res = $plugin->activate(true);
        echo "$slug: activate " . (is_wp_error($res) ? $res->get_error_message() : 'OK') . "\n";
    } else {
        echo "$slug: already active\n";
    }
}
