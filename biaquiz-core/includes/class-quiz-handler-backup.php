<?php
/**
 * Gestionnaire de quiz pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les quiz côté serveur
 */
class BIAQuiz_Handler {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('wp_ajax_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
        add_action('wp_ajax_nopriv_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
        add_action('wp_ajax_save_score', array(__CLASS__, 'ajax_save_score'));
        add_action('wp_ajax_nopriv_save_score', array(__CLASS__, 'ajax_save_score'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_filter('the_content', array(__CLASS__, 'filter_quiz_content'));
    }
    
    /**
     * Enregistrer les scripts
     */
    public static function enqueue_scripts() {
        if (is_singular('biaquiz') || is_tax('quiz_category') || is_front_page()) {
            // Localiser les variables pour JavaScript
            wp_localize_script('acme-biaquiz-quiz', 'acme_biaquiz_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('acme_biaquiz_nonce'),
                'home_url' => home_url('/'),
                'strings' => array(
                    'loading' => __('Chargement...', 'biaquiz-core'),
                    'error' => __('Une erreur est survenue', 'biaquiz-core'),
                    'correct' => __('Correct !', 'biaquiz-core'),
                    'incorrect' => __('Incorrect', 'biaquiz-core'),
                    'quiz_completed' => __('Quiz terminé !', 'biaquiz-core'),
                    'perfect_score' => __('Score parfait !', 'biaquiz-core'),
                    'retry_incorrect' => __('Réessayez les questions incorrectes', 'biaquiz-core'),
                )
            ));
        }
    }
    
    /**
     * AJAX pour obtenir un quiz
     */
    public static function ajax_get_quiz() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'acme_biaquiz_nonce')) {
            wp_die(__('Erreur de sécurité', 'biaquiz-core'));
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        if (!$quiz_id) {
            wp_send_json_error(__('ID de quiz invalide', 'biaquiz-core'));
        }
        
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'biaquiz' || $quiz->post_status !== 'publish') {
            wp_send_json_error(__('Quiz non trouvé', 'biaquiz-core'));
        }
        
        // Vérifier si le quiz est actif
        $quiz_active = get_post_meta($quiz_id, 'quiz_active', true);
        if ($quiz_active === '0') {
            wp_send_json_error(__('Ce quiz n\'est pas disponible actuellement', 'biaquiz-core'));
        }
        
        // Obtenir les questions validées
        $questions = BIAQuiz_ACF_Integration::get_validated_questions($quiz_id);
        if (!$questions) {
            wp_send_json_error(__('Ce quiz ne contient pas de questions valides', 'biaquiz-core'));
        }
        
        // Obtenir les paramètres du quiz
        $settings = BIAQuiz_ACF_Integration::get_quiz_settings($quiz_id);
        
        // Préparer les données de réponse
        $response_data = array(
            'title' => $quiz->post_title,
            'description' => $quiz->post_excerpt,
            'questions' => $questions,
            'settings' => $settings,
            'quiz_meta' => array(
                'difficulty' => get_post_meta($quiz_id, 'quiz_difficulty', true),
                'time_limit' => get_post_meta($quiz_id, 'quiz_time_limit', true),
                'number' => get_post_meta($quiz_id, 'quiz_number', true)
            )
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX pour sauvegarder le score
     */
    public static function ajax_save_score() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'acme_biaquiz_nonce')) {
            wp_die(__('Erreur de sécurité', 'biaquiz-core'));
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $score = intval($_POST['score']);
        $total = intval($_POST['total']);
        $time = intval($_POST['time']);
        
        if (!$quiz_id || $score < 0 || $total <= 0 || $score > $total) {
            wp_send_json_error(__('Données invalides', 'biaquiz-core'));
        }
        
        // Sauvegarder les statistiques
        $saved = biaquiz_save_quiz_stat($quiz_id, $score, $total, $time);
        
        if ($saved !== false) {
            wp_send_json_success(array(
                'message' => __('Score enregistré avec succès', 'biaquiz-core'),
                'score' => $score,
                'total' => $total,
                'percentage' => round(($score / $total) * 100, 1)
            ));
        } else {
            wp_send_json_error(__('Erreur lors de l\'enregistrement du score', 'biaquiz-core'));
        }
    }
    
    /**
     * Filtrer le contenu des quiz pour ajouter l'interface
     */
    public static function filter_quiz_content($content) {
        if (!is_singular('biaquiz')) {
            return $content;
        }
        
        global $post;
        
        // Vérifier si le quiz est actif
        $quiz_active = get_post_meta($post->ID, 'quiz_active', true);
        if ($quiz_active === '0') {
            return $content . '<div class="quiz-inactive"><p>' . __('Ce quiz n\'est pas disponible actuellement.', 'biaquiz-core') . '</p></div>';
        }
        
        // Vérifier s'il y a des erreurs de validation
        $validation_errors = get_post_meta($post->ID, '_biaquiz_validation_errors', true);
        if ($validation_errors && current_user_can('edit_post', $post->ID)) {
            $content .= '<div class="quiz-validation-errors" style="background: #ffebee; border: 1px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">';
            $content .= $validation_errors;
            $content .= '</div>';
        }
        
        // Obtenir les informations du quiz
        $quiz_number = get_post_meta($post->ID, 'quiz_number', true);
        $quiz_difficulty = get_post_meta($post->ID, 'quiz_difficulty', true);
        $quiz_time_limit = get_post_meta($post->ID, 'quiz_time_limit', true);
        
        // Obtenir la catégorie
        $categories = get_the_terms($post->ID, 'quiz_category');
        $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
        
        // Obtenir les statistiques
        $stats = self::get_quiz_statistics($post->ID);
        
        // Construire l'interface du quiz
        $quiz_interface = '<div class="quiz-container" data-quiz-id="' . $post->ID . '">';
        
        // Informations du quiz
        $quiz_interface .= '<div class="quiz-info-panel">';
        $quiz_interface .= '<div class="quiz-meta">';
        
        if ($category) {
            $category_meta = BIAQuiz_Taxonomies::get_category_meta($category->term_id);
            $quiz_interface .= '<div class="quiz-category">';
            $quiz_interface .= '<span class="category-icon">' . ($category_meta['icon'] ?: '📚') . '</span>';
            $quiz_interface .= '<span class="category-name">' . esc_html($category->name) . '</span>';
            $quiz_interface .= '</div>';
        }
        
        if ($quiz_number) {
            $quiz_interface .= '<div class="quiz-number">Quiz n°' . $quiz_number . '</div>';
        }
        
        if ($quiz_difficulty) {
            $difficulty_labels = array(
                'facile' => array('label' => 'Facile', 'color' => '#10b981'),
                'moyen' => array('label' => 'Moyen', 'color' => '#f59e0b'),
                'difficile' => array('label' => 'Difficile', 'color' => '#ef4444')
            );
            
            if (isset($difficulty_labels[$quiz_difficulty])) {
                $diff = $difficulty_labels[$quiz_difficulty];
                $quiz_interface .= '<div class="quiz-difficulty" style="color: ' . $diff['color'] . ';">';
                $quiz_interface .= '<span>●</span> ' . $diff['label'];
                $quiz_interface .= '</div>';
            }
        }
        
        if ($quiz_time_limit && $quiz_time_limit > 0) {
            $quiz_interface .= '<div class="quiz-time-limit">⏱️ ' . $quiz_time_limit . ' minutes</div>';
        }
        
        $quiz_interface .= '</div>'; // .quiz-meta
        
        // Statistiques
        if ($stats['total_attempts'] > 0) {
            $quiz_interface .= '<div class="quiz-stats">';
            $quiz_interface .= '<h4>Statistiques</h4>';
            $quiz_interface .= '<div class="stats-grid">';
            $quiz_interface .= '<div class="stat-item">';
            $quiz_interface .= '<span class="stat-value">' . number_format($stats['total_attempts']) . '</span>';
            $quiz_interface .= '<span class="stat-label">Tentatives</span>';
            $quiz_interface .= '</div>';
            $quiz_interface .= '<div class="stat-item">';
            $quiz_interface .= '<span class="stat-value">' . number_format($stats['avg_score'], 1) . '/20</span>';
            $quiz_interface .= '<span class="stat-label">Score moyen</span>';
            $quiz_interface .= '</div>';
            $quiz_interface .= '<div class="stat-item">';
            $quiz_interface .= '<span class="stat-value">' . $stats['success_rate'] . '%</span>';
            $quiz_interface .= '<span class="stat-label">Réussite</span>';
            $quiz_interface .= '</div>';
            $quiz_interface .= '</div>'; // .stats-grid
            $quiz_interface .= '</div>'; // .quiz-stats
        }
        
        $quiz_interface .= '</div>'; // .quiz-info-panel
        
        // Bouton de démarrage
        $quiz_interface .= '<div class="quiz-start">';
        $quiz_interface .= '<button class="btn btn-primary start-quiz-btn" data-quiz-id="' . $post->ID . '">';
        $quiz_interface .= '🚀 Commencer le quiz';
        $quiz_interface .= '</button>';
        $quiz_interface .= '<p class="quiz-instructions">';
        $quiz_interface .= '20 questions • Une seule bonne réponse par question • Répétition des erreurs jusqu\'au score parfait';
        $quiz_interface .= '</p>';
        $quiz_interface .= '</div>';
        
        $quiz_interface .= '</div>'; // .quiz-container
        
        return $content . $quiz_interface;
    }
    
    /**
     * Obtenir les statistiques d'un quiz
     */
    public static function get_quiz_statistics($quiz_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                MAX(score) as max_score,
                MIN(score) as min_score,
                AVG(time_taken) as avg_time,
                COUNT(CASE WHEN score = total_questions THEN 1 END) as perfect_scores
            FROM $table_name 
            WHERE quiz_id = %d
        ", $quiz_id));
        
        if (!$stats || $stats->total_attempts == 0) {
            return array(
                'total_attempts' => 0,
                'avg_score' => 0,
                'max_score' => 0,
                'min_score' => 0,
                'avg_time' => 0,
                'success_rate' => 0,
                'perfect_scores' => 0
            );
        }
        
        $success_rate = $stats->total_attempts > 0 ? round(($stats->perfect_scores / $stats->total_attempts) * 100, 1) : 0;
        
        return array(
            'total_attempts' => intval($stats->total_attempts),
            'avg_score' => floatval($stats->avg_score),
            'max_score' => intval($stats->max_score),
            'min_score' => intval($stats->min_score),
            'avg_time' => intval($stats->avg_time),
            'success_rate' => $success_rate,
            'perfect_scores' => intval($stats->perfect_scores)
        );
    }
    
    /**
     * Obtenir les quiz d'une catégorie avec leurs statistiques
     */
    public static function get_category_quizzes_with_stats($category_slug) {
        $quizzes = biaquiz_get_quizzes_by_category($category_slug);
        
        foreach ($quizzes as &$quiz) {
            $quiz->stats = self::get_quiz_statistics($quiz->ID);
            $quiz->meta = array(
                'number' => get_post_meta($quiz->ID, 'quiz_number', true),
                'difficulty' => get_post_meta($quiz->ID, 'quiz_difficulty', true),
                'time_limit' => get_post_meta($quiz->ID, 'quiz_time_limit', true),
                'active' => get_post_meta($quiz->ID, 'quiz_active', true) !== '0'
            );
        }
        
        return $quizzes;
    }
    
    /**
     * Obtenir le classement des meilleurs scores
     */
    public static function get_leaderboard($quiz_id = null, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $where_clause = $quiz_id ? $wpdb->prepare("WHERE quiz_id = %d", $quiz_id) : "";
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                quiz_id,
                score,
                total_questions,
                time_taken,
                completed_at,
                ROUND((score / total_questions) * 100, 1) as percentage
            FROM $table_name 
            $where_clause
            ORDER BY score DESC, time_taken ASC
            LIMIT %d
        ", $limit));
        
        return $results ?: array();
    }
}

