<?php
/**
 * Course-page wording.
 * - Everywhere: LearnPress/Eduma "Curriculum" becomes "Contenido del curso" / "Course content".
 * - Spanish courses: WordPress runs in es_ES there (locale.php) and LearnPress uses its official
 *   Spanish pack; Eduma, Thim and the site menu have no Spanish pack, so their labels are mapped here.
 */

defined('ABSPATH') || exit;

/** 'es' / 'en' for the course being viewed (course page or one of its lessons), null elsewhere. */
function akdemy_viewed_course_lang(): ?string
{
    if (function_exists('akdemy_request_is_spanish_course') && akdemy_request_is_spanish_course()) {
        return 'es'; // known from the URL, even before WP_Query
    }
    if (!did_action('wp')) {
        return null;
    }
    $id = get_queried_object_id();
    if ($id && get_post_type($id) !== 'lp_course' && function_exists('learn_press_get_course')) {
        $course = learn_press_get_course(); // lesson/quiz URLs live under their course
        $id = $course ? $course->get_id() : 0;
    }
    if (!$id || get_post_type($id) !== 'lp_course') {
        return null;
    }
    return get_post_meta($id, '_akdemy_lang', true) === 'es' ? 'es' : 'en';
}

function akdemy_course_label(string $translation, string $text): string
{
    static $both = [
        'Curriculum'        => ['es' => 'Contenido del curso', 'en' => 'Course content'],
        'Course Curriculum' => ['es' => 'Contenido del curso', 'en' => 'Course content'],
    ];
    // Spanish only: strings with no official es_ES pack (Eduma / Thim), plus wording choices over LearnPress's pack.
    static $es = [
        'Overview'        => 'Descripción',
        'Teacher'         => 'Instructor',
        'Instructor'      => 'Instructor',
        'Course Features' => 'Características del curso',
        'Lectures'        => 'Lecciones',
        'Lessons'         => 'Lecciones',
        'Quizzes'         => 'Cuestionarios',
        'Skill level'     => 'Nivel',
        'Students'        => 'Estudiantes',
        'Certificate'     => 'Certificado',
        'Assessments'     => 'Evaluaciones',
        'Self'            => 'Autoevaluación',
        'Yes'             => 'Sí',
        'No'              => 'No',
        'Duration'        => 'Duración',
        'Language'        => 'Idioma',
        'You May Like'    => 'También te puede interesar',
        'Enroll Now'      => 'Inscríbete ahora',
        'Buy Now'         => 'Comprar ahora',
        'Review'          => 'Reseña',
        'Reviews'         => 'Reseñas',
        'Share'           => 'Compartir',
        // Lesson page: curriculum toggle, comment form, login popup
        'Leave A Reply'        => 'Deja un comentario',
        'Comment *'            => 'Comentario *',
        'Name'                 => 'Nombre',
        'Email'                => 'Correo electrónico',
        'Website'              => 'Sitio web',
        'Post Comment'         => 'Publicar comentario',
        'Username or email'    => 'Usuario o correo electrónico',
        'Password'             => 'Contraseña',
        'Lost Password'        => '¿Olvidaste tu contraseña?',
        'Remember me'          => 'Recordarme',
        'Login'                => 'Iniciar sesión',
        'Register'             => 'Registrarse',
    ];
    $lang = akdemy_viewed_course_lang();
    if (!$lang) {
        return $translation;
    }
    if (isset($both[$text])) {
        return $both[$text][$lang];
    }
    return ($lang === 'es' && isset($es[$text])) ? $es[$text] : $translation;
}

foreach (['learnpress', 'eduma', 'thim-elementor-kit', 'thim-core'] as $domain) {
    add_filter("gettext_{$domain}", fn ($translation, $text) => akdemy_course_label($translation, $text), 20, 2);
}

// Instructor bio: Spanish courses show the Spanish version (user meta `akdemy_bio_es`, set by
// wordpress/setup/instructor-bio.php) in place of the English "Biographical Info".
add_filter('get_user_metadata', function ($value, $user_id, $key, $single) {
    if ($key !== 'description' || akdemy_viewed_course_lang() !== 'es') {
        return $value;
    }
    $es = get_user_meta($user_id, 'akdemy_bio_es', true);
    return $es === '' ? $value : ($single ? $es : [$es]);
}, 10, 4);

// LearnPress hard-codes this tooltip in HTML (lp-template-hooks.php), outside gettext.
add_action('wp_footer', function () {
    if (akdemy_viewed_course_lang() === 'es') {
        echo "<script>document.querySelectorAll('#sidebar-toggle').forEach(e=>e.title='Mostrar/ocultar contenido');</script>";
    }
});

// Site menu in Spanish inside Spanish courses.
add_filter('nav_menu_item_title', function ($title) {
    static $es = [
        'Home' => 'Inicio', 'Courses' => 'Cursos', 'Insights' => 'Insights',
        'Kpex Platform' => 'Plataforma Kpex', 'Who We Are' => 'Quiénes somos', 'Blog' => 'Blog',
    ];
    return (akdemy_viewed_course_lang() === 'es' && isset($es[$title])) ? $es[$title] : $title;
});
