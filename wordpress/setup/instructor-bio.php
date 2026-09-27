<?php
/**
 * Instructor biographies (source of truth). Run: scripts\wp-eduma.ps1 eval-file wordpress/setup/instructor-bio.php
 * - English goes in the standard WordPress "Biographical Info" (user meta `description`).
 * - Spanish goes in `akdemy_bio_es`; includes/course-labels.php shows it on Spanish courses.
 * Source: Carlos's LinkedIn profile (experience section, read 2026-09-26) + his AACE AEP status as he stated it.
 */

$bios = [
    'Carlos Fuenmayor' => [
        'en' => "Carlos Fuenmayor is a cost engineer with more than 25 years of experience estimating capital projects in energy and industry, and an instructor certified twice by AACE International as an Approved Education Provider (AEP).\n\n"
              . "He has led Class 5 to Class 2 estimates for PDVSA, WorleyParsons, Eni, KBR and Fichtner, among others, on onshore and offshore oil & gas, LNG and FLNG, refining, petrochemicals, industrial decarbonisation and renewable energy projects, in more than ten countries across the Americas, Europe, Africa, the Middle East and Central Asia. He is currently Lead Cost Estimator at Fichtner in Germany.\n\n"
              . "Teaching has run through his whole career: at Eni he was the in-house focal point and trainer for IHS QUE\$TOR and Aspen Capital Cost Estimator, training new and junior estimators; he has taught cost estimating seminars and mentors estimating teams. At CAF Corporation he created Kpex, the cost intelligence platform this course teaches.",
        'es' => "Carlos Fuenmayor es ingeniero de costos con más de 25 años de experiencia estimando proyectos de capital en energía e industria, e instructor certificado dos veces por AACE International como Approved Education Provider (AEP).\n\n"
              . "Ha dirigido estimados de la Clase 5 a la Clase 2 para PDVSA, WorleyParsons, Eni, KBR y Fichtner, entre otros, en proyectos de petróleo y gas onshore y offshore, GNL y FLNG, refinación, petroquímica, descarbonización industrial y energías renovables, en más de diez países de América, Europa, África, Oriente Medio y Asia Central. Actualmente es Lead Cost Estimator en Fichtner, en Alemania.\n\n"
              . "La formación ha acompañado toda su carrera: en Eni fue el referente interno y formador de IHS QUE\$TOR y Aspen Capital Cost Estimator para estimadores nuevos y junior; ha impartido seminarios de estimación de costos y mentoriza equipos de estimación. En CAF Corporation creó Kpex, la plataforma de inteligencia de costos que enseña este curso.",
    ],
];

foreach ($bios as $name => $bio) {
    $user = get_users(['search' => $name, 'search_columns' => ['display_name'], 'number' => 1])[0] ?? null;
    if (!$user) {
        WP_CLI::warning("User not found: $name");
        continue;
    }
    update_user_meta($user->ID, 'description', $bio['en']);
    update_user_meta($user->ID, 'akdemy_bio_es', $bio['es']);
    WP_CLI::success("Bio updated for #{$user->ID} $name (EN + ES)");
}
