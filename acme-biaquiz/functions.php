<?php
/**
 * ACME BIAQuiz Theme Functions
 * 
 * @package ACME_BIAQuiz
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration du thème
 */
function acme_biaquiz_setup() {
    // Support des fonctionnalités WordPress
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Support des menus
    register_nav_menus(array(
        'primary' => __('Menu Principal', 'acme-biaquiz'),
        'footer' => __('Menu Pied de page', 'acme-biaquiz'),
    ));
}
add_action('after_setup_theme', 'acme_biaquiz_setup');

/**
 * Enregistrement des scripts et styles
 */
function acme_biaquiz_scripts() {
    // Style principal
    wp_enqueue_style('acme-biaquiz-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    
    // Script principal du quiz
    wp_enqueue_script('acme-biaquiz-quiz', get_template_directory_uri() . '/js/quiz.js', array('jquery'), '1.0.0', true);
    
    // Script pour le thème sombre/clair
    wp_enqueue_script('acme-biaquiz-theme', get_template_directory_uri() . '/js/theme-toggle.js', array('jquery'), '1.0.0', true);
    
    // Localisation pour AJAX
    wp_localize_script('acme-biaquiz-quiz', 'acme_biaquiz_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('acme_biaquiz_nonce'),
        'strings' => array(
            'loading' => __('Chargement...', 'acme-biaquiz'),
            'error' => __('Une erreur est survenue', 'acme-biaquiz'),
            'correct' => __('Correct !', 'acme-biaquiz'),
            'incorrect' => __('Incorrect', 'acme-biaquiz'),
            'quiz_completed' => __('Quiz terminé !', 'acme-biaquiz'),
            'perfect_score' => __('Score parfait !', 'acme-biaquiz'),
            'retry_incorrect' => __('Réessayez les questions incorrectes', 'acme-biaquiz'),
        )
    ));
}
add_action('wp_enqueue_scripts', 'acme_biaquiz_scripts');

/**
 * Enregistrement des zones de widgets
 */
function acme_biaquiz_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar Principal', 'acme-biaquiz'),
        'id'            => 'sidebar-1',
        'description'   => __('Zone de widgets principale', 'acme-biaquiz'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer', 'acme-biaquiz'),
        'id'            => 'footer-1',
        'description'   => __('Zone de widgets du pied de page', 'acme-biaquiz'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'acme_biaquiz_widgets_init');

/**
 * Personnalisation de l'admin
 */
function acme_biaquiz_admin_style() {
    wp_enqueue_style('acme-biaquiz-admin', get_template_directory_uri() . '/css/admin.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'acme_biaquiz_admin_style');

/**
 * Fonction pour obtenir les catégories de quiz
 */
function get_quiz_categories() {
    return array(
        'aerodynamique' => array(
            'name' => 'Aérodynamique et mécanique du vol',
            'description' => 'Principes de vol, forces aérodynamiques, performances des aéronefs',
            'icon' => '✈️'
        ),
        'aeronefs' => array(
            'name' => 'Connaissance des aéronefs',
            'description' => 'Structure, systèmes, équipements et instruments de bord',
            'icon' => '🛩️'
        ),
        'meteorologie' => array(
            'name' => 'Météorologie',
            'description' => 'Phénomènes météorologiques, cartes et prévisions',
            'icon' => '🌤️'
        ),
        'navigation' => array(
            'name' => 'Navigation, règlementation et sécurité des vols',
            'description' => 'Navigation aérienne, réglementation et procédures de sécurité',
            'icon' => '🧭'
        ),
        'histoire' => array(
            'name' => 'Histoire de l\'aéronautique et de l\'espace',
            'description' => 'Grands moments de l\'aviation et de la conquête spatiale',
            'icon' => '🚀'
        ),
        'anglais' => array(
            'name' => 'Anglais aéronautique',
            'description' => 'Vocabulaire et phraséologie aéronautique en anglais',
            'icon' => '🗣️'
        )
    );
}

/**
 * Fonction pour obtenir les quiz d'une catégorie
 */
function get_quizzes_by_category($category) {
    $args = array(
        'post_type' => 'biaquiz',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'quiz_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        ),
        'meta_key' => 'quiz_number',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    );
    
    return get_posts($args);
}

/**
 * Fonction pour obtenir les questions d'un quiz
 */
function get_quiz_questions($quiz_id) {
    $questions = get_field('questions', $quiz_id);
    if (!$questions) {
        return array();
    }
    
    // Mélanger les questions pour plus de variété
    shuffle($questions);
    
    return $questions;
}

/**
 * AJAX pour obtenir un quiz
 */
function acme_biaquiz_get_quiz() {
    check_ajax_referer('acme_biaquiz_nonce', 'nonce');
    
    $quiz_id = intval($_POST['quiz_id']);
    if (!$quiz_id) {
        wp_die('ID de quiz invalide');
    }
    
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'biaquiz') {
        wp_die('Quiz non trouvé');
    }
    
    $questions = get_quiz_questions($quiz_id);
    
    $response = array(
        'success' => true,
        'data' => array(
            'title' => $quiz->post_title,
            'questions' => $questions
        )
    );
    
    wp_send_json($response);
}
add_action('wp_ajax_get_quiz', 'acme_biaquiz_get_quiz');
add_action('wp_ajax_nopriv_get_quiz', 'acme_biaquiz_get_quiz');

/**
 * AJAX pour enregistrer le score
 */
function acme_biaquiz_save_score() {
    check_ajax_referer('acme_biaquiz_nonce', 'nonce');
    
    $quiz_id = intval($_POST['quiz_id']);
    $score = intval($_POST['score']);
    $total = intval($_POST['total']);
    
    // Ici on pourrait enregistrer les statistiques si nécessaire
    // Pour l'instant on retourne juste une confirmation
    
    $response = array(
        'success' => true,
        'message' => 'Score enregistré'
    );
    
    wp_send_json($response);
}
add_action('wp_ajax_save_score', 'acme_biaquiz_save_score');
add_action('wp_ajax_nopriv_save_score', 'acme_biaquiz_save_score');

/**
 * Fonction pour créer les répertoires nécessaires
 */
function acme_biaquiz_create_directories() {
    $upload_dir = wp_upload_dir();
    $biaquiz_dir = $upload_dir['basedir'] . '/biaquiz';
    
    if (!file_exists($biaquiz_dir)) {
        wp_mkdir_p($biaquiz_dir);
    }
    
    // Créer le répertoire pour les imports
    $import_dir = $biaquiz_dir . '/imports';
    if (!file_exists($import_dir)) {
        wp_mkdir_p($import_dir);
    }
}
add_action('init', 'acme_biaquiz_create_directories');

/**
 * Désactiver les commentaires sur les quiz
 */
function acme_biaquiz_disable_comments($open, $post_id) {
    $post = get_post($post_id);
    if ($post->post_type == 'biaquiz') {
        return false;
    }
    return $open;
}
add_filter('comments_open', 'acme_biaquiz_disable_comments', 10, 2);

/**
 * Personnaliser l'excerpt length
 */
function acme_biaquiz_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'acme_biaquiz_excerpt_length');

/**
 * Ajouter des classes CSS au body
 */
function acme_biaquiz_body_classes($classes) {
    if (is_singular('biaquiz')) {
        $classes[] = 'single-quiz';
    }
    
    if (is_tax('quiz_category')) {
        $classes[] = 'quiz-category-archive';
    }
    
    return $classes;
}
add_filter('body_class', 'acme_biaquiz_body_classes');

