<?php
/**
 * LearnPress checkout, Akdemy style: order summary card with what the course includes,
 * no "Note to administrator" box, and Spanish copy when the cart holds a Spanish course.
 */

defined('ABSPATH') || exit;

function akdemy_is_checkout_page(): bool
{
    return did_action('wp') && function_exists('learn_press_get_page_id')
        && (int) get_queried_object_id() === (int) learn_press_get_page_id('checkout');
}

/** Courses in the LearnPress cart, as post IDs. */
function akdemy_cart_course_ids(): array
{
    $cart = class_exists('LearnPress') ? LearnPress::instance()->cart : null;
    return $cart ? array_map(fn($i) => (int) $i['item_id'], array_values((array) $cart->get_items())) : [];
}

/** 'es' when every course in the cart is Spanish, else 'en'. */
function akdemy_checkout_lang(): string
{
    static $lang = null;
    if ($lang === null) {
        $ids  = akdemy_cart_course_ids();
        $lang = $ids && !array_filter($ids, fn($id) => get_post_meta($id, '_akdemy_lang', true) !== 'es') ? 'es' : 'en';
    }
    return $lang;
}

// Runs on 'wp' so it sees Eduma's own re-hooking (it moves both blocks into the order column).
add_action('wp', function () {
    if (!class_exists('LearnPress')) {
        return;
    }
    $tpl = LearnPress::instance()->template('checkout');
    // One-off courses need no note to the administrator.
    remove_action('learn-press/after-checkout-form', $tpl->func('order_comment'), 60);
    remove_action('learn-press/before-checkout-form', $tpl->func('order_comment'), 11);
    // "Logged in as…" belongs above the payment, not inside the order card.
    remove_action('learn-press/before-checkout-form', $tpl->func('account_logged_in'), 9);
    remove_action('learn-press/after-checkout-form', $tpl->func('account_logged_in'), 20);
    add_action('learn-press/after-checkout-form', $tpl->func('account_logged_in'), 5);
});

add_action('learn-press/before-checkout-form', function () {
    $es  = akdemy_checkout_lang() === 'es';
    $ids = akdemy_cart_course_ids();
    $id  = $ids[0] ?? 0;
    $reg = (float) get_post_meta($id, '_lp_regular_price', true);
    $now = (float) get_post_meta($id, '_lp_sale_price', true);
    $perks = $es
        ? ['Certificado de finalización CAF Corporation', '15 días de acceso a Kpex desde hoy', 'Descuento en tu membresía Kpex', 'Quizzes por módulo y examen final', 'Acceso en línea a tu ritmo']
        : ['CAF Corporation certificate of completion', '15 days of Kpex access from today', 'Discount on your Kpex membership', 'Module quizzes and a final exam', 'Self-paced online access'];
    ?>
    <div class="akdemy-checkout-perks">
      <?php if (count($ids) === 1 && $reg > 0 && $now > 0 && $now < $reg) : ?>
        <div class="akdemy-checkout-launch">
          <span class="akdemy-checkout-tag"><?php echo $es ? 'Precio de lanzamiento' : 'Launch price'; ?></span>
          <span><?php echo $es ? 'Antes' : 'Regular'; ?> <s><?php echo esc_html(learn_press_format_price($reg, true)); ?></s>
          · <?php echo $es ? 'ahorras' : 'you save'; ?> <strong><?php echo esc_html(learn_press_format_price($reg - $now, true)); ?></strong></span>
        </div>
      <?php endif; ?>
      <h5><?php echo $es ? 'Tu inscripción incluye' : 'Your enrollment includes'; ?></h5>
      <ul>
        <?php foreach ($perks as $perk) : ?><li><?php echo esc_html($perk); ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php
}, 20);

add_filter('learn-press/checkout-proceed-button-text', fn() => akdemy_checkout_lang() === 'es' ? 'Completar inscripción' : 'Complete enrollment');

add_filter('the_title', function ($title, $post_id = 0) {
    if (!akdemy_is_checkout_page() || akdemy_checkout_lang() !== 'es') {
        return $title;
    }
    $es = [
        (int) learn_press_get_page_id('checkout')        => 'Finalizar compra',
        (int) learn_press_get_page_id('term_conditions') => 'Términos y condiciones',
    ];
    return $es[(int) $post_id] ?? $title;
}, 10, 2);

// LearnPress checkout strings in Spanish for Spanish carts.
add_filter('gettext_learnpress', function ($translation, $text) {
    static $es = [
        'Your order'                         => 'Tu pedido',
        'Subtotal'                           => 'Subtotal',
        'Total'                              => 'Total',
        'Payment'                            => 'Método de pago',
        'Logged in as <a href="%1$s">%2$s</a>.' => 'Sesión iniciada como <a href="%1$s">%2$s</a>.',
        'Log out &raquo;'                    => 'Cerrar sesión &raquo;',
        'Log out of this account'            => 'Cerrar sesión de esta cuenta',
        'By completing your purchase you agree to those <a href="%1$s" target="_blank">%2$s</a>.'
            => 'Al completar la compra aceptas los <a href="%1$s" target="_blank">%2$s</a>.',
        'Terms of Service'                   => 'Términos del servicio',
        'Checkout'                           => 'Finalizar compra',
    ];
    if (isset($es[$text]) && akdemy_is_checkout_page() && akdemy_checkout_lang() === 'es') {
        return $es[$text];
    }
    return $translation;
}, 10, 2);

add_filter('gettext_with_context_learnpress', function ($translation, $text, $context) {
    if ($text === 'Secure Connection' && akdemy_is_checkout_page() && akdemy_checkout_lang() === 'es') {
        return 'Conexión segura';
    }
    return $translation;
}, 10, 3);
