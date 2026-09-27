<?php
/**
 * CAF Insights inside Akdemy.
 *
 * Source: caf-corporation.com/insights/feed.json (Kpex2G InsightArticleController::publicFeed).
 * Resilience: live feed (cached 1h) → last good response → bundled snapshot (tools/snapshot-insights.mjs).
 *
 * Shortcodes:
 *   [akdemy_insights limit="3"]          cards linking to the article on caf-corporation.com
 *   [akdemy_courses]                     LearnPress course cards (used on the home page)
 */

if (!defined('ABSPATH')) {
    exit;
}

const AKDEMY_INSIGHTS_DEFAULT_FEED = 'https://caf-corporation.com/insights/feed.json';

function akdemy_insights_feed(): array
{
    $cached = get_transient('akdemy_insights_feed');
    if (is_array($cached)) {
        return $cached;
    }

    $url      = get_option('akdemy_insights_feed_url', AKDEMY_INSIGHTS_DEFAULT_FEED);
    $response = wp_remote_get(add_query_arg('limit', 24, $url), ['timeout' => 6]);
    $data     = null;
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $json = json_decode(wp_remote_retrieve_body($response), true);
        if (is_array($json) && isset($json['items']) && is_array($json['items'])) {
            $data = $json;
            update_option('akdemy_insights_last_good', $json, false);
        }
    }

    if (!$data) {
        $data = get_option('akdemy_insights_last_good');
    }
    if (!is_array($data)) {
        $snapshot = __DIR__ . '/../assets/insights-snapshot.json';
        $data = is_readable($snapshot) ? json_decode((string) file_get_contents($snapshot), true) : ['items' => []];
    }

    // Retry a failing live feed sooner than a healthy one.
    set_transient('akdemy_insights_feed', $data, ($data['source'] ?? '') === 'snapshot' ? 10 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS);
    return $data;
}

add_shortcode('akdemy_insights', function ($atts) {
    $atts  = shortcode_atts(['limit' => 3, 'more' => 'yes'], $atts);
    $feed  = akdemy_insights_feed();
    $items = array_slice($feed['items'] ?? [], 0, max(1, (int) $atts['limit']));
    if (!$items) {
        return '';
    }
    $all = esc_url($feed['insights_url'] ?? 'https://caf-corporation.com/insights');

    ob_start(); ?>
    <div class="akdemy-insights-grid">
        <?php foreach ($items as $it) :
            $color = preg_match('/^#[0-9a-f]{3,6}$/i', (string) ($it['category_color'] ?? '')) ? $it['category_color'] : '#F5A011'; ?>
            <a class="akdemy-insight-card" href="<?php echo esc_url($it['url']); ?>" target="_blank" rel="noopener">
                <div class="akdemy-insight-img"<?php if (!empty($it['cover_url'])) : ?> style="background-image:url('<?php echo esc_url($it['cover_url']); ?>')"<?php endif; ?>>
                    <?php if (!empty($it['members_only'])) : ?><span class="akdemy-insight-lock">Members</span><?php endif; ?>
                </div>
                <div class="akdemy-insight-body">
                    <div class="akdemy-insight-cat" style="color:<?php echo esc_attr($color); ?>"><?php echo esc_html($it['category_label'] ?? ''); ?></div>
                    <div class="akdemy-insight-title"><?php echo esc_html($it['title']); ?></div>
                    <div class="akdemy-insight-excerpt"><?php echo esc_html(wp_trim_words((string) ($it['excerpt'] ?? ''), 24)); ?></div>
                    <div class="akdemy-insight-meta">
                        <span><?php echo (int) ($it['reading_time'] ?? 5); ?> min read</span>
                        <span class="akdemy-insight-read">Read on CAF →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($atts['more'] === 'yes') : ?>
        <p class="akdemy-insights-more"><a href="<?php echo $all; ?>" target="_blank" rel="noopener">All insights on caf-corporation.com →</a></p>
    <?php endif;
    return ob_get_clean();
});

add_shortcode('akdemy_courses', function ($atts) {
    $atts    = shortcode_atts(['limit' => 6], $atts);
    $courses = get_posts(['post_type' => 'lp_course', 'post_status' => 'publish', 'numberposts' => (int) $atts['limit']]);
    if (!$courses) {
        return '';
    }
    ob_start(); ?>
    <div class="akdemy-course-grid">
        <?php foreach ($courses as $post) :
            $course = function_exists('learn_press_get_course') ? learn_press_get_course($post->ID) : null; ?>
            <a class="akdemy-course-card" href="<?php echo esc_url(get_permalink($post)); ?>">
                <div class="akdemy-course-img"><?php echo get_the_post_thumbnail($post, 'large'); ?></div>
                <div class="akdemy-course-body">
                    <div class="akdemy-course-title"><?php echo esc_html(get_the_title($post)); ?></div>
                    <div class="akdemy-course-excerpt"><?php echo esc_html(get_the_excerpt($post)); ?></div>
                    <div class="akdemy-course-foot">
                        <span class="akdemy-course-price"><?php echo $course ? wp_kses_post($course->get_course_price_html()) : ''; ?></span>
                        <span class="akdemy-course-cta">View course →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
});
