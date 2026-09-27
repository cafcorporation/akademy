<?php
/**
 * Footer mirroring caf-corporation.com (.ln-footer): brand column + 3 link columns + bottom bar with badges.
 * Stored as the theme's "footer" template part override.
 * Run: powershell -File scripts\wp.ps1 eval-file wordpress\setup\footer.php
 */
$courses = get_permalink((int) get_option('learn_press_courses_page_id'));
$profile = get_permalink((int) get_option('learn_press_profile_page_id'));
$logo    = plugins_url('assets/akdemy-logo-white.svg', WP_PLUGIN_DIR . '/akdemy-bridge/akdemy-bridge.php');
$year    = date('Y');

$html = <<<HTML
<div class="akdemy-footer">
  <div class="akdemy-footer-inner">
    <div class="akdemy-footer-grid">
      <div>
        <img class="akdemy-footer-logo" src="{$logo}" alt="Akdemy — Cost Engineering Campus" width="200">
        <p class="akdemy-footer-desc">The cost engineering campus of CAF Corporation. Practical courses on CAPEX estimating, AACE classification and the Kpex cost intelligence platform.</p>
      </div>
      <div>
        <div class="akdemy-footer-col-title">Akdemy</div>
        <div class="akdemy-footer-links">
          <a href="{$courses}">Courses</a>
          <a href="{$profile}">Sign In</a>
        </div>
      </div>
      <div>
        <div class="akdemy-footer-col-title">Kpex</div>
        <div class="akdemy-footer-links">
          <a href="https://caf-corporation.com/" target="_blank" rel="noopener">Kpex Platform</a>
          <a href="https://caf-corporation.com/services" target="_blank" rel="noopener">Who We Are</a>
        </div>
      </div>
      <div>
        <div class="akdemy-footer-col-title">Contact</div>
        <div class="akdemy-footer-links">
          <a href="mailto:support@caf-corporation.com">support@caf-corporation.com</a>
        </div>
      </div>
    </div>
    <div class="akdemy-footer-bottom">
      <div class="akdemy-footer-copy">© {$year} CAF Corporation Ingeniería de Costes SL</div>
      <div class="akdemy-footer-badges"><span>AACE</span><span>PMI</span><span>KPEX</span></div>
    </div>
  </div>
</div>
HTML;

$content = "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";

$existing = get_posts([
    'post_type'   => 'wp_template_part',
    'name'        => 'footer',
    'numberposts' => 1,
    'post_status' => 'publish',
    'tax_query'   => [['taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => get_stylesheet()]],
]);
$data = [
    'post_type'    => 'wp_template_part',
    'post_name'    => 'footer',
    'post_title'   => 'Footer',
    'post_status'  => 'publish',
    'post_content' => $content,
];
$id = $existing ? wp_update_post(['ID' => $existing[0]->ID] + $data) : wp_insert_post($data);
wp_set_object_terms($id, get_stylesheet(), 'wp_theme');
wp_set_object_terms($id, 'footer', 'wp_template_part_area');
echo "footer template part #$id\n";
