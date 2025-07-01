<?php
/**
 * Plugin Name: BIAQuiz Core
 * Plugin URI: https://acme-biaquiz.com
 * Description: Plugin principal pour la gestion des quiz BIA - Custom Post Types, taxonomies et fonctionnalités de base
 * Version: 1.0.0
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
define('BIAQUIZ_CORE_VERSION', '1.0.0');
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
        $this->load_dependencies();
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-acf-integration.php';
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-admin.php';
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-import-export.php';
        require_once BIAQUIZ_CORE_PLUGIN_DIR . 'includes/class-quiz-handler.php';
    }
    
    /**
     * Initialisation du plugin
     */
    public function init() {
        // Initialiser les Custom Post Types
        BIAQuiz_Post_Types::init();
        
        // Initialiser les taxonomies
        BIAQuiz_Taxonomies::init();
        
        // Initialiser l'intégration ACF
        BIAQuiz_ACF_Integration::init();
        
        // Initialiser l'administration
        if (is_admin()) {
            BIAQuiz_Admin::init();
        }
        
        // Initialiser l'import/export
        BIAQuiz_Import_Export::init();
        
        // Initialiser le gestionnaire de quiz
        BIAQuiz_Handler::init();
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain('biaquiz-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables personnalisées si nécessaire
        $this->create_tables();
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Créer les catégories par défaut
        $this->create_default_categories();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    /**
     * Créer les tables personnalisées
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table pour les statistiques de quiz (optionnel)
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
    }
    
    /**
     * Créer les catégories par défaut
     */
    private function create_default_categories() {
        $categories = array(
            'aerodynamique' => array(
                'name' => 'Aérodynamique et mécanique du vol',
                'description' => 'Principes de vol, forces aérodynamiques, performances des aéronefs'
            ),
            'aeronefs' => array(
                'name' => 'Connaissance des aéronefs',
                'description' => 'Structure, systèmes, équipements et instruments de bord'
            ),
            'meteorologie' => array(
                'name' => 'Météorologie',
                'description' => 'Phénomènes météorologiques, cartes et prévisions'
            ),
            'navigation' => array(
                'name' => 'Navigation, règlementation et sécurité des vols',
                'description' => 'Navigation aérienne, réglementation et procédures de sécurité'
            ),
            'histoire' => array(
                'name' => 'Histoire de l\'aéronautique et de l\'espace',
                'description' => 'Grands moments de l\'aviation et de la conquête spatiale'
            ),
            'anglais' => array(
                'name' => 'Anglais aéronautique',
                'description' => 'Vocabulaire et phraséologie aéronautique en anglais'
            )
        );
        
        foreach ($categories as $slug => $category) {
            if (!term_exists($slug, 'quiz_category')) {
                wp_insert_term(
                    $category['name'],
                    'quiz_category',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
            }
        }
    }
}

// Initialiser le plugin
function biaquiz_core_init() {
    return BIAQuiz_Core::get_instance();
}

// Démarrer le plugin
biaquiz_core_init();

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
 * Obtenir les catégories de quiz
 */
function biaquiz_get_categories() {
    return get_terms(array(
        'taxonomy' => 'quiz_category',
        'hide_empty' => false,
        'orderby' => 'term_order',
        'order' => 'ASC'
    ));
}

/**
 * Obtenir les quiz d'une catégorie
 */
function biaquiz_get_quizzes_by_category($category_slug) {
    $args = array(
        'post_type' => 'biaquiz',
        'posts_per_page' => -1,
        'post_status' => 'publish',
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
    $questions = get_field('questions', $quiz_id);
    if (!$questions || !is_array($questions)) {
        return array();
    }
    
    return $questions;
}

/**
 * Enregistrer une statistique de quiz
 */
function biaquiz_save_quiz_stat($quiz_id, $score, $total_questions, $time_taken = 0) {
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

