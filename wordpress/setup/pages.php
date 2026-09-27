<?php
/**
 * Home + Insights pages; Home becomes the static front page (replaces the default "Hello world" blog).
 * Run: powershell -File scripts\wp.ps1 eval-file wordpress\setup\pages.php
 */
$courses_url = get_permalink((int) get_option('learn_press_courses_page_id'));

function akdemy_upsert_page(string $slug, string $title, string $content): int
{
    $page = get_page_by_path($slug);
    $data = [
        'post_type'    => 'page',
        'post_name'    => $slug,
        'post_title'   => $title,
        'post_status'  => 'publish',
        'post_content' => $content,
    ];
    return $page ? wp_update_post(['ID' => $page->ID] + $data) : wp_insert_post($data);
}

$insights_url = home_url('/insights/');

$home = <<<HTML
<!-- wp:html -->
<section class="akdemy-hero">
  <div class="akdemy-hero-inner">
    <div class="akdemy-eyebrow">Cost Engineering Campus · by CAF Corporation</div>
    <h1>Learn CAPEX estimating<br><span>the way it is really done.</span></h1>
    <p>Practical, AACE- and PMI-aligned courses built on Kpex — the cost intelligence platform of CAF Corporation. Available in English and Spanish.</p>
    <div class="akdemy-hero-ctas">
      <a class="akdemy-btn-primary" href="{$courses_url}">Browse courses →</a>
      <a class="akdemy-btn-ghost" href="{$insights_url}">Read Insights</a>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="akdemy-section">
  <div class="akdemy-section-head">
    <div class="akdemy-eyebrow">Courses</div>
    <h2>Start with the official Kpex course</h2>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[akdemy_courses]
<!-- /wp:shortcode -->

<!-- wp:html -->
<section class="akdemy-section">
  <div class="akdemy-section-head">
    <div class="akdemy-eyebrow">Insights</div>
    <h2>Latest from CAF Corporation</h2>
    <p>Methodology, benchmarks and cost intelligence — published on caf-corporation.com.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[akdemy_insights limit="3"]
<!-- /wp:shortcode -->
HTML;

$insights = <<<HTML
<!-- wp:html -->
<section class="akdemy-section akdemy-section-first">
  <div class="akdemy-section-head">
    <div class="akdemy-eyebrow">CAF Insights</div>
    <h2>Cost intelligence, from the Kpex team</h2>
    <p>Articles on AACE methodology, cost curves, location factors and escalation. Each one opens on caf-corporation.com.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[akdemy_insights limit="24" more="yes"]
<!-- /wp:shortcode -->
HTML;

$home_id     = akdemy_upsert_page('home', 'Home', $home);
$insights_id = akdemy_upsert_page('insights', 'Insights', $insights);

update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);

// Default sample content → trash (recoverable), not deleted.
foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $type) {
    $p = get_page_by_path($slug, OBJECT, $type);
    if ($p && $p->post_status !== 'trash') {
        wp_trash_post($p->ID);
        echo "trashed $type '$slug'\n";
    }
}
echo "home #$home_id (front page), insights #$insights_id\n";
