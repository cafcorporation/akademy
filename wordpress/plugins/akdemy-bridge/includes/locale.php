<?php
/**
 * Spanish courses speak Spanish: while a visitor is on a Spanish course (its page, lessons and quizzes),
 * WordPress runs in es_ES so LearnPress and core strings use their official Spanish translations.
 * The rest of the site, and the English courses, stay in English. Eduma strings without an official
 * Spanish pack are covered in course-labels.php.
 */

defined('ABSPATH') || exit;

/** Slug of the course a front-end URL belongs to (/courses/<slug>/…), or null. */
function akdemy_request_course_slug(): ?string
{
    if (is_admin() || empty($_SERVER['REQUEST_URI'])) {
        return null;
    }
    $path = trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
    return preg_match('#^courses/([^/]+)#', $path, $m) ? sanitize_title($m[1]) : null;
}

/** True when the current request is inside a Spanish course. Decided from the URL, before WP_Query runs. */
function akdemy_request_is_spanish_course(): bool
{
    static $es = null;
    if ($es !== null) {
        return $es;
    }
    $slug = akdemy_request_course_slug();
    if (!$slug) {
        return $es = false;
    }
    global $wpdb;
    $lang = $wpdb->get_var($wpdb->prepare(
        "SELECT pm.meta_value FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_akdemy_lang'
         WHERE p.post_type = 'lp_course' AND p.post_name = %s LIMIT 1",
        $slug
    ));
    return $es = ($lang === 'es');
}

$akdemy_es_locale = fn ($locale) => akdemy_request_is_spanish_course() ? 'es_ES' : $locale;
add_filter('locale', $akdemy_es_locale);
add_filter('determine_locale', $akdemy_es_locale);
