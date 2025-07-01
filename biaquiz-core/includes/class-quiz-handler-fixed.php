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
 * Classe pour gérer les quiz et les interactions AJAX
 */
class BIAQuiz_Quiz_Handler {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        // Actions AJAX pour les utilisateurs connectés et non connectés
        add_action('wp_ajax_biaquiz_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
        add_action('wp_ajax_nopriv_biaquiz_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
        
        add_action('wp_ajax_biaquiz_submit_answer', array(__CLASS__, 'ajax_submit_answer'));
        add_action('wp_ajax_nopriv_biaquiz_submit_answer', array(__CLASS__, 'ajax_submit_answer'));
        
        add_action('wp_ajax_biaquiz_save_progress', array(__CLASS__, 'ajax_save_progress'));
        add_action('wp_ajax_nopriv_biaquiz_save_progress', array(__CLASS__, 'ajax_save_progress'));
        
        add_action('wp_ajax_biaquiz_complete_quiz', array(__CLASS__, 'ajax_complete_quiz'));
        add_action('wp_ajax_nopriv_biaquiz_complete_quiz', array(__CLASS__, 'ajax_complete_quiz'));
        
        // Enqueue des scripts
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('biaquiz_quiz', array(__CLASS__, 'quiz_shortcode'));
        add_shortcode('biaquiz_categories', array(__CLASS__, 'categories_shortcode'));
    }
    
    /**
     * Enqueue des scripts et styles
     */
    public static function enqueue_scripts() {
        // Enqueue seulement sur les pages de quiz
        if (is_singular('biaquiz') || is_tax('quiz_category') || has_shortcode(get_post()->post_content ?? '', 'biaquiz_quiz')) {
            wp_enqueue_script(
                'biaquiz-quiz',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/js/quiz.js',
                array('jquery'),
                BIAQUIZ_CORE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'biaquiz-quiz',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/css/quiz.css',
                array(),
                BIAQUIZ_CORE_VERSION
            );
            
            // Localiser le script avec les données AJAX
            wp_localize_script('biaquiz-quiz', 'biaquiz_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_nonce'),
                'strings' => array(
                    'loading' => __('Chargement...', 'biaquiz-core'),
                    'error' => __('Une erreur est survenue', 'biaquiz-core'),
                    'correct' => __('Correct !', 'biaquiz-core'),
                    'incorrect' => __('Incorrect', 'biaquiz-core'),
                    'next_question' => __('Question suivante', 'biaquiz-core'),
                    'finish_quiz' => __('Terminer le quiz', 'biaquiz-core'),
                    'quiz_completed' => __('Quiz terminé !', 'biaquiz-core'),
                    'score' => __('Score', 'biaquiz-core'),
                    'time' => __('Temps', 'biaquiz-core'),
                    'restart' => __('Recommencer', 'biaquiz-core'),
                    'back_to_categories' => __('Retour aux catégories', 'biaquiz-core')
                )
            ));
        }
    }
    
    /**
     * AJAX: Récupérer les données d'un quiz
     */
    public static function ajax_get_quiz() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'biaquiz_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'biaquiz-core')));
        }
        
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(array('message' => __('ID de quiz invalide', 'biaquiz-core')));
        }
        
        // Récupérer les données du quiz
        $quiz_data = biaquiz_get_quiz_data($quiz_id);
        
        if (!$quiz_data) {
            wp_send_json_error(array('message' => __('Quiz non trouvé ou inactif', 'biaquiz-core')));
        }
        
        // Préparer les questions pour le frontend
        $questions = array();
        foreach ($quiz_data['questions'] as $index => $question) {
            $answers = array();
            foreach ($question['answers'] as $answer_index => $answer) {
                $answers[] = array(
                    'id' => chr(65 + $answer_index), // A, B, C, D
                    'text' => $answer['answer_text']
                    // Ne pas envoyer is_correct au frontend pour la sécurité
                );
            }
            
            $questions[] = array(
                'id' => $index + 1,
                'text' => $question['question_text'],
                'image' => $question['question_image'] ?? null,
                'answers' => $answers
            );
        }
        
        $response_data = array(
            'id' => $quiz_data['id'],
            'title' => $quiz_data['title'],
            'description' => $quiz_data['description'],
            'difficulty' => $quiz_data['difficulty'],
            'time_limit' => $quiz_data['time_limit'],
            'total_questions' => count($questions),
            'questions' => $questions
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * AJAX: Soumettre une réponse
     */
    public static function ajax_submit_answer() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'biaquiz_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'biaquiz-core')));
        }
        
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $question_id = intval($_POST['question_id'] ?? 0);
        $answer_id = sanitize_text_field($_POST['answer_id'] ?? '');
        
        if (!$quiz_id || !$question_id || !$answer_id) {
            wp_send_json_error(array('message' => __('Données manquantes', 'biaquiz-core')));
        }
        
        // Récupérer les questions du quiz
        $questions = biaquiz_get_quiz_questions($quiz_id);
        
        if (!$questions || !isset($questions[$question_id - 1])) {
            wp_send_json_error(array('message' => __('Question non trouvée', 'biaquiz-core')));
        }
        
        $question = $questions[$question_id - 1];
        
        // Convertir l'ID de réponse (A, B, C, D) en index (0, 1, 2, 3)
        $answer_index = ord(strtoupper($answer_id)) - 65;
        
        if ($answer_index < 0 || $answer_index >= count($question['answers'])) {
            wp_send_json_error(array('message' => __('Réponse invalide', 'biaquiz-core')));
        }
        
        $selected_answer = $question['answers'][$answer_index];
        $is_correct = !empty($selected_answer['is_correct']);
        
        $response = array(
            'is_correct' => $is_correct,
            'explanation' => $question['question_explanation'] ?? '',
            'correct_answer' => null
        );
        
        // Si la réponse est incorrecte, indiquer la bonne réponse
        if (!$is_correct) {
            foreach ($question['answers'] as $index => $answer) {
                if (!empty($answer['is_correct'])) {
                    $response['correct_answer'] = chr(65 + $index);
                    break;
                }
            }
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Sauvegarder la progression
     */
    public static function ajax_save_progress() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'biaquiz_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'biaquiz-core')));
        }
        
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $current_question = intval($_POST['current_question'] ?? 0);
        $score = intval($_POST['score'] ?? 0);
        $time_elapsed = intval($_POST['time_elapsed'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(array('message' => __('ID de quiz invalide', 'biaquiz-core')));
        }
        
        // Sauvegarder dans la session ou cookie (pour les utilisateurs non connectés)
        $progress_data = array(
            'quiz_id' => $quiz_id,
            'current_question' => $current_question,
            'score' => $score,
            'time_elapsed' => $time_elapsed,
            'timestamp' => time()
        );
        
        // Utiliser les cookies pour la persistance
        setcookie(
            'biaquiz_progress_' . $quiz_id,
            json_encode($progress_data),
            time() + (24 * 60 * 60), // 24 heures
            '/'
        );
        
        wp_send_json_success(array('message' => __('Progression sauvegardée', 'biaquiz-core')));
    }
    
    /**
     * AJAX: Terminer un quiz
     */
    public static function ajax_complete_quiz() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'biaquiz_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'biaquiz-core')));
        }
        
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);
        $total_questions = intval($_POST['total_questions'] ?? 20);
        $time_taken = intval($_POST['time_taken'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(array('message' => __('ID de quiz invalide', 'biaquiz-core')));
        }
        
        // Sauvegarder les statistiques
        $stat_saved = biaquiz_save_quiz_stat($quiz_id, $score, $total_questions, $time_taken);
        
        // Supprimer la progression sauvegardée
        setcookie('biaquiz_progress_' . $quiz_id, '', time() - 3600, '/');
        
        $response = array(
            'message' => __('Quiz terminé avec succès', 'biaquiz-core'),
            'score' => $score,
            'total_questions' => $total_questions,
            'percentage' => round(($score / $total_questions) * 100, 1),
            'time_taken' => $time_taken,
            'stat_saved' => $stat_saved !== false
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Shortcode pour afficher un quiz
     */
    public static function quiz_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'category' => '',
            'number' => 0
        ), $atts);
        
        $quiz_id = intval($atts['id']);
        
        // Si pas d'ID, essayer de trouver par catégorie et numéro
        if (!$quiz_id && $atts['category'] && $atts['number']) {
            $quizzes = biaquiz_get_quizzes_by_category($atts['category']);
            foreach ($quizzes as $quiz) {
                $quiz_number = get_post_meta($quiz->ID, 'quiz_number', true);
                if ($quiz_number == $atts['number']) {
                    $quiz_id = $quiz->ID;
                    break;
                }
            }
        }
        
        if (!$quiz_id) {
            return '<p>' . __('Quiz non trouvé', 'biaquiz-core') . '</p>';
        }
        
        $quiz_data = biaquiz_get_quiz_data($quiz_id);
        if (!$quiz_data) {
            return '<p>' . __('Quiz non disponible', 'biaquiz-core') . '</p>';
        }
        
        ob_start();
        ?>
        <div id="biaquiz-container" class="biaquiz-quiz-container" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
            <div class="biaquiz-loading">
                <p><?php _e('Chargement du quiz...', 'biaquiz-core'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour afficher les catégories
     */
    public static function categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 3,
            'show_count' => true
        ), $atts);
        
        $categories = biaquiz_get_categories();
        
        if (empty($categories)) {
            return '<p>' . __('Aucune catégorie trouvée', 'biaquiz-core') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="biaquiz-categories-grid" style="grid-template-columns: repeat(<?php echo intval($atts['columns']); ?>, 1fr);">
            <?php foreach ($categories as $category) : 
                $quiz_count = 0;
                if ($atts['show_count']) {
                    $quizzes = biaquiz_get_quizzes_by_category($category->slug);
                    $quiz_count = count($quizzes);
                }
            ?>
                <div class="biaquiz-category-card" style="border-left-color: <?php echo esc_attr($category->color); ?>;">
                    <div class="category-icon"><?php echo esc_html($category->icon); ?></div>
                    <h3 class="category-title">
                        <a href="<?php echo get_term_link($category); ?>"><?php echo esc_html($category->name); ?></a>
                    </h3>
                    <p class="category-description"><?php echo esc_html($category->description); ?></p>
                    <?php if ($atts['show_count']) : ?>
                        <p class="category-count"><?php printf(_n('%d quiz', '%d quiz', $quiz_count, 'biaquiz-core'), $quiz_count); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Récupérer la progression sauvegardée d'un quiz
     */
    public static function get_saved_progress($quiz_id) {
        $cookie_name = 'biaquiz_progress_' . $quiz_id;
        
        if (isset($_COOKIE[$cookie_name])) {
            $progress = json_decode($_COOKIE[$cookie_name], true);
            
            // Vérifier que la progression n'est pas trop ancienne (24h)
            if ($progress && isset($progress['timestamp']) && (time() - $progress['timestamp']) < (24 * 60 * 60)) {
                return $progress;
            }
        }
        
        return null;
    }
    
    /**
     * Valider une réponse (méthode interne)
     */
    private static function validate_answer($quiz_id, $question_id, $answer_id) {
        $questions = biaquiz_get_quiz_questions($quiz_id);
        
        if (!$questions || !isset($questions[$question_id - 1])) {
            return false;
        }
        
        $question = $questions[$question_id - 1];
        $answer_index = ord(strtoupper($answer_id)) - 65;
        
        if ($answer_index < 0 || $answer_index >= count($question['answers'])) {
            return false;
        }
        
        return !empty($question['answers'][$answer_index]['is_correct']);
    }
    
    /**
     * Obtenir les statistiques globales
     */
    public static function get_global_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_attempts,
                COUNT(DISTINCT quiz_id) as total_quizzes,
                AVG(score) as avg_score,
                SUM(CASE WHEN score = total_questions THEN 1 ELSE 0 END) as perfect_scores
            FROM $table_name
        ");
    }
}

