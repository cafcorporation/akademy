<?php
/**
 * LearnPress payment gateway: Stripe Checkout (hosted page) — no paid add-on required.
 *
 * Flow: LP order → Stripe Checkout Session (card data never touches Akdemy) → student returns →
 * payment confirmed by BOTH (a) retrieving the session on return and (b) the signed webhook
 * `checkout.session.completed` at /wp-json/akdemy/v1/stripe/webhook. Either one completes the order.
 *
 * Keys are entered by the site owner in LearnPress › Settings › Payments › Card (Stripe).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Akdemy_Gateway_Stripe extends LP_Gateway_Abstract
{
    public $id = 'akdemy-stripe';
    const API = 'https://api.stripe.com/v1/';

    public function __construct()
    {
        $this->method_title       = 'Card (Stripe)';
        $this->method_description = 'Pay by card on Stripe’s secure checkout page.';
        $this->title              = 'Card (Stripe)';
        $this->description        = 'Pay securely by card. You will be redirected to Stripe.';
        $this->order_button_text  = 'Pay by card';
        parent::__construct();
    }

    public function get_settings(): array
    {
        $webhook = rest_url('akdemy/v1/stripe/webhook');
        return [
            ['type' => 'title', 'title' => 'Card payments with Stripe Checkout',
             'desc' => 'Create a webhook in Stripe (Developers › Webhooks) pointing to <code>' . esc_html($webhook) . '</code> with the event <code>checkout.session.completed</code>, and paste its signing secret below.'],
            ['title' => 'Enable/Disable', 'id' => '[enable]', 'default' => 'no', 'type' => 'checkbox', 'desc' => 'Enable card payments (Stripe)'],
            ['title' => 'Test mode', 'id' => '[test_mode]', 'default' => 'yes', 'type' => 'checkbox', 'desc' => 'Use the test keys (no real charges)'],
            ['title' => 'Test secret key', 'id' => '[test_secret_key]', 'type' => 'password', 'desc' => 'sk_test_…'],
            ['title' => 'Test webhook signing secret', 'id' => '[test_webhook_secret]', 'type' => 'password', 'desc' => 'whsec_…'],
            ['title' => 'Live secret key', 'id' => '[live_secret_key]', 'type' => 'password', 'desc' => 'sk_live_…'],
            ['title' => 'Live webhook signing secret', 'id' => '[live_webhook_secret]', 'type' => 'password', 'desc' => 'whsec_…'],
            ['type' => 'sectionend'],
        ];
    }

    private function mode(): string
    {
        return $this->settings->get('test_mode', 'yes') === 'yes' ? 'test' : 'live';
    }

    public function secret_key(): string
    {
        return trim((string) $this->settings->get($this->mode() . '_secret_key', ''));
    }

    public function webhook_secret(): string
    {
        return trim((string) $this->settings->get($this->mode() . '_webhook_secret', ''));
    }

    /** Hide the method at checkout until a key is configured. */
    public function is_enabled()
    {
        return parent::is_enabled() && $this->secret_key() !== '';
    }

    public function api(string $method, string $path, array $body = [])
    {
        $response = wp_remote_request(self::API . $path, [
            'method'  => $method,
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $this->secret_key(), 'Stripe-Version' => '2024-06-20'],
            'body'    => $body,
        ]);
        if (is_wp_error($response)) {
            throw new Exception('Stripe: ' . $response->get_error_message());
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (wp_remote_retrieve_response_code($response) >= 400) {
            throw new Exception('Stripe: ' . ($data['error']['message'] ?? 'request failed'));
        }
        return $data;
    }

    public function process_payment($order_id)
    {
        $order    = learn_press_get_order($order_id);
        $currency = strtolower(learn_press_get_currency());
        $names    = [];
        foreach ((array) $order->get_items() as $item) {
            $names[] = $item['name'] ?? get_the_title($item['course_id'] ?? 0);
        }
        $user     = get_userdata($order->get_user_id());
        $return   = add_query_arg('akdemy_stripe_session', '{CHECKOUT_SESSION_ID}', $this->get_return_url($order));

        $session = $this->api('POST', 'checkout/sessions', [
            'mode'                                           => 'payment',
            'client_reference_id'                            => (string) $order_id,
            'metadata[lp_order_id]'                          => (string) $order_id,
            'customer_email'                                 => $user ? $user->user_email : null,
            'line_items[0][quantity]'                        => 1,
            'line_items[0][price_data][currency]'            => $currency,
            'line_items[0][price_data][unit_amount]'         => (int) round((float) $order->get_total() * 100),
            'line_items[0][price_data][product_data][name]'  => implode(', ', array_filter($names)) ?: 'Akdemy course',
            'success_url'                                    => str_replace('%7BCHECKOUT_SESSION_ID%7D', '{CHECKOUT_SESSION_ID}', $return),
            'cancel_url'                                     => learn_press_get_page_link('checkout'),
        ]);
        update_post_meta($order_id, '_akdemy_stripe_session', $session['id']);

        return ['result' => 'success', 'redirect' => $session['url']];
    }

    /** Completes the LP order if the session is paid and belongs to it (idempotent). */
    public function complete_from_session(array $session): bool
    {
        $order_id = (int) ($session['client_reference_id'] ?? $session['metadata']['lp_order_id'] ?? 0);
        if (!$order_id || ($session['payment_status'] ?? '') !== 'paid') {
            return false;
        }
        if (get_post_meta($order_id, '_akdemy_stripe_session', true) !== $session['id']) {
            return false;
        }
        $order = learn_press_get_order($order_id);
        if (!$order || $order->has_status('completed')) {
            return (bool) $order;
        }
        return $order->payment_complete((string) ($session['payment_intent'] ?? $session['id']));
    }
}

// ── Registration (after LearnPress is loaded) ─────────────────────────────────
add_filter('learn-press/payment-methods', function ($gateways) {
    if (class_exists('LP_Gateway_Abstract')) {
        $gateways['akdemy-stripe'] = 'Akdemy_Gateway_Stripe';
    }
    return $gateways;
});

function akdemy_stripe_gateway(): ?Akdemy_Gateway_Stripe
{
    $all = LP_Gateways::instance()->get_gateways();
    return ($all['akdemy-stripe'] ?? null) instanceof Akdemy_Gateway_Stripe ? $all['akdemy-stripe'] : null;
}

// (a) Return from Stripe: verify the session server-side, then complete.
add_action('template_redirect', function () {
    $sid = isset($_GET['akdemy_stripe_session']) ? sanitize_text_field(wp_unslash($_GET['akdemy_stripe_session'])) : '';
    if (!$sid || strpos($sid, 'cs_') !== 0 || !($gw = akdemy_stripe_gateway()) || !$gw->secret_key()) {
        return;
    }
    try {
        $gw->complete_from_session($gw->api('GET', 'checkout/sessions/' . rawurlencode($sid)));
    } catch (Throwable $e) {
        error_log('[akdemy-stripe] return verify: ' . $e->getMessage());
    }
});

// (b) Signed webhook.
add_action('rest_api_init', function () {
    register_rest_route('akdemy/v1', '/stripe/webhook', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true', // authenticated by the Stripe signature below
        'callback'            => function (WP_REST_Request $request) {
            $gw = akdemy_stripe_gateway();
            if (!$gw || !$gw->webhook_secret()) {
                return new WP_REST_Response(['error' => 'not configured'], 503);
            }
            $payload = $request->get_body();
            $header  = (string) $request->get_header('stripe_signature');
            parse_str(str_replace(',', '&', $header), $sig);
            $ts = (int) ($sig['t'] ?? 0);
            $expected = hash_hmac('sha256', $ts . '.' . $payload, $gw->webhook_secret());
            if (!$ts || abs(time() - $ts) > 300 || !hash_equals($expected, (string) ($sig['v1'] ?? ''))) {
                return new WP_REST_Response(['error' => 'bad signature'], 400);
            }
            $event = json_decode($payload, true);
            if (($event['type'] ?? '') === 'checkout.session.completed') {
                $gw->complete_from_session($event['data']['object'] ?? []);
            }
            return new WP_REST_Response(['received' => true], 200);
        },
    ]);
});
