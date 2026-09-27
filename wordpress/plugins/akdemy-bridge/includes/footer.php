<?php
/**
 * CAF-style footer (mirrors caf-corporation.com .ln-footer). Rendered by Eduma in place of its
 * default copyright bar ("Education WordPress Theme by ThimPress").
 */

if (!defined('ABSPATH')) {
    exit;
}

function akdemy_footer_html(): string
{
    $courses = function_exists('learn_press_get_page_link') ? learn_press_get_page_link('courses') : home_url('/courses/');
    $profile = function_exists('learn_press_get_page_link') ? learn_press_get_page_link('profile') : wp_login_url();
    $logo    = plugins_url('assets/akdemy-logo-white.svg', AKDEMY_BRIDGE_FILE);
    // Spanish inside Spanish courses (includes/locale.php), English elsewhere.
    $es = function_exists('akdemy_request_is_spanish_course') && akdemy_request_is_spanish_course();
    $t  = fn (string $en, string $es_text) => esc_html($es ? $es_text : $en);
    ob_start(); ?>
<div class="akdemy-footer">
  <div class="akdemy-footer-inner">
    <div class="akdemy-footer-grid">
      <div>
        <img class="akdemy-footer-logo" src="<?php echo esc_url($logo); ?>" alt="Akdemy — Cost Engineering Campus" width="200">
        <p class="akdemy-footer-desc"><?php echo $t('The cost engineering campus of CAF Corporation. Practical courses on CAPEX estimating, AACE classification and the Kpex cost intelligence platform.', 'El campus de ingeniería de costos de CAF Corporation. Cursos prácticos de estimación de CAPEX, clasificación AACE y la plataforma de inteligencia de costos Kpex.'); ?></p>
      </div>
      <div>
        <div class="akdemy-footer-col-title">Akdemy</div>
        <div class="akdemy-footer-links">
          <a href="<?php echo esc_url($courses); ?>"><?php echo $t('Courses', 'Cursos'); ?></a>
          <a href="<?php echo esc_url(home_url('/insights/')); ?>">Insights</a>
          <a href="<?php echo esc_url($profile); ?>"><?php echo $t('Sign In', 'Iniciar sesión'); ?></a>
        </div>
      </div>
      <div>
        <div class="akdemy-footer-col-title">Kpex</div>
        <div class="akdemy-footer-links">
          <a href="https://caf-corporation.com/" target="_blank" rel="noopener"><?php echo $t('Kpex Platform', 'Plataforma Kpex'); ?></a>
          <a href="https://caf-corporation.com/services" target="_blank" rel="noopener"><?php echo $t('Who We Are', 'Quiénes somos'); ?></a>
        </div>
      </div>
      <div>
        <div class="akdemy-footer-col-title"><?php echo $t('Contact', 'Contacto'); ?></div>
        <div class="akdemy-footer-links">
          <a href="mailto:support@caf-corporation.com">support@caf-corporation.com</a>
        </div>
      </div>
    </div>
    <div class="akdemy-footer-bottom">
      <div class="akdemy-footer-copy">© <?php echo esc_html(gmdate('Y')); ?> CAF Corporation Ingeniería de Costes SL</div>
      <div class="akdemy-footer-badges"><span>AACE</span><span>PMI</span><span>KPEX</span></div>
    </div>
  </div>
</div>
<?php
    return (string) ob_get_clean();
}

// Eduma: swap its copyright bar for the CAF footer.
add_action('after_setup_theme', function () {
    if (get_template() !== 'eduma') {
        return;
    }
    remove_action('thim_copyright_area', 'thim_print_copyright');
    add_action('thim_copyright_area', function () {
        echo akdemy_footer_html(); // phpcs:ignore — escaped inside
    });
}, 20);
