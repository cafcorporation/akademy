<?php
/**
 * Publishes a built course package (build/<key>.json) into LearnPress
 * through LearnPress's own abilities (learnpress/create-*), so every
 * cache and relation is maintained exactly as LearnPress expects.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Akdemy_Importer
{
    /** Post meta that tags everything the importer creates with the package key. */
    const META_KEY = '_akdemy_key';

    /** @var callable */
    private $log;

    public function __construct(callable $log)
    {
        $this->log = $log;
    }

    public function import(array $pkg): int
    {
        $key = $pkg['key'];
        $this->remove($key);

        $course = $pkg['course'];
        if (!empty($pkg['cover_file'])) {
            $course['featured_image_id'] = $this->cover_attachment($pkg['cover_file']);
        }
        $course_id = $this->ability('learnpress/create-course', $course, 'course_id');
        update_post_meta($course_id, self::META_KEY, $key);
        update_post_meta($course_id, '_akdemy_lang', $pkg['lang'] ?? 'en');
        ($this->log)("Course #$course_id: {$pkg['course']['title']}");
        $last_quiz = ['id' => 0, 'grade' => 70];

        foreach ($pkg['sections'] as $section) {
            $section_id = $this->ability('learnpress/create-section', [
                'course_id' => $course_id,
                'name'      => $section['name'],
                'order'     => $section['order'],
            ], 'section_id');

            foreach ($section['items'] as $order => $item) {
                if ($item['kind'] === 'lesson') {
                    $id = $this->ability('learnpress/create-lesson', [
                        'course_id'  => $course_id,
                        'section_id' => $section_id,
                        'title'      => $item['title'],
                        'content'    => $this->localize_images($item['content']),
                        'duration'   => $item['duration'],
                        'preview'    => $item['preview'],
                        'status'     => 'publish',
                        'order'      => $order + 1,
                    ], 'lesson_id');
                    $this->apply_video($id, $item['video_url'] ?? '');
                } else {
                    $id = $this->ability('learnpress/create-quiz', [
                        'course_id'           => $course_id,
                        'section_id'          => $section_id,
                        'title'               => $item['title'],
                        'duration'            => $item['duration'],
                        'passing_grade'       => $item['passing_grade'],
                        'show_correct_review' => true,
                        'status'              => 'publish',
                        'order'               => $order + 1,
                    ], 'quiz_id');
                    $last_quiz = ['id' => $id, 'grade' => (int) $item['passing_grade']];
                    foreach ($item['questions'] as $q) {
                        $qid = $this->ability('learnpress/add-quiz-question', ['quiz_id' => $id] + $q, 'question_id');
                        update_post_meta($qid, self::META_KEY, $key);
                    }
                }
                update_post_meta($id, self::META_KEY, $key);
                // Clean URL: "1.1 · Qué hace…" → "1-1-que-hace…" (the middle dot would be %c2%b7).
                wp_update_post([
                    'ID'        => $id,
                    'post_name' => wp_unique_post_slug(
                        sanitize_title(remove_accents(str_replace('·', ' ', $item['title']))),
                        $id, 'publish', get_post_type($id), 0
                    ),
                ]);
            }
            ($this->log)("  {$section['name']} (" . count($section['items']) . ' items)');
        }

        // Pass = final exam (last quiz) at its passing grade → triggers the Akdemy certificate.
        if ($last_quiz['id']) {
            update_post_meta($course_id, '_lp_course_result', 'evaluate_final_quiz');
            update_post_meta($course_id, '_lp_final_quiz', $last_quiz['id']);
            update_post_meta($course_id, '_lp_passing_condition', $last_quiz['grade']);
            ($this->log)("Evaluation: final quiz #{$last_quiz['id']} ≥ {$last_quiz['grade']}%");
        }

        return $course_id;
    }

    /**
     * Updates the published course in place — page fields (overview, excerpt, title), lesson text and
     * video, quiz wording — keeping its IDs, enrolments and certificate link (see update_items()).
     */
    public function update_course(array $pkg): int
    {
        $ids = get_posts([
            'post_type'   => 'lp_course',
            'post_status' => 'any',
            'numberposts' => 1,
            'fields'      => 'ids',
            'meta_key'    => self::META_KEY,
            'meta_value'  => $pkg['key'],
        ]);
        if (!$ids) {
            throw new RuntimeException("No published course for {$pkg['key']} — run akdemy import first.");
        }
        $course_id = (int) $ids[0];
        $result = wp_update_post([
            'ID'           => $course_id,
            'post_title'   => $pkg['course']['title'],
            'post_excerpt' => $pkg['course']['excerpt'],
            'post_content' => $this->localize_images($pkg['course']['description']),
        ], true);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        ($this->log)("Course #$course_id updated: {$pkg['course']['title']}");
        $this->update_items($pkg);
        return $course_id;
    }

    /**
     * Refreshes lesson text and quiz question wording in place. Lessons are matched by their ref
     * ("0.1 · …"), quizzes by title and questions by position, so only IDs-preserving edits apply:
     * adding/removing lessons or questions, or changing answer options, still needs a full import.
     */
    private function update_items(array $pkg): void
    {
        global $wpdb;
        $posts = fn (string $type) => get_posts([
            'post_type' => $type, 'post_status' => 'any', 'numberposts' => -1,
            'meta_key'  => self::META_KEY, 'meta_value' => $pkg['key'],
        ]);
        $lessons = $posts('lp_lesson');
        $quizzes = $posts('lp_quiz');
        $n_lessons = $n_questions = 0;

        foreach ($pkg['sections'] as $section) {
            foreach ($section['items'] as $item) {
                if ($item['kind'] === 'lesson') {
                    $prefix = $item['ref'] . ' ·';
                    $match = array_values(array_filter($lessons, fn ($p) => str_starts_with($p->post_title, $prefix)));
                    if (!$match) {
                        ($this->log)("  ! lesson {$item['ref']} not found — needs a full import");
                        continue;
                    }
                    wp_update_post([
                        'ID'           => $match[0]->ID,
                        'post_title'   => $item['title'],
                        'post_content' => $this->localize_images($item['content']),
                    ]);
                    $this->apply_video($match[0]->ID, $item['video_url'] ?? '');
                    $n_lessons++;
                    continue;
                }
                $match = array_values(array_filter($quizzes, fn ($p) => $p->post_title === $item['title']));
                if (!$match) {
                    ($this->log)("  ! quiz \"{$item['title']}\" not found — needs a full import");
                    continue;
                }
                $qids = $wpdb->get_col($wpdb->prepare(
                    "SELECT question_id FROM {$wpdb->prefix}learnpress_quiz_questions WHERE quiz_id = %d ORDER BY question_order",
                    $match[0]->ID
                ));
                if (count($qids) !== count($item['questions'])) {
                    ($this->log)("  ! quiz \"{$item['title']}\": " . count($qids) . ' questions published vs ' . count($item['questions']) . ' built — needs a full import');
                    continue;
                }
                foreach ($item['questions'] as $i => $q) {
                    wp_update_post(['ID' => (int) $qids[$i], 'post_title' => $q['title']]);
                    update_post_meta((int) $qids[$i], '_lp_explanation', $q['explanation'] ?? '');
                    $n_questions++;
                }
            }
        }
        ($this->log)("Updated in place: $n_lessons lessons, $n_questions quiz questions");
    }

    /** Permanently removes a previous import of the same package (only posts the importer tagged). */
    public function remove(string $key): void
    {
        global $wpdb;
        $ids = get_posts([
            'post_type'   => ['lp_course', 'lp_lesson', 'lp_quiz', 'lp_question'],
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_key'    => self::META_KEY,
            'meta_value'  => $key,
        ]);
        if (!$ids) {
            return;
        }
        $courses = array_filter($ids, fn ($id) => get_post_type($id) === 'lp_course');
        foreach ($courses as $course_id) {
            $sections = $wpdb->get_col($wpdb->prepare(
                "SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d",
                $course_id
            ));
            if ($sections) {
                $in = implode(',', array_map('intval', $sections));
                $wpdb->query("DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE section_id IN ($in)");
                $wpdb->query("DELETE FROM {$wpdb->prefix}learnpress_sections WHERE section_id IN ($in)");
            }
            $wpdb->delete("{$wpdb->prefix}learnpress_courses", ['ID' => $course_id]);
        }
        foreach ($ids as $id) {
            if (get_post_type($id) === 'lp_quiz') {
                $wpdb->delete("{$wpdb->prefix}learnpress_quiz_questions", ['quiz_id' => $id]);
            }
            if (get_post_type($id) === 'lp_question') {
                $wpdb->delete("{$wpdb->prefix}learnpress_question_answers", ['question_id' => $id]);
            }
            wp_delete_post($id, true);
        }
        ($this->log)('Removed previous import: ' . count($ids) . ' items');
    }

    /**
     * Optional lesson video (front matter `video_url`) → Eduma's lesson "Media" field, shown above the text.
     * Removing `video_url` removes the video only if the importer put it there; a video added by hand in wp-admin stays.
     */
    private function apply_video(int $lesson_id, string $url): void
    {
        if ($url !== '') {
            update_post_meta($lesson_id, '_lp_lesson_video_intro', esc_url_raw($url));
            update_post_meta($lesson_id, '_akdemy_video_managed', 1);
        } elseif (get_post_meta($lesson_id, '_akdemy_video_managed', true)) {
            delete_post_meta($lesson_id, '_lp_lesson_video_intro');
            delete_post_meta($lesson_id, '_akdemy_video_managed');
        }
    }

    /** Replaces akdemy-local:<path> image sources (from the build) with uploaded media URLs. */
    private function localize_images(string $html): string
    {
        return preg_replace_callback('/src="akdemy-local:([^"]+)"/', function ($m) {
            $url = is_readable($m[1]) ? wp_get_attachment_url($this->cover_attachment($m[1])) : '';
            return 'src="' . esc_url($url) . '"';
        }, $html);
    }

    /** Uploads a file once (keyed by content hash); re-imports reuse the attachment while unchanged. */
    private function cover_attachment(string $file): int
    {
        if (!is_readable($file)) {
            throw new RuntimeException("Cover not found: $file");
        }
        $hash = md5_file($file);
        $existing = get_posts([
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => 1,
            'fields'      => 'ids',
            'meta_key'    => '_akdemy_cover_hash',
            'meta_value'  => $hash,
        ]);
        if ($existing) {
            return (int) $existing[0];
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam(basename($file));
        copy($file, $tmp);
        $id = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], 0);
        if (is_wp_error($id)) {
            @unlink($tmp);
            throw new RuntimeException('Cover upload: ' . $id->get_error_message());
        }
        update_post_meta($id, '_akdemy_cover_hash', $hash);
        ($this->log)("Cover uploaded: #$id " . basename($file));
        return (int) $id;
    }

    private function ability(string $name, array $input, string $id_field): int
    {
        $ability = wp_get_ability($name);
        if (!$ability) {
            throw new RuntimeException("Ability $name not available — enable LearnPress > Settings > Advanced > MCP.");
        }
        $result = $ability->execute($input);
        if (is_wp_error($result)) {
            throw new RuntimeException("$name: " . $result->get_error_message());
        }
        $id = $this->find_id($result, $id_field);
        if (!$id) {
            throw new RuntimeException("$name: no $id_field in response " . wp_json_encode($result));
        }
        return $id;
    }

    /** LearnPress wraps responses differently per tool; look for the id anywhere in the result. */
    private function find_id($result, string $field): int
    {
        if (is_array($result)) {
            if (isset($result[$field]) && is_numeric($result[$field])) {
                return (int) $result[$field];
            }
            foreach ($result as $value) {
                if (is_array($value) && ($id = $this->find_id($value, $field))) {
                    return $id;
                }
            }
        }
        return 0;
    }
}
