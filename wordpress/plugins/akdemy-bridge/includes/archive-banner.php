<?php
/**
 * Slim page banner for the LearnPress course archive (/courses/ + course category/tag archives).
 *
 * LearnPress (free) has no banner; this prepends one to its "Archive Course" block group at render
 * time, so the LearnPress template itself stays untouched and update-safe.
 * Image: option `akdemy_courses_banner_url` (falls back to the classroom hero).
 */

if (!defined('ABSPATH')) {
    exit;
}

function akdemy_is_course_archive(): bool
{
    return is_post_type_archive('lp_course') || is_tax(['course_category', 'course_tag']);
}

add_filter('render_block_core/group', function ($html, $block) {
    static $done = false;
    if ($done || is_admin() || !akdemy_is_course_archive()
        || ($block['attrs']['metadata']['name'] ?? '') !== 'Archive Course') {
        return $html;
    }
    $done = true;

    $image = get_option('akdemy_courses_banner_url') ?: plugins_url('assets/hero-course.jpg', AKDEMY_BRIDGE_FILE);
    $title = is_tax() ? single_term_title('', false) : 'Courses';
    $sub   = is_tax()
        ? wp_strip_all_tags(term_description())
        : 'Practical CAPEX estimating courses built on Kpex — AACE- and PMI-aligned, in English and Spanish.';
    $home  = home_url('/');
    $all   = get_post_type_archive_link('lp_course');

    ob_start(); ?>
    <section class="akdemy-page-banner" style="--akdemy-banner-img:url('<?php echo esc_url($image); ?>')">
      <div class="akdemy-page-banner-inner">
        <nav class="akdemy-page-banner-crumbs" aria-label="Breadcrumb">
          <a href="<?php echo esc_url($home); ?>">Home</a><span>›</span>
          <?php if (is_tax()) : ?><a href="<?php echo esc_url($all); ?>">Courses</a><span>›</span><?php endif; ?>
          <span aria-current="page"><?php echo esc_html($title); ?></span>
        </nav>
        <div class="akdemy-eyebrow">Akdemy · Cost Engineering Campus</div>
        <h1><?php echo esc_html($title); ?></h1>
        <?php if ($sub) : ?><p><?php echo esc_html($sub); ?></p><?php endif; ?>
      </div>
    </section>
    <?php
    return ob_get_clean() . $html;
}, 10, 2);

// The banner carries the breadcrumb + title → hide LearnPress's own on these pages.
add_filter('body_class', function ($classes) {
    if (akdemy_is_course_archive()) {
        $classes[] = 'akdemy-has-page-banner';
    }
    return $classes;
});
