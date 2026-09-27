<?php
/**
 * Course page behaviour:
 * - "You May Like" / related courses only lists courses in the same language as the one being viewed.
 * - Lesson comments on (student ↔ facilitator); none on the course page or quizzes.
 */

defined('ABSPATH') || exit;

// Eduma's thim_lp_get_related_courses() runs a plain WP_Query (no filter of its own): narrow it here.
add_action('pre_get_posts', function (WP_Query $q) {
    if (is_admin() || $q->is_main_query() || !is_singular('lp_course') || $q->get('post_type') !== 'lp_course') {
        return;
    }
    $current = (int) get_queried_object_id();
    if (!in_array($current, array_map('intval', (array) $q->get('post__not_in')), true)) {
        return; // not a "related to this course" query
    }
    $lang = get_post_meta($current, '_akdemy_lang', true) ?: 'en';
    $meta = (array) $q->get('meta_query');
    $meta[] = ['key' => '_akdemy_lang', 'value' => $lang];
    $q->set('meta_query', $meta);
});

// Comments are the student ↔ facilitator channel: open on every lesson, closed on the course sales page
// and on quizzes (they would leak answers). Only logged-in users can comment (option comment_registration).
add_filter('comments_open', function ($open, $post_id) {
    return match (get_post_type($post_id)) {
        'lp_lesson'           => true,
        'lp_course', 'lp_quiz' => false,
        default               => $open,
    };
}, 20, 2);
add_filter('pings_open', fn ($open, $post_id) => in_array(get_post_type($post_id), ['lp_course', 'lp_lesson', 'lp_quiz'], true) ? false : $open, 20, 2);
