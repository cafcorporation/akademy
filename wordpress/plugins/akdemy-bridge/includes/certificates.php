<?php
/**
 * Akdemy certificates (no paid LearnPress add-on needed).
 *
 * Issued when a student PASSES a course (LearnPress evaluates the final quiz, ≥ passing grade).
 * Each certificate has a public verification page: /certificate/<CODE>/ (printable → "Save as PDF").
 *
 * Storage: user meta `_akdemy_certificates` [course_id => record] + option index `akdemy_cert_index` [code => user/course].
 * Shortcode: [akdemy_my_certificates]
 * WP-CLI:    wp akdemy certificate <user> <course>   (manual issue, e.g. for testing or re-sends)
 */

if (!defined('ABSPATH')) {
    exit;
}

const AKDEMY_CERT_META  = '_akdemy_certificates';
const AKDEMY_CERT_INDEX = 'akdemy_cert_index';

function akdemy_course_lang(int $course_id): string
{
    return get_post_meta($course_id, '_akdemy_lang', true) === 'es' ? 'es' : 'en';
}

function akdemy_certificate_url(string $code): string
{
    return home_url('/certificate/' . rawurlencode($code) . '/');
}

/** Idempotent: returns the existing certificate for this user+course if already issued. */
function akdemy_issue_certificate(int $user_id, int $course_id, ?float $score = null): array
{
    $certs = (array) get_user_meta($user_id, AKDEMY_CERT_META, true);
    if (!empty($certs[$course_id]['code'])) {
        return $certs[$course_id];
    }
    $user = get_userdata($user_id);
    do {
        $code = 'AKD-' . gmdate('Y') . '-' . strtoupper(wp_generate_password(6, false, false));
        $index = (array) get_option(AKDEMY_CERT_INDEX, []);
    } while (isset($index[$code]));

    $record = [
        'code'         => $code,
        'course_id'    => $course_id,
        'course_title' => get_the_title($course_id),
        'lang'         => akdemy_course_lang($course_id),
        'name'         => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->display_name,
        'score'        => $score,
        'issued_at'    => current_time('mysql'),
    ];
    $certs[$course_id] = $record;
    update_user_meta($user_id, AKDEMY_CERT_META, $certs);
    $index[$code] = ['user_id' => $user_id, 'course_id' => $course_id];
    update_option(AKDEMY_CERT_INDEX, $index, false);

    akdemy_send_certificate_email($user, $record);
    do_action('akdemy/certificate-issued', $record, $user_id);
    return $record;
}

function akdemy_find_certificate(string $code): ?array
{
    $index = (array) get_option(AKDEMY_CERT_INDEX, []);
    if (empty($index[$code])) {
        return null;
    }
    $certs = (array) get_user_meta($index[$code]['user_id'], AKDEMY_CERT_META, true);
    return $certs[$index[$code]['course_id']] ?? null;
}

// ── Issue on pass ───────────────────────────────────────────────────────────
add_action('learn-press/user-course/finished', function ($user_course) {
    // The official Certificates add-on (bundled with Eduma) takes over when active.
    if (akdemy_addon_active('learnpress-certificates/learnpress-certificates.php')) {
        return;
    }
    if (!is_object($user_course) || !method_exists($user_course, 'is_passed') || !$user_course->is_passed()) {
        return;
    }
    $score = null;
    if (method_exists($user_course, 'calculate_course_results')) {
        $res   = $user_course->calculate_course_results();
        $score = isset($res['result']) ? (float) $res['result'] : null;
    }
    akdemy_issue_certificate((int) $user_course->user_id, (int) $user_course->item_id, $score);
});

// ── Email ──────────────────────────────────────────────────────────────────
function akdemy_send_certificate_email(WP_User $user, array $c): void
{
    $es    = $c['lang'] === 'es';
    $url   = akdemy_certificate_url($c['code']);
    $trial = get_option('akdemy_trial_url', 'https://caf-corporation.com/register');
    $subj  = $es ? "Tu certificado Akdemy: {$c['course_title']}" : "Your Akdemy certificate: {$c['course_title']}";
    $body  = $es
        ? "<p>Hola {$c['name']},</p><p>¡Felicidades! Has completado <strong>{$c['course_title']}</strong>.</p>"
          . "<p><a href=\"{$url}\">Ver y descargar tu certificado</a> · Código: <strong>{$c['code']}</strong></p>"
          . "<p><strong>Tu beneficio de alumno:</strong> 15 días de prueba gratuita de Kpex. <a href=\"{$trial}\">Actívala aquí</a>.</p>"
          . "<p>— El equipo de Akdemy · CAF Corporation</p>"
        : "<p>Hi {$c['name']},</p><p>Congratulations! You have completed <strong>{$c['course_title']}</strong>.</p>"
          . "<p><a href=\"{$url}\">View and download your certificate</a> · Code: <strong>{$c['code']}</strong></p>"
          . "<p><strong>Your student benefit:</strong> a 15-day free Kpex trial. <a href=\"{$trial}\">Activate it here</a>.</p>"
          . "<p>— The Akdemy team · CAF Corporation</p>";
    wp_mail($user->user_email, $subj, $body, ['Content-Type: text/html; charset=UTF-8']);
}

// ── Public verification page /certificate/<CODE>/ ────────────────────────────
add_action('init', function () {
    add_rewrite_rule('^certificate/([A-Za-z0-9-]+)/?$', 'index.php?akdemy_cert=$matches[1]', 'top');
});
add_filter('query_vars', fn ($v) => array_merge($v, ['akdemy_cert']));

add_action('template_redirect', function () {
    $code = get_query_var('akdemy_cert');
    if (!$code) {
        return;
    }
    $c = akdemy_find_certificate(strtoupper(sanitize_text_field($code)));
    if (!$c) {
        status_header(404);
        wp_die(esc_html__('Certificate not found.', 'akdemy'), 'Akdemy', ['response' => 404]);
    }
    akdemy_render_certificate($c);
    exit;
});

function akdemy_render_certificate(array $c): void
{
    $es     = $c['lang'] === 'es';
    $t      = $es
        ? ['cert' => 'Certificado de finalización', 'intro' => 'Se certifica que', 'done' => 'ha completado satisfactoriamente el curso', 'date' => 'Fecha', 'score' => 'Examen final', 'code' => 'Código de verificación', 'print' => 'Descargar PDF / Imprimir', 'verified' => 'Certificado verificado', 'role' => 'Instructor principal']
        : ['cert' => 'Certificate of Completion', 'intro' => 'This is to certify that', 'done' => 'has successfully completed the course', 'date' => 'Date', 'score' => 'Final exam', 'code' => 'Verification code', 'print' => 'Download PDF / Print', 'verified' => 'Verified certificate', 'role' => 'Lead Instructor'];
    // Month names by certificate language, independent of the site locale.
    $ts     = strtotime($c['issued_at']);
    $meses  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $date   = $es
        ? sprintf('%d de %s de %d', (int) gmdate('j', $ts), $meses[(int) gmdate('n', $ts) - 1], (int) gmdate('Y', $ts))
        : gmdate('F j, Y', $ts);
    $logo   = plugins_url('assets/akdemy-logo-white.svg', AKDEMY_BRIDGE_FILE);
    $signer = get_option('akdemy_cert_signatory', 'Carlos Fuenmayor');
    $url    = akdemy_certificate_url($c['code']);
    ?><!DOCTYPE html>
<html lang="<?php echo $es ? 'es' : 'en'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($t['cert'] . ' · ' . $c['name']); ?> — Akdemy</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  @page { size: A4 landscape; margin: 0; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', Calibri, Arial, sans-serif; background: #ECEAE5; color: #1a1a1a;
         -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .bar { max-width: 1123px; margin: 22px auto 12px; display: flex; justify-content: space-between; align-items: center; padding: 0 4px; }
  .verified { font-size: 13px; font-weight: 700; color: #1a7a4a; }
  .verified::before { content: "✔ "; }
  .btn { background: #F5A011; color: #fff; border: 0; border-radius: 10px; padding: 11px 22px; font: 800 14px 'Inter', sans-serif; cursor: pointer; }
  .btn:hover { background: #d4880a; }
  .cert { width: 1123px; height: 794px; margin: 0 auto 40px; background: #fff; position: relative; overflow: hidden;
          box-shadow: 0 18px 60px rgba(0,0,0,.18); display: grid; grid-template-columns: 330px 1fr; }
  .side { background: linear-gradient(160deg, #1a1a18 0%, #2e2e2c 100%); color: #fff; padding: 56px 40px; position: relative; }
  .side::after { content: ''; position: absolute; top: -80px; right: -120px; width: 320px; height: 320px; border-radius: 50%;
                 background: radial-gradient(circle, rgba(245,160,17,.22), transparent 70%); }
  .side img { width: 220px; display: block; }
  .side .curve { position: absolute; left: 0; right: 0; bottom: 0; }
  .side .meta { position: absolute; left: 40px; bottom: 56px; right: 40px; }
  .side .k { font-size: 10px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: #F5A011; margin-top: 18px; }
  .side .v { font-size: 15px; font-weight: 700; margin-top: 4px; }
  .main { padding: 70px 70px 56px 64px; display: flex; flex-direction: column; border-top: 8px solid #F5A011; }
  .pill { align-self: flex-start; background: rgba(245,160,17,.1); border: 1px solid rgba(245,160,17,.28); color: #F5A011;
          border-radius: 20px; padding: 5px 14px; font-size: 11px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; }
  h1 { font-size: 46px; font-weight: 900; letter-spacing: -1.5px; line-height: 1.05; margin-top: 22px; }
  .intro { margin-top: 44px; font-size: 16px; color: #888; }
  .name { font-size: 50px; font-weight: 900; letter-spacing: -1.5px; line-height: 1.05; margin-top: 8px;
          padding-bottom: 14px; border-bottom: 2px solid #f0ece6; }
  .done { margin-top: 22px; font-size: 16px; color: #888; }
  .course { font-size: 24px; font-weight: 800; letter-spacing: -.5px; line-height: 1.25; margin-top: 6px; max-width: 640px; }
  .foot { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; }
  .sig { min-width: 260px; }
  .sig .line { border-top: 1.5px solid #1a1a1a; margin-bottom: 8px; }
  .sig .n { font-weight: 800; font-size: 15px; }
  .sig .r { font-size: 12px; color: #888; margin-top: 2px; }
  .badges span { display: inline-block; background: rgba(245,160,17,.12); border: 1px solid rgba(245,160,17,.3); color: #d4880a;
                 border-radius: 6px; padding: 4px 10px; font-size: 10px; font-weight: 800; letter-spacing: .4px; margin-left: 6px; }
  @media print { body { background: #fff; } .bar { display: none; } .cert { margin: 0; box-shadow: none; } }
  @media (max-width: 1160px) { .cert { transform: scale(calc(100vw / 1180px)); transform-origin: top left; } }
</style>
</head>
<body>
  <div class="bar">
    <span class="verified"><?php echo esc_html($t['verified']); ?> · <?php echo esc_html($c['code']); ?></span>
    <button class="btn" onclick="window.print()"><?php echo esc_html($t['print']); ?></button>
  </div>
  <div class="cert">
    <aside class="side">
      <img src="<?php echo esc_url($logo); ?>" alt="Akdemy — Cost Engineering Campus">
      <svg class="curve" viewBox="0 0 330 330" aria-hidden="true">
        <path d="M0 300 C 110 200, 190 140, 330 105" fill="none" stroke="#F5A011" stroke-width="4" opacity=".85"/>
        <path d="M0 325 C 110 225, 200 165, 330 135 L 330 75 C 190 110, 110 170, 0 275 Z" fill="#F5A011" opacity=".08"/>
      </svg>
      <div class="meta">
        <div class="k"><?php echo esc_html($t['date']); ?></div><div class="v"><?php echo esc_html($date); ?></div>
        <?php if ($c['score'] !== null) : ?>
          <div class="k"><?php echo esc_html($t['score']); ?></div><div class="v"><?php echo esc_html(round((float) $c['score'])); ?>%</div>
        <?php endif; ?>
        <div class="k"><?php echo esc_html($t['code']); ?></div><div class="v"><?php echo esc_html($c['code']); ?></div>
        <div class="v" style="font-size:11px;font-weight:500;color:#aaa;word-break:break-all;margin-top:6px;"><?php echo esc_html($url); ?></div>
      </div>
    </aside>
    <main class="main">
      <span class="pill">Akdemy · Cost Engineering Campus</span>
      <h1><?php echo esc_html($t['cert']); ?></h1>
      <p class="intro"><?php echo esc_html($t['intro']); ?></p>
      <div class="name"><?php echo esc_html($c['name']); ?></div>
      <p class="done"><?php echo esc_html($t['done']); ?></p>
      <div class="course"><?php echo esc_html($c['course_title']); ?></div>
      <div class="foot">
        <div class="sig">
          <div class="line"></div>
          <div class="n"><?php echo esc_html($signer); ?></div>
          <div class="r"><?php echo esc_html($t['role']); ?> · CAF Corporation</div>
        </div>
        <div class="badges"><span>AACE</span><span>PMI</span><span>KPEX</span></div>
      </div>
    </main>
  </div>
</body>
</html><?php
}

// ── Student list ───────────────────────────────────────────────────────────
add_shortcode('akdemy_my_certificates', function () {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Sign in to see your certificates.', 'akdemy') . '</p>';
    }
    $certs = array_filter((array) get_user_meta(get_current_user_id(), AKDEMY_CERT_META, true));
    if (!$certs) {
        return '<p>' . esc_html__('You have no certificates yet — pass a course final exam to earn one.', 'akdemy') . '</p>';
    }
    $out = '<div class="akdemy-insights-grid">';
    foreach ($certs as $c) {
        $out .= sprintf(
            '<a class="akdemy-insight-card" href="%s" target="_blank" rel="noopener"><div class="akdemy-insight-body"><div class="akdemy-insight-cat" style="color:#F5A011">Certificate · %s</div><div class="akdemy-insight-title">%s</div><div class="akdemy-insight-meta"><span>%s</span><span class="akdemy-insight-read">View →</span></div></div></a>',
            esc_url(akdemy_certificate_url($c['code'])),
            esc_html($c['code']),
            esc_html($c['course_title']),
            esc_html(date_i18n(get_option('date_format'), strtotime($c['issued_at'])))
        );
    }
    return $out . '</div>';
});

// ── WP-CLI: manual issue ────────────────────────────────────────────────────
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('akdemy certificate', function ($args) {
        [$user, $course] = $args;
        $u = is_numeric($user) ? get_userdata((int) $user) : get_user_by('login', $user);
        if (!$u || get_post_type((int) $course) !== 'lp_course') {
            WP_CLI::error('Usage: wp akdemy certificate <user id|login> <course id>');
        }
        $c = akdemy_issue_certificate($u->ID, (int) $course);
        WP_CLI::success("{$c['code']} → " . akdemy_certificate_url($c['code']));
    });
}
