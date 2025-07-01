<?php
/**
 * Plugin Name: BIAQuiz Core
 * Plugin URI: https://acme-biaquiz.com
 * Description: Plugin principal pour la gestion des quiz BIA - Custom Post Types, taxonomies et fonctionnalités de base
 * Version: 1.0.1
 * Author: ACME
 * License: GPL v2 or later
 * Text Domain: biaquiz-core
 * Domain Path: /languages
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('BIAQUIZ_CORE_VERSION', '1.0.1');
define('BIAQUIZ_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIAQUIZ_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BIAQUIZ_CORE_PLUGIN_FILE', __FILE__);

/**
 * Classe principale du plugin BIAQuiz Core
 */
class BIAQuiz_Core {
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Indicateur si le plugin est initialisé
     */
    private $initialized = false;
    
    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        add_action('init', array($this, 'init'), 5);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Vérifier les dépendances
     */
    public function check_dependencies() {
        $missing_dependencies = array();
        
        // Vérifier ACF
        if (!class_exists('ACF') && !function_exists('get_field')) {
            $missing_dependencies[] = 'Advanced Custom Fields';
        }
        
        if (!empty($missing_dependencies)) {
            add_action('admin_notices', function() use ($missing_dependencies) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>BIAQuiz Core:</strong> ';
                echo sprintf(
                    __('Les plugins suivants sont requis: %s', 'biaquiz-core'),
                    implode(', ', $missing_dependencies)
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        $files = array(
            'includes/class-post-types.php',
            'includes/class-taxonomies.php',
            'includes/class-acf-integration.php',
            'includes/class-admin.php',
            'includes/class-import-export.php',
            'includes/class-quiz-handler.php'
        );
        
        foreach ($files as $file) {
            $file_path = BIAQUIZ_CORE_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("BIAQuiz Core: Fichier manquant - $file");
            }
        }
    }
    
    /**
     * Initialisation du plugin
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        // Vérifier les dépendances avant d'initialiser
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Charger les dépendances
        $this->load_dependencies();
        
        // Initialiser les composants
        $this->init_components();
        
        $this->initialized = true;
        
        // Hook pour permettre aux autres plugins d'étendre
        do_action('biaquiz_core_initialized');
    }
    
    /**
     * Initialiser les composants
     */
    private function init_components() {
        // Initialiser les Custom Post Types
        if (class_exists('BIAQuiz_Post_Types')) {
            BIAQuiz_Post_Types::init();
        }
        
        // Initialiser les taxonomies
        if (class_exists('BIAQuiz_Taxonomies')) {
            BIAQuiz_Taxonomies::init();
        }
        
        // Initialiser l'intégration ACF
        if (class_exists('BIAQuiz_ACF_Integration')) {
            BIAQuiz_ACF_Integration::init();
        }
        
        // Initialiser l'administration
        if (is_admin() && class_exists('BIAQuiz_Admin')) {
            BIAQuiz_Admin::init();
        }
        
        // Initialiser l'import/export
        if (class_exists('BIAQuiz_Import_Export')) {
            BIAQuiz_Import_Export::init();
        }
        
        // Initialiser le gestionnaire de quiz
        if (class_exists('BIAQuiz_Quiz_Handler')) {
            BIAQuiz_Quiz_Handler::init();
        }
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain('biaquiz-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Afficher les notices d'administration
     */
    public function admin_notices() {
        // Vérifier si ACF est installé mais pas activé
        if (file_exists(WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php') && !class_exists('ACF')) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>BIAQuiz Core:</strong> ';
            echo __('Advanced Custom Fields est installé mais pas activé. Veuillez l\'activer pour utiliser BIAQuiz.', 'biaquiz-core');
            echo '</p></div>';
        }
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Vérifier les dépendances lors de l'activation
        if (!$this->check_dependencies()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('BIAQuiz Core nécessite Advanced Custom Fields pour fonctionner.', 'biaquiz-core'),
                __('Erreur d\'activation', 'biaquiz-core'),
                array('back_link' => true)
            );
        }
        
        // Charger les dépendances pour l'activation
        $this->load_dependencies();
        
        // Initialiser les composants
        $this->init_components();
        
        // Créer les tables personnalisées si nécessaire
        $this->create_tables();
        
        // Créer les catégories par défaut
        $this->create_default_categories();
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Marquer que le plugin a été activé
        update_option('biaquiz_core_activated', true);
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Supprimer le marqueur d'activation
        delete_option('biaquiz_core_activated');
    }
    
    /**
     * Créer les tables personnalisées
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table pour les statistiques de quiz
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) NOT NULL,
            user_ip varchar(45) NOT NULL,
            score int(11) NOT NULL,
            total_questions int(11) NOT NULL,
            time_taken int(11) NOT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY user_ip (user_ip),
            KEY completed_at (completed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Vérifier si la table a été créée
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("BIAQuiz Core: Impossible de créer la table $table_name");
        }
    }
    
    /**
     * Créer les catégories par défaut
     */
    private function create_default_categories() {
        $categories = array(
            'aerodynamique' => array(
                'name' => 'Aérodynamique et mécanique du vol',
                'description' => 'Principes de vol, forces aérodynamiques, performances des aéronefs',
                'color' => '#3b82f6',
                'icon' => '✈️',
                'order' => 1
            ),
            'aeronefs' => array(
                'name' => 'Connaissance des aéronefs',
                'description' => 'Structure, systèmes, équipements et instruments de bord',
                'color' => '#f59e0b',
                'icon' => '🛩️',
                'order' => 2
            ),
            'meteorologie' => array(
                'name' => 'Météorologie',
                'description' => 'Phénomènes météorologiques, cartes et prévisions',
                'color' => '#06b6d4',
                'icon' => '🌤️',
                'order' => 3
            ),
            'navigation' => array(
                'name' => 'Navigation, règlementation et sécurité des vols',
                'description' => 'Navigation aérienne, réglementation et procédures de sécurité',
                'color' => '#ef4444',
                'icon' => '🧭',
                'order' => 4
            ),
            'histoire' => array(
                'name' => 'Histoire de l\'aéronautique et de l\'espace',
                'description' => 'Grands moments de l\'aviation et de la conquête spatiale',
                'color' => '#eab308',
                'icon' => '🚀',
                'order' => 5
            ),
            'anglais' => array(
                'name' => 'Anglais aéronautique',
                'description' => 'Vocabulaire et phraséologie aéronautique en anglais',
                'color' => '#8b5cf6',
                'icon' => '🗣️',
                'order' => 6
            )
        );
        
        foreach ($categories as $slug => $category) {
            if (!term_exists($slug, 'quiz_category')) {
                $term = wp_insert_term(
                    $category['name'],
                    'quiz_category',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
                
                if (!is_wp_error($term)) {
                    // Ajouter les métadonnées de la catégorie
                    update_term_meta($term['term_id'], 'category_color', $category['color']);
                    update_term_meta($term['term_id'], 'category_icon', $category['icon']);
                    update_term_meta($term['term_id'], 'category_order', $category['order']);
                }
            }
        }
    }
    
    /**
     * Vérifier si le plugin est correctement initialisé
     */
    public function is_initialized() {
        return $this->initialized;
    }
    
    /**
     * Obtenir la version du plugin
     */
    public function get_version() {
        return BIAQUIZ_CORE_VERSION;
    }
}

// Initialiser le plugin
function biaquiz_core_init() {
    return BIAQuiz_Core::get_instance();
}

// Démarrer le plugin
add_action('plugins_loaded', 'biaquiz_core_init', 0);

/**
 * Fonctions utilitaires globales
 */

/**
 * Obtenir l'instance du plugin
 */
function biaquiz_core() {
    return BIAQuiz_Core::get_instance();
}

/**
 * Vérifier si BIAQuiz est prêt
 */
function biaquiz_is_ready() {
    $core = biaquiz_core();
    return $core && $core->is_initialized();
}

/**
 * Obtenir les catégories de quiz
 */
function biaquiz_get_categories() {
    if (!biaquiz_is_ready()) {
        return array();
    }
    
    $terms = get_terms(array(
        'taxonomy' => 'quiz_category',
        'hide_empty' => false,
        'meta_key' => 'category_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    ));
    
    if (is_wp_error($terms)) {
        return array();
    }
    
    // Ajouter les métadonnées aux termes
    foreach ($terms as $term) {
        $term->color = get_term_meta($term->term_id, 'category_color', true) ?: '#3b82f6';
        $term->icon = get_term_meta($term->term_id, 'category_icon', true) ?: '📝';
        $term->order = get_term_meta($term->term_id, 'category_order', true) ?: 999;
    }
    
    return $terms;
}

/**
 * Obtenir les quiz d'une catégorie
 */
function biaquiz_get_quizzes_by_category($category_slug) {
    if (!biaquiz_is_ready()) {
        return array();
    }
    
    $args = array(
        'post_type' => 'biaquiz',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'quiz_active',
                'value' => '1',
                'compare' => '='
            )
        ),
        'tax_query' => array(
            array(
                'taxonomy' => 'quiz_category',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ),
        ),
        'meta_key' => 'quiz_number',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    );
    
    return get_posts($args);
}

/**
 * Obtenir les questions d'un quiz
 */
function biaquiz_get_quiz_questions($quiz_id) {
    if (!biaquiz_is_ready() || !function_exists('get_field')) {
        return array();
    }
    
    $questions = get_field('questions', $quiz_id);
    if (!$questions || !is_array($questions)) {
        return array();
    }
    
    return $questions;
}

/**
 * Obtenir un quiz avec toutes ses données
 */
function biaquiz_get_quiz_data($quiz_id) {
    if (!biaquiz_is_ready()) {
        return false;
    }
    
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'biaquiz' || $quiz->post_status !== 'publish') {
        return false;
    }
    
    // Vérifier si le quiz est actif
    $is_active = get_post_meta($quiz_id, 'quiz_active', true);
    if ($is_active !== '1') {
        return false;
    }
    
    $questions = biaquiz_get_quiz_questions($quiz_id);
    if (empty($questions)) {
        return false;
    }
    
    return array(
        'id' => $quiz_id,
        'title' => $quiz->post_title,
        'description' => $quiz->post_content,
        'excerpt' => $quiz->post_excerpt,
        'questions' => $questions,
        'difficulty' => get_post_meta($quiz_id, 'quiz_difficulty', true) ?: 'moyen',
        'time_limit' => get_post_meta($quiz_id, 'quiz_time_limit', true) ?: 0,
        'number' => get_post_meta($quiz_id, 'quiz_number', true) ?: 1,
        'category' => wp_get_post_terms($quiz_id, 'quiz_category'),
        'settings' => function_exists('get_field') ? get_field('quiz_settings', $quiz_id) : array()
    );
}

/**
 * Enregistrer une statistique de quiz
 */
function biaquiz_save_quiz_stat($quiz_id, $score, $total_questions, $time_taken = 0) {
    if (!biaquiz_is_ready()) {
        return false;
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'biaquiz_stats';
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    return $wpdb->insert(
        $table_name,
        array(
            'quiz_id' => $quiz_id,
            'user_ip' => $user_ip,
            'score' => $score,
            'total_questions' => $total_questions,
            'time_taken' => $time_taken,
            'completed_at' => current_time('mysql')
        ),
        array('%d', '%s', '%d', '%d', '%d', '%s')
    );
}

/**
 * Obtenir les statistiques d'un quiz
 */
function biaquiz_get_quiz_stats($quiz_id) {
    if (!biaquiz_is_ready()) {
        return false;
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'biaquiz_stats';
    
    return $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            AVG(score) as avg_score,
            MAX(score) as max_score,
            MIN(score) as min_score,
            AVG(time_taken) as avg_time
        FROM $table_name 
        WHERE quiz_id = %d
    ", $quiz_id));
}

/**
 * Hook d'activation différée pour s'assurer que tout est prêt
 */
add_action('wp_loaded', function() {
    if (get_option('biaquiz_core_activated')) {
        // Vérifier que tout est en place
        if (biaquiz_is_ready()) {
            // Tout est OK, supprimer le marqueur
            delete_option('biaquiz_core_activated');
        }
    }
});


