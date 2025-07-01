<?php
/**
 * Intégration ACF pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer l'intégration ACF
 */
class BIAQuiz_ACF_Integration {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('acf/init', array(__CLASS__, 'register_fields'));
        add_filter('acf/settings/save_json', array(__CLASS__, 'acf_json_save_point'));
        add_filter('acf/settings/load_json', array(__CLASS__, 'acf_json_load_point'));
        add_action('acf/save_post', array(__CLASS__, 'validate_quiz_data'), 20);
        add_filter('acf/load_field/name=quiz_number', array(__CLASS__, 'load_quiz_number'));
    }
    
    /**
     * Enregistrer les champs ACF par code (fallback si JSON non disponible)
     */
    public static function register_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        // Vérifier si le groupe existe déjà (via JSON)
        if (acf_get_field_group('group_biaquiz_questions')) {
            return;
        }
        
        // Enregistrer le groupe de champs par code
        acf_add_local_field_group(array(
            'key' => 'group_biaquiz_questions',
            'title' => 'Questions du Quiz',
            'fields' => array(
                array(
                    'key' => 'field_questions',
                    'label' => 'Questions',
                    'name' => 'questions',
                    'type' => 'repeater',
                    'instructions' => 'Ajoutez les 20 questions du quiz. Chaque question doit avoir exactement 4 réponses avec une seule bonne réponse.',
                    'required' => 1,
                    'collapsed' => 'field_question_text',
                    'min' => 20,
                    'max' => 20,
                    'layout' => 'block',
                    'button_label' => 'Ajouter une question',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_question_text',
                            'label' => 'Question',
                            'name' => 'question_text',
                            'type' => 'textarea',
                            'required' => 1,
                            'rows' => 3,
                            'placeholder' => 'Saisissez votre question ici...',
                        ),
                        array(
                            'key' => 'field_question_image',
                            'label' => 'Image (optionnelle)',
                            'name' => 'question_image',
                            'type' => 'image',
                            'wrapper' => array('width' => '50'),
                            'return_format' => 'array',
                            'preview_size' => 'thumbnail',
                        ),
                        array(
                            'key' => 'field_question_explanation',
                            'label' => 'Explication',
                            'name' => 'question_explanation',
                            'type' => 'textarea',
                            'wrapper' => array('width' => '50'),
                            'rows' => 3,
                            'placeholder' => 'Explication de la réponse...',
                        ),
                        array(
                            'key' => 'field_answers',
                            'label' => 'Réponses',
                            'name' => 'answers',
                            'type' => 'repeater',
                            'required' => 1,
                            'min' => 4,
                            'max' => 4,
                            'layout' => 'table',
                            'button_label' => 'Ajouter une réponse',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_answer_text',
                                    'label' => 'Réponse',
                                    'name' => 'answer_text',
                                    'type' => 'text',
                                    'required' => 1,
                                    'wrapper' => array('width' => '70'),
                                    'placeholder' => 'Texte de la réponse...',
                                ),
                                array(
                                    'key' => 'field_answer_correct',
                                    'label' => 'Correct',
                                    'name' => 'is_correct',
                                    'type' => 'true_false',
                                    'wrapper' => array('width' => '30'),
                                    'message' => 'Bonne réponse',
                                    'ui' => 1,
                                    'ui_on_text' => 'Oui',
                                    'ui_off_text' => 'Non',
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_quiz_settings',
                    'label' => 'Paramètres du Quiz',
                    'name' => 'quiz_settings',
                    'type' => 'group',
                    'layout' => 'block',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_shuffle_questions',
                            'label' => 'Mélanger les questions',
                            'name' => 'shuffle_questions',
                            'type' => 'true_false',
                            'wrapper' => array('width' => '33'),
                            'default_value' => 1,
                            'ui' => 1,
                        ),
                        array(
                            'key' => 'field_shuffle_answers',
                            'label' => 'Mélanger les réponses',
                            'name' => 'shuffle_answers',
                            'type' => 'true_false',
                            'wrapper' => array('width' => '33'),
                            'default_value' => 1,
                            'ui' => 1,
                        ),
                        array(
                            'key' => 'field_show_explanations',
                            'label' => 'Afficher les explications',
                            'name' => 'show_explanations',
                            'type' => 'true_false',
                            'wrapper' => array('width' => '34'),
                            'default_value' => 1,
                            'ui' => 1,
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'biaquiz',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'active' => true,
        ));
    }
    
    /**
     * Définir le point de sauvegarde JSON pour ACF
     */
    public static function acf_json_save_point($path) {
        return BIAQUIZ_CORE_PLUGIN_DIR . 'acf-json';
    }
    
    /**
     * Définir le point de chargement JSON pour ACF
     */
    public static function acf_json_load_point($paths) {
        $paths[] = BIAQUIZ_CORE_PLUGIN_DIR . 'acf-json';
        return $paths;
    }
    
    /**
     * Valider les données du quiz lors de la sauvegarde
     */
    public static function validate_quiz_data($post_id) {
        // Vérifier si c'est un quiz
        if (get_post_type($post_id) !== 'biaquiz') {
            return;
        }
        
        // Récupérer les questions
        $questions = get_field('questions', $post_id);
        
        if (!$questions || !is_array($questions)) {
            return;
        }
        
        $errors = array();
        
        // Vérifier le nombre de questions
        if (count($questions) !== 20) {
            $errors[] = sprintf(__('Le quiz doit contenir exactement 20 questions. Actuellement: %d questions.', 'biaquiz-core'), count($questions));
        }
        
        // Vérifier chaque question
        foreach ($questions as $index => $question) {
            $question_num = $index + 1;
            
            // Vérifier le texte de la question
            if (empty($question['question_text'])) {
                $errors[] = sprintf(__('Question %d: Le texte de la question est requis.', 'biaquiz-core'), $question_num);
                continue;
            }
            
            // Vérifier les réponses
            if (!isset($question['answers']) || !is_array($question['answers'])) {
                $errors[] = sprintf(__('Question %d: Les réponses sont requises.', 'biaquiz-core'), $question_num);
                continue;
            }
            
            if (count($question['answers']) !== 4) {
                $errors[] = sprintf(__('Question %d: Exactement 4 réponses sont requises. Actuellement: %d réponses.', 'biaquiz-core'), $question_num, count($question['answers']));
                continue;
            }
            
            // Vérifier qu'il y a exactement une bonne réponse
            $correct_count = 0;
            $empty_answers = 0;
            
            foreach ($question['answers'] as $answer_index => $answer) {
                if (empty($answer['answer_text'])) {
                    $empty_answers++;
                }
                
                if (!empty($answer['is_correct'])) {
                    $correct_count++;
                }
            }
            
            if ($empty_answers > 0) {
                $errors[] = sprintf(__('Question %d: Toutes les réponses doivent avoir un texte.', 'biaquiz-core'), $question_num);
            }
            
            if ($correct_count === 0) {
                $errors[] = sprintf(__('Question %d: Au moins une réponse doit être marquée comme correcte.', 'biaquiz-core'), $question_num);
            } elseif ($correct_count > 1) {
                $errors[] = sprintf(__('Question %d: Une seule réponse peut être marquée comme correcte. Actuellement: %d réponses correctes.', 'biaquiz-core'), $question_num, $correct_count);
            }
        }
        
        // Afficher les erreurs si il y en a
        if (!empty($errors)) {
            $error_message = '<strong>' . __('Erreurs de validation du quiz:', 'biaquiz-core') . '</strong><br>';
            $error_message .= implode('<br>', $errors);
            
            // Stocker l'erreur pour l'afficher
            update_post_meta($post_id, '_biaquiz_validation_errors', $error_message);
            
            // Marquer le quiz comme inactif s'il y a des erreurs
            update_post_meta($post_id, 'quiz_active', '0');
        } else {
            // Supprimer les erreurs précédentes
            delete_post_meta($post_id, '_biaquiz_validation_errors');
            
            // Réactiver le quiz s'il était désactivé pour cause d'erreurs
            if (get_post_meta($post_id, 'quiz_active', true) === '0') {
                update_post_meta($post_id, 'quiz_active', '1');
            }
        }
    }
    
    /**
     * Charger automatiquement le numéro de quiz suivant
     */
    public static function load_quiz_number($field) {
        global $post;
        
        if (!$post || $post->post_type !== 'biaquiz') {
            return $field;
        }
        
        // Si c'est un nouveau quiz et qu'il n'y a pas de numéro
        if ($post->post_status === 'auto-draft' && empty(get_post_meta($post->ID, 'quiz_number', true))) {
            // Obtenir la catégorie si elle est définie
            $categories = wp_get_post_terms($post->ID, 'quiz_category');
            if (!empty($categories)) {
                $category_slug = $categories[0]->slug;
                $next_number = self::get_next_quiz_number($category_slug);
                $field['default_value'] = $next_number;
            }
        }
        
        return $field;
    }
    
    /**
     * Obtenir le prochain numéro de quiz pour une catégorie
     */
    private static function get_next_quiz_number($category_slug) {
        global $wpdb;
        
        $max_number = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(CAST(pm.meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE pm.meta_key = 'quiz_number'
            AND p.post_type = 'biaquiz'
            AND p.post_status = 'publish'
            AND t.slug = %s
        ", $category_slug));
        
        return ($max_number ? $max_number + 1 : 1);
    }
    
    /**
     * Obtenir les questions d'un quiz avec validation
     */
    public static function get_validated_questions($quiz_id) {
        $questions = get_field('questions', $quiz_id);
        
        if (!$questions || !is_array($questions) || count($questions) !== 20) {
            return false;
        }
        
        $validated_questions = array();
        
        foreach ($questions as $question) {
            // Vérifier la structure de la question
            if (empty($question['question_text']) || !isset($question['answers']) || !is_array($question['answers'])) {
                continue;
            }
            
            if (count($question['answers']) !== 4) {
                continue;
            }
            
            // Vérifier qu'il y a exactement une bonne réponse
            $correct_count = 0;
            $valid_answers = array();
            
            foreach ($question['answers'] as $answer) {
                if (empty($answer['answer_text'])) {
                    continue 2; // Passer à la question suivante
                }
                
                $valid_answers[] = array(
                    'text' => $answer['answer_text'],
                    'is_correct' => !empty($answer['is_correct'])
                );
                
                if (!empty($answer['is_correct'])) {
                    $correct_count++;
                }
            }
            
            if ($correct_count !== 1 || count($valid_answers) !== 4) {
                continue;
            }
            
            // Question valide
            $validated_questions[] = array(
                'question_text' => $question['question_text'],
                'question_image' => $question['question_image'] ?? null,
                'question_explanation' => $question['question_explanation'] ?? '',
                'answers' => $valid_answers
            );
        }
        
        return count($validated_questions) === 20 ? $validated_questions : false;
    }
    
    /**
     * Obtenir les paramètres d'un quiz
     */
    public static function get_quiz_settings($quiz_id) {
        $settings = get_field('quiz_settings', $quiz_id);
        
        return array(
            'shuffle_questions' => !empty($settings['shuffle_questions']),
            'shuffle_answers' => !empty($settings['shuffle_answers']),
            'show_explanations' => !empty($settings['show_explanations'])
        );
    }
}

