<?php
/**
 * Akdemy Course Manual — an editable, branded deliverable per course+language.
 *
 * A `akdemy_manual` post holds the manual body (edited in the WordPress block editor).
 * It is linked to a LearnPress course; "Seed from course" fills the body from the course's
 * published lessons so authors don't rewrite content. A swappable cover is chosen from a small
 * bank of branded covers. "Generate PDF" opens a print-ready A4 view (browser → Save as PDF),
 * the archived deliverable handed over when the course ends.
 *
 * Storage (post meta): _akdemy_manual_course (lp_course id), _akdemy_manual_lang (es|en),
 *                      _akdemy_manual_subtitle, _akdemy_manual_cover (cover bank key).
 * Actions (admin-post, nonce + edit_post gated): akdemy_manual_seed, akdemy_manual_pdf.
 */

if (!defined('ABSPATH')) {
    exit;
}

const AKDEMY_MANUAL_CPT = 'akdemy_manual';

/** The cover bank. Each key → label + optional background image (relative to the plugin). */
function akdemy_manual_covers(): array
{
    return [
        'curve'           => ['label' => 'Curva de marca (sin foto)',  'bg' => null],
        'classroom-dark'  => ['label' => 'Aula · oscuro',               'bg' => 'assets/backgrounds/02-classroom-dark.jpg'],
        'classroom-light' => ['label' => 'Aula · claro',                'bg' => 'assets/backgrounds/01-classroom-light.jpg'],
        'window'          => ['label' => 'Ventanal',                    'bg' => 'assets/backgrounds/03-classroom-window.jpg'],
        'hero'            => ['label' => 'Hero del curso',              'bg' => 'assets/hero-course.jpg'],
    ];
}

function akdemy_manual_lang(int $manual_id): string
{
    return get_post_meta($manual_id, '_akdemy_manual_lang', true) === 'es' ? 'es' : 'en';
}

// ── CPT ──────────────────────────────────────────────────────────────────────
add_action('init', function () {
    register_post_type(AKDEMY_MANUAL_CPT, [
        'labels' => [
            'name'          => 'Course Manuals',
            'singular_name' => 'Course Manual',
            'add_new_item'  => 'Add New Manual',
            'edit_item'     => 'Edit Manual',
            'menu_name'     => 'Course Manuals',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'show_in_rest'  => true, // block editor
        'menu_icon'     => 'dashicons-book-alt',
        'menu_position' => 26,
        'supports'      => ['title', 'editor', 'revisions'],
        'capability_type' => 'post',
    ]);
});

// ── Settings metabox (course link, language, subtitle, cover) ─────────────────
add_action('add_meta_boxes', function () {
    add_meta_box('akdemy_manual_settings', 'Manual settings', 'akdemy_manual_settings_box', AKDEMY_MANUAL_CPT, 'side', 'high');
    add_meta_box('akdemy_manual_actions', 'Produce', 'akdemy_manual_actions_box', AKDEMY_MANUAL_CPT, 'side', 'high');
});

function akdemy_manual_settings_box(WP_Post $post): void
{
    wp_nonce_field('akdemy_manual_save', 'akdemy_manual_nonce');
    $course_id = (int) get_post_meta($post->ID, '_akdemy_manual_course', true);
    $lang      = akdemy_manual_lang($post->ID);
    $subtitle  = (string) get_post_meta($post->ID, '_akdemy_manual_subtitle', true);
    $cover     = get_post_meta($post->ID, '_akdemy_manual_cover', true) ?: 'curve';
    $courses   = get_posts([
        'post_type' => 'lp_course', 'post_status' => ['publish', 'draft'],
        'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC',
    ]);
    ?>
    <p><label for="akdemy_manual_course"><strong>Course</strong></label><br>
    <select name="akdemy_manual_course" id="akdemy_manual_course" style="width:100%">
        <option value="0"><?php esc_html_e('— none —', 'akdemy'); ?></option>
        <?php foreach ($courses as $c) :
            $clang = get_post_meta($c->ID, '_akdemy_lang', true) ?: 'en'; ?>
            <option value="<?php echo (int) $c->ID; ?>" data-lang="<?php echo esc_attr($clang); ?>" <?php selected($course_id, $c->ID); ?>>
                <?php echo esc_html($c->post_title . ' (' . $clang . ')'); ?>
            </option>
        <?php endforeach; ?>
    </select></p>

    <p><label for="akdemy_manual_lang"><strong>Language</strong></label><br>
    <select name="akdemy_manual_lang" id="akdemy_manual_lang" style="width:100%">
        <option value="en" <?php selected($lang, 'en'); ?>>English</option>
        <option value="es" <?php selected($lang, 'es'); ?>>Español</option>
    </select></p>

    <p><label for="akdemy_manual_subtitle"><strong>Subtitle</strong> <span style="color:#888">(cover)</span></label><br>
    <input type="text" name="akdemy_manual_subtitle" id="akdemy_manual_subtitle" value="<?php echo esc_attr($subtitle); ?>" style="width:100%"></p>

    <p><strong><?php esc_html_e('Cover', 'akdemy'); ?></strong></p>
    <div class="akdemy-cover-bank">
        <?php foreach (akdemy_manual_covers() as $key => $cfg) :
            $thumb = $cfg['bg'] ? plugins_url($cfg['bg'], AKDEMY_BRIDGE_FILE) : ''; ?>
            <label class="akdemy-cover-opt<?php echo $cover === $key ? ' is-on' : ''; ?>">
                <input type="radio" name="akdemy_manual_cover" value="<?php echo esc_attr($key); ?>" <?php checked($cover, $key); ?>>
                <span class="akdemy-cover-thumb" <?php echo $thumb ? 'style="background-image:url(\'' . esc_url($thumb) . '\')"' : 'data-curve="1"'; ?>></span>
                <span class="akdemy-cover-name"><?php echo esc_html($cfg['label']); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function akdemy_manual_actions_box(WP_Post $post): void
{
    $course_id = (int) get_post_meta($post->ID, '_akdemy_manual_course', true);
    $seed_url  = wp_nonce_url(admin_url('admin-post.php?action=akdemy_manual_seed&id=' . $post->ID), 'akdemy_manual_seed_' . $post->ID);
    $pdf_url   = wp_nonce_url(admin_url('admin-post.php?action=akdemy_manual_pdf&id=' . $post->ID), 'akdemy_manual_pdf_' . $post->ID);
    ?>
    <p>
        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="width:100%;text-align:center;box-sizing:border-box">
            🖨️ <?php esc_html_e('Generate PDF', 'akdemy'); ?>
        </a>
    </p>
    <p>
        <a href="<?php echo esc_url($seed_url); ?>" class="button" style="width:100%;text-align:center;box-sizing:border-box"
           onclick="return confirm('<?php echo esc_js(__('This replaces the current body with the course lessons. Continue?', 'akdemy')); ?>');">
            ⤵️ <?php esc_html_e('Seed from course', 'akdemy'); ?>
        </a>
    </p>
    <?php if (!$course_id) : ?>
        <p style="color:#b32d2e;margin:6px 0 0"><?php esc_html_e('Link a course first, then save, then seed.', 'akdemy'); ?></p>
    <?php else : ?>
        <p style="color:#888;margin:6px 0 0;font-size:11px"><?php esc_html_e('Seeding overwrites edits. Generate PDF opens a print-ready A4 page.', 'akdemy'); ?></p>
    <?php endif; ?>
    <?php
}

// ── Save ──────────────────────────────────────────────────────────────────────
add_action('save_post_' . AKDEMY_MANUAL_CPT, function (int $post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['akdemy_manual_nonce']) || !wp_verify_nonce(sanitize_key($_POST['akdemy_manual_nonce']), 'akdemy_manual_save')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, '_akdemy_manual_course', (int) ($_POST['akdemy_manual_course'] ?? 0));
    update_post_meta($post_id, '_akdemy_manual_lang', ($_POST['akdemy_manual_lang'] ?? 'en') === 'es' ? 'es' : 'en');
    update_post_meta($post_id, '_akdemy_manual_subtitle', sanitize_text_field(wp_unslash($_POST['akdemy_manual_subtitle'] ?? '')));
    $cover = sanitize_key($_POST['akdemy_manual_cover'] ?? 'curve');
    update_post_meta($post_id, '_akdemy_manual_cover', array_key_exists($cover, akdemy_manual_covers()) ? $cover : 'curve');
});

// ── Admin styles (cover picker) on the manual edit screen ─────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true) || get_post_type() !== AKDEMY_MANUAL_CPT) {
        return;
    }
    $css = __DIR__ . '/../assets/akdemy-manual.css';
    if (is_readable($css)) {
        wp_enqueue_style('akdemy-manual', plugins_url('assets/akdemy-manual.css', AKDEMY_BRIDGE_FILE), [], (string) filemtime($css));
    }
});

// ── Seed the body from the linked course's published lessons ──────────────────
add_action('admin_post_akdemy_manual_seed', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id || !current_user_can('edit_post', $id) || !check_admin_referer('akdemy_manual_seed_' . $id)) {
        wp_die('Not allowed');
    }
    $course_id = (int) get_post_meta($id, '_akdemy_manual_course', true);
    if (!$course_id) {
        wp_safe_redirect(add_query_arg('akdemy_manual_msg', 'nocourse', get_edit_post_link($id, 'url')));
        exit;
    }
    $body = akdemy_manual_build_body($course_id);
    wp_update_post(['ID' => $id, 'post_content' => $body]);
    wp_safe_redirect(add_query_arg('akdemy_manual_msg', 'seeded', get_edit_post_link($id, 'url')));
    exit;
});

add_action('admin_notices', function () {
    if (get_post_type() !== AKDEMY_MANUAL_CPT || empty($_GET['akdemy_manual_msg'])) {
        return;
    }
    $msg = sanitize_key($_GET['akdemy_manual_msg']);
    $map = [
        'seeded'   => ['updated', __('Manual seeded from the course lessons.', 'akdemy')],
        'nocourse' => ['error', __('Link a course and save before seeding.', 'akdemy')],
    ];
    if (isset($map[$msg])) {
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($map[$msg][0] === 'error' ? 'error' : 'success'), esc_html($map[$msg][1]));
    }
});

/**
 * Reads the course curriculum (sections → lessons, in order) straight from LearnPress tables
 * and returns block-editor markup: module = H2, lesson = H3, lesson body = an HTML block.
 */
function akdemy_manual_build_body(int $course_id): string
{
    global $wpdb;
    $sections = $wpdb->get_results($wpdb->prepare(
        "SELECT section_id, section_name FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d ORDER BY section_order ASC",
        $course_id
    ));
    $blocks = [];
    foreach ($sections as $s) {
        $blocks[] = akdemy_block_heading(2, $s->section_name);
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT item_id FROM {$wpdb->prefix}learnpress_section_items WHERE section_id = %d AND item_type = %s ORDER BY item_order ASC",
            (int) $s->section_id, 'lp_lesson'
        ));
        foreach ($items as $it) {
            $lesson = get_post((int) $it->item_id);
            if (!$lesson || $lesson->post_status === 'trash') {
                continue;
            }
            // Lesson titles are stored as "1.1 · Title"; keep the ref visible as the heading.
            $blocks[] = akdemy_block_heading(3, $lesson->post_title);
            $content = trim((string) $lesson->post_content);
            if ($content !== '') {
                $blocks[] = "<!-- wp:html -->\n" . $content . "\n<!-- /wp:html -->";
            }
        }
    }
    if (!$blocks) {
        return "<!-- wp:paragraph --><p>" . esc_html__('No lessons found for this course.', 'akdemy') . "</p><!-- /wp:paragraph -->";
    }
    return implode("\n\n", $blocks);
}

function akdemy_block_heading(int $level, string $text): string
{
    $tag = 'h' . $level;
    return sprintf("<!-- wp:heading {\"level\":%d} -->\n<%s>%s</%s>\n<!-- /wp:heading -->", $level, $tag, esc_html($text), $tag);
}

// ── Generate PDF (print-ready A4 view) ────────────────────────────────────────
add_action('admin_post_akdemy_manual_pdf', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id || !current_user_can('edit_post', $id) || !check_admin_referer('akdemy_manual_pdf_' . $id)) {
        wp_die('Not allowed');
    }
    akdemy_manual_render(get_post($id));
    exit;
});

function akdemy_manual_render(WP_Post $post): void
{
    $es        = akdemy_manual_lang($post->ID) === 'es';
    $subtitle  = (string) get_post_meta($post->ID, '_akdemy_manual_subtitle', true);
    $cover_key = get_post_meta($post->ID, '_akdemy_manual_cover', true) ?: 'curve';
    $covers    = akdemy_manual_covers();
    $cover     = $covers[$cover_key] ?? $covers['curve'];
    $logo      = plugins_url('assets/akdemy-logo-white.svg', AKDEMY_BRIDGE_FILE);
    $bg        = $cover['bg'] ? plugins_url($cover['bg'], AKDEMY_BRIDGE_FILE) : '';
    $t = $es
        ? ['official' => 'Curso oficial', 'print' => 'Descargar PDF / Imprimir', 'by' => 'por', 'page' => 'Página', 'of' => 'de', 'manual' => 'Manual del curso']
        : ['official' => 'Official course', 'print' => 'Download PDF / Print', 'by' => 'by', 'page' => 'Page', 'of' => 'of', 'manual' => 'Course manual'];

    // Render the block/HTML body to final HTML — block + shortcode + paragraph pipeline only, so
    // third-party the_content filters (LearnPress UI, sharing buttons, related content) can't leak in.
    $GLOBALS['post'] = $post;
    setup_postdata($post);
    $body = do_shortcode(wpautop(do_blocks($post->post_content)));
    wp_reset_postdata();

    // Cover curve SVG (used by the photo-less 'curve' option, and as a flourish on the dark side).
    $curveSvg = '<svg class="cv" viewBox="0 0 900 900" preserveAspectRatio="xMaxYMax slice" aria-hidden="true">'
        . '<path d="M60 820 C 260 520, 480 360, 880 250 L 880 170 C 470 290, 250 450, 60 760 Z" fill="#F5A011" opacity="0.10"/>'
        . '<path d="M60 790 C 260 490, 470 330, 880 210" fill="none" stroke="#F5A011" stroke-width="7" stroke-linecap="round"/>'
        . '<circle cx="520" cy="366" r="15" fill="#F5A011"/>'
        . '<circle cx="520" cy="366" r="29" fill="none" stroke="#F5A011" stroke-width="3" opacity="0.6"/></svg>';
    ?><!DOCTYPE html>
<html lang="<?php echo $es ? 'es' : 'en'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html(get_the_title($post) . ' — Akdemy'); ?></title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root { --o:#F5A011; --oh:#d4880a; --g:#585856; --ink:#1a1a1a; --cream:#F8F6F2; --line:#e8e4dc; }
  @page { size: A4; margin: 20mm 17mm 18mm;
    @bottom-left  { content: "Akdemy · CAF Corporation"; font: 600 7.5pt Inter, Arial; color: #8d8a84; }
    @bottom-right { content: "<?php echo esc_html($t['page']); ?> " counter(page) " <?php echo esc_html($t['of']); ?> " counter(pages); font: 600 7.5pt Inter, Arial; color: #8d8a84; } }
  @page cover { margin: 0; @bottom-left { content: none } @bottom-right { content: none } }
  * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  html, body { margin: 0; font-family: Inter, Calibri, Arial, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.6; }

  .bar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 10px; padding: 12px 16px; background: #ECEAE5; }
  .btn { background: var(--o); color: #fff; border: 0; border-radius: 10px; padding: 11px 22px; font: 800 14px Inter, sans-serif; cursor: pointer; }
  .btn:hover { background: var(--oh); }

  /* Cover */
  .cover { page: cover; position: relative; width: 210mm; height: 297mm; margin: 0 auto; color: #fff; overflow: hidden; break-after: page;
           background: #1a1a18; }
  .cover .photo { position: absolute; inset: 0; background-size: cover; background-position: center; }
  .cover .scrim { position: absolute; inset: 0;
           background: linear-gradient(115deg, rgba(20,20,18,.92) 0%, rgba(26,26,24,.78) 48%, rgba(26,26,24,.35) 100%); }
  .cover.curve { background: radial-gradient(120% 140% at 85% 20%, #585856 0%, #3F3F3D 70%); }
  .cover .cv { position: absolute; right: 0; bottom: 0; width: 150mm; height: 150mm; }
  .cover .band { position: absolute; left: 0; top: 0; bottom: 0; width: 5mm; background: var(--o); }
  .cover .in { position: absolute; inset: 24mm 22mm; display: flex; flex-direction: column; }
  .cover .logo { width: 70mm; }
  .cover .mid { margin-top: auto; }
  .cover .eyebrow { display: inline-block; font-size: 9pt; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;
           color: var(--o); border: 1px solid rgba(245,160,17,.6); border-radius: 30px; padding: 5px 16px; margin-bottom: 10mm; }
  .cover h1 { font-size: 40pt; line-height: 1.03; font-weight: 900; letter-spacing: -1.5px; margin: 0 0 6mm; max-width: 150mm; }
  .cover .sub { font-size: 15pt; color: #e6e3dc; font-weight: 500; max-width: 150mm; }
  .cover .base { margin-top: 12mm; padding-top: 6mm; border-top: 1px solid rgba(255,255,255,.2); font-size: 9pt; color: #cfcbc2; letter-spacing: .5px; }
  .cover .base b { color: #fff; }

  /* Body */
  .manual { max-width: 176mm; margin: 0 auto; padding: 4mm 0 10mm; }
  .manual h2 { font-size: 20pt; font-weight: 900; letter-spacing: -.6px; color: var(--ink); margin: 14mm 0 3mm;
               padding-bottom: 2.5mm; border-bottom: 2px solid var(--o); break-after: avoid; }
  .manual h2:first-child { margin-top: 2mm; }
  .manual h3 { font-size: 14pt; font-weight: 800; letter-spacing: -.3px; color: var(--oh); margin: 8mm 0 2mm; break-after: avoid; }
  .manual h4 { font-size: 11.5pt; font-weight: 800; color: var(--ink); margin: 5mm 0 1mm; }
  .manual p { margin: 0 0 2.6mm; }
  .manual ul, .manual ol { margin: 0 0 3mm 5mm; }
  .manual li { margin: 0 0 1mm; }
  .manual img { max-width: 100%; height: auto; border: 1px solid var(--line); border-radius: 6px; margin: 2mm 0; break-inside: avoid; }
  .manual table { width: 100%; border-collapse: collapse; margin: 3mm 0; font-size: 9.5pt; break-inside: avoid; }
  .manual th { background: var(--g); color: #fff; text-align: left; padding: 2mm 3mm; font-weight: 700; }
  .manual td { border-bottom: 1px solid var(--line); padding: 2mm 3mm; vertical-align: top; }
  .manual tr:nth-child(even) td { background: var(--cream); }
  .manual blockquote { border-left: 4px solid var(--o); background: var(--cream); margin: 3mm 0; padding: 2.5mm 4mm; color: #4a4a48; }
  .manual a { color: var(--oh); text-decoration: none; }
  .manual code { background: var(--cream); border: 1px solid var(--line); border-radius: 4px; padding: 0 4px; font-size: 9.5pt; }

  @media print { .bar { display: none; } .cover { margin: 0; } }
</style>
</head>
<body>
  <div class="bar"><button class="btn" onclick="window.print()"><?php echo esc_html($t['print']); ?></button></div>

  <div class="cover <?php echo $cover_key === 'curve' ? 'curve' : ''; ?>">
    <?php if ($bg) : ?>
      <div class="photo" style="background-image:url('<?php echo esc_url($bg); ?>')"></div>
      <div class="scrim"></div>
    <?php else : ?>
      <?php echo $curveSvg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
    <?php endif; ?>
    <div class="band"></div>
    <div class="in">
      <img class="logo" src="<?php echo esc_url($logo); ?>" alt="Akdemy — Cost Engineering Campus">
      <div class="mid">
        <span class="eyebrow"><?php echo esc_html($t['official']); ?> · <?php echo esc_html($t['manual']); ?></span>
        <h1><?php echo esc_html(get_the_title($post)); ?></h1>
        <?php if ($subtitle !== '') : ?><p class="sub"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
        <div class="base"><?php echo esc_html($t['by']); ?> <b>CAF Corporation</b> · Akdemy — Cost Engineering Campus</div>
      </div>
    </div>
  </div>

  <div class="manual"><?php echo wp_kses_post($body); ?></div>
</body>
</html><?php
}
