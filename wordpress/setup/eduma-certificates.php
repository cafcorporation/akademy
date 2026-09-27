<?php
/**
 * Akdemy certificates in the official LearnPress Certificates add-on (bundled with Eduma).
 * Design approved 2026-09-24: certificates/certificate.html → cert-bg-{es,en}.png (1684×1191).
 * Canvas 842×595; background = image layer at scale .5; dynamic layers at the approved coordinates (÷2).
 * Assigns ES template to the ES course and EN to the EN course (course meta `_lp_cert`).
 * Run: powershell -File scripts\wp-eduma.ps1 eval-file wordpress\setup\eduma-certificates.php
 */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) $admins[0]);

$dir = 'C:/Users/CarlosFuenmayor/Akdemy-AI/certificates/';

function akdemy_cert_media(string $file): int
{
    $hash = md5_file($file);
    $ids  = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1,
                       'fields' => 'ids', 'meta_key' => '_akdemy_media_hash', 'meta_value' => $hash]);
    if ($ids) {
        return (int) $ids[0];
    }
    $tmp = wp_tempnam(basename($file));
    copy($file, $tmp);
    $id = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], 0);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    update_post_meta($id, '_akdemy_media_hash', $hash);
    return (int) $id;
}

/** Common Fabric.js object props used by the add-on. */
function akdemy_layer_base(array $extra): array
{
    return array_merge([
        'version' => '6.9.0', 'originX' => 'left', 'originY' => 'top',
        'fill' => 'rgb(0,0,0)', 'stroke' => null, 'strokeWidth' => 0, 'strokeDashArray' => null,
        'strokeLineCap' => 'butt', 'strokeDashOffset' => 0, 'strokeLineJoin' => 'miter', 'strokeUniform' => false,
        'strokeMiterLimit' => 4, 'scaleX' => 1, 'scaleY' => 1, 'angle' => 0, 'flipX' => false, 'flipY' => false,
        'opacity' => 1, 'shadow' => null, 'visible' => true, 'backgroundColor' => '', 'fillRule' => 'nonzero',
        'paintFirst' => 'fill', 'globalCompositeOperation' => 'source-over', 'skewX' => 0, 'skewY' => 0,
    ], $extra);
}

function akdemy_text_layer(string $id, string $placeholder, float $left, float $top, float $width, int $size, string $weight, string $color, float $lh = 1.1): array
{
    return akdemy_layer_base([
        'id' => $id, 'type_layer' => 'text-static', 'type' => 'Textbox', 'name' => "[$placeholder]", 'text' => "[$placeholder]",
        'left' => $left, 'top' => $top, 'width' => $width, 'height' => $size * $lh, 'fill' => $color,
        'fontSize' => $size, 'fontWeight' => $weight, 'fontFamily' => 'Inter', 'fontStyle' => 'normal',
        'lineHeight' => $lh, 'charSpacing' => 0, 'textAlign' => 'left', 'styles' => [],
        'underline' => false, 'overline' => false, 'linethrough' => false, 'textBackgroundColor' => '',
        'direction' => 'ltr', 'minWidth' => 20, 'splitByGrapheme' => false,
    ]);
}

$editions = [
    'es' => ['title' => 'Akdemy — Certificado de finalización (ES)', 'date_format' => 'j \d\e F \d\e Y'],
    'en' => ['title' => 'Akdemy — Certificate of Completion (EN)',  'date_format' => 'F j, Y'],
];

foreach ($editions as $lang => $cfg) {
    $bg_id  = akdemy_cert_media($dir . "cert-bg-$lang.png");
    $bg_url = wp_get_attachment_url($bg_id);

    // Coordinates = approved preview (certificate.html .dyn, 1684×1191) ÷ 2.
    $layers = [
        akdemy_layer_base([
            'id' => "akdemy_bg_$lang", 'type_layer' => 'image', 'type' => 'Image', 'name' => 'Background',
            'originX' => 'center', 'originY' => 'center', 'left' => 421, 'top' => 297.5,
            'width' => 1684, 'height' => 1191, 'scaleX' => 0.5, 'scaleY' => 0.5,
            'src' => $bg_url, 'crossOrigin' => 'anonymous', 'filters' => [], 'cropX' => 0, 'cropY' => 0,
            'selectable' => false,
        ]),
        akdemy_text_layer("akdemy_name_$lang",   'STUDENT_NAME',    295, 202.5, 500, 40, '900', '#1a1a1a', 1.1),
        akdemy_text_layer("akdemy_course_$lang", 'COURSE_TITLE',    295, 307.5, 500, 20, '800', '#1a1a1a', 1.25),
        akdemy_text_layer("akdemy_date_$lang",   'COURSE_END_DATE', 30,  319,   200, 13, '700', '#ffffff', 1.2)
            + ['formatDate' => $cfg['date_format']],
        akdemy_layer_base([
            'id' => "akdemy_qr_$lang", 'type_layer' => 'qr_code', 'type' => 'Image', 'name' => 'QR Code',
            'left' => 37.5, 'top' => 387.5, 'width' => 150, 'height' => 150, 'scaleX' => 0.5333, 'scaleY' => 0.5333,
            'src' => '{{CERT_ASSETS_URL}}/images/qrcode-default.png', 'crossOrigin' => 'anonymous',
            'cropX' => 0, 'cropY' => 0, 'qr_size' => 80,
        ]),
    ];
    $design = ['width' => 842, 'height' => 595, 'background' => '#ffffff', 'layers' => $layers];

    $existing = get_posts(['post_type' => 'lp_cert', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
                           'meta_key' => '_akdemy_cert_lang', 'meta_value' => $lang]);
    $data = ['post_type' => 'lp_cert', 'post_status' => 'publish', 'post_title' => $cfg['title']];
    $cert_id = $existing ? wp_update_post(['ID' => $existing[0]] + $data) : wp_insert_post($data);

    update_post_meta($cert_id, '_akdemy_cert_lang', $lang);
    update_post_meta($cert_id, '_lp_layer', wp_slash(wp_json_encode($design, JSON_UNESCAPED_UNICODE)));
    update_post_meta($cert_id, '_lp_cert_template', str_replace(home_url(), '', $bg_url));
    update_post_meta($cert_id, '_lp_cert_thumbnail', str_replace(home_url(), '', $bg_url));
    if (class_exists('LearnPress\Certificates\Models\CertificatePostModel')) {
        $m = \LearnPress\Certificates\Models\CertificatePostModel::find($cert_id, true);
        $m && $m->clean_caches();
    }

    // Assign to the course of the same language edition.
    $courses = get_posts(['post_type' => 'lp_course', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
                          'meta_key' => '_akdemy_lang', 'meta_value' => $lang]);
    foreach ($courses as $course_id) {
        update_post_meta($course_id, '_lp_cert', $cert_id);
        wp_update_post(['ID' => $course_id]); // let LearnPress refresh its course cache
    }
    echo "$lang: certificate #$cert_id → courses " . implode(',', $courses) . "\n";
}
