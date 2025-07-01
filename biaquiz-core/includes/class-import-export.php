<?php
/**
 * Système d'import/export pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer l'import/export des quiz
 */
class BIAQuiz_Import_Export {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('wp_ajax_biaquiz_import', array(__CLASS__, 'ajax_import_quiz'));
        add_action('wp_ajax_biaquiz_export', array(__CLASS__, 'ajax_export_quiz'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Import/Export', 'biaquiz-core'),
            __('Import/Export', 'biaquiz-core'),
            'manage_options',
            'biaquiz-import-export',
            array(__CLASS__, 'admin_page')
        );
    }
    
    /**
     * Enregistrer les scripts admin
     */
    public static function enqueue_admin_scripts($hook) {
        if ($hook === 'biaquiz_page_biaquiz-import-export') {
            wp_enqueue_script('biaquiz-import-export', BIAQUIZ_CORE_PLUGIN_URL . 'assets/js/import-export.js', array('jquery'), BIAQUIZ_CORE_VERSION, true);
            wp_enqueue_style('biaquiz-import-export', BIAQUIZ_CORE_PLUGIN_URL . 'assets/css/import-export.css', array(), BIAQUIZ_CORE_VERSION);
            
            wp_localize_script('biaquiz-import-export', 'biaquiz_import_export', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_import_export_nonce'),
                'strings' => array(
                    'importing' => __('Import en cours...', 'biaquiz-core'),
                    'exporting' => __('Export en cours...', 'biaquiz-core'),
                    'success' => __('Opération réussie', 'biaquiz-core'),
                    'error' => __('Une erreur est survenue', 'biaquiz-core'),
                )
            ));
        }
    }
    
    /**
     * Page d'administration
     */
    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export de Quiz', 'biaquiz-core'); ?></h1>
            
            <div class="biaquiz-import-export-container">
                <!-- Section Import -->
                <div class="import-section">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Importer des Quiz', 'biaquiz-core'); ?></h2>
                        <div class="inside">
                            <p><?php _e('Importez des quiz depuis un fichier CSV ou JSON.', 'biaquiz-core'); ?></p>
                            
                            <form id="import-form" enctype="multipart/form-data">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="import-file"><?php _e('Fichier', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <input type="file" id="import-file" name="import_file" accept=".csv,.json" required />
                                            <p class="description">
                                                <?php _e('Formats acceptés : CSV, JSON. Taille maximale : 10 MB', 'biaquiz-core'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="import-category"><?php _e('Catégorie', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <select id="import-category" name="import_category" required>
                                                <option value=""><?php _e('Sélectionner une catégorie', 'biaquiz-core'); ?></option>
                                                <?php
                                                $categories = get_terms(array(
                                                    'taxonomy' => 'quiz_category',
                                                    'hide_empty' => false
                                                ));
                                                foreach ($categories as $category) {
                                                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="import-options"><?php _e('Options', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" id="update-existing" name="update_existing" value="1" />
                                                <?php _e('Mettre à jour les quiz existants', 'biaquiz-core'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" id="auto-publish" name="auto_publish" value="1" checked />
                                                <?php _e('Publier automatiquement', 'biaquiz-core'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php _e('Importer', 'biaquiz-core'); ?>
                                    </button>
                                </p>
                            </form>
                            
                            <div id="import-progress" class="import-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <div class="progress-text"></div>
                            </div>
                            
                            <div id="import-results" class="import-results" style="display: none;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Export -->
                <div class="export-section">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Exporter des Quiz', 'biaquiz-core'); ?></h2>
                        <div class="inside">
                            <p><?php _e('Exportez vos quiz au format CSV ou JSON.', 'biaquiz-core'); ?></p>
                            
                            <form id="export-form">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="export-category"><?php _e('Catégorie', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <select id="export-category" name="export_category">
                                                <option value=""><?php _e('Toutes les catégories', 'biaquiz-core'); ?></option>
                                                <?php
                                                foreach ($categories as $category) {
                                                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="export-format"><?php _e('Format', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <select id="export-format" name="export_format">
                                                <option value="json"><?php _e('JSON', 'biaquiz-core'); ?></option>
                                                <option value="csv"><?php _e('CSV', 'biaquiz-core'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="export-options"><?php _e('Options', 'biaquiz-core'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" id="include-stats" name="include_stats" value="1" />
                                                <?php _e('Inclure les statistiques', 'biaquiz-core'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" id="include-images" name="include_images" value="1" />
                                                <?php _e('Inclure les URLs des images', 'biaquiz-core'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php _e('Exporter', 'biaquiz-core'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Section Documentation -->
                <div class="documentation-section">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Format des fichiers', 'biaquiz-core'); ?></h2>
                        <div class="inside">
                            <h3><?php _e('Format CSV', 'biaquiz-core'); ?></h3>
                            <p><?php _e('Le fichier CSV doit contenir les colonnes suivantes :', 'biaquiz-core'); ?></p>
                            <ul>
                                <li><strong>title</strong> : <?php _e('Titre du quiz', 'biaquiz-core'); ?></li>
                                <li><strong>description</strong> : <?php _e('Description du quiz', 'biaquiz-core'); ?></li>
                                <li><strong>difficulty</strong> : <?php _e('Difficulté (facile, moyen, difficile)', 'biaquiz-core'); ?></li>
                                <li><strong>question_1</strong> : <?php _e('Texte de la question 1', 'biaquiz-core'); ?></li>
                                <li><strong>answer_1_a</strong> : <?php _e('Réponse A de la question 1', 'biaquiz-core'); ?></li>
                                <li><strong>answer_1_b</strong> : <?php _e('Réponse B de la question 1', 'biaquiz-core'); ?></li>
                                <li><strong>answer_1_c</strong> : <?php _e('Réponse C de la question 1', 'biaquiz-core'); ?></li>
                                <li><strong>answer_1_d</strong> : <?php _e('Réponse D de la question 1', 'biaquiz-core'); ?></li>
                                <li><strong>correct_1</strong> : <?php _e('Lettre de la bonne réponse (A, B, C ou D)', 'biaquiz-core'); ?></li>
                                <li><strong>explanation_1</strong> : <?php _e('Explication de la question 1 (optionnel)', 'biaquiz-core'); ?></li>
                                <li><?php _e('... (répéter pour les 20 questions)', 'biaquiz-core'); ?></li>
                            </ul>
                            
                            <h3><?php _e('Format JSON', 'biaquiz-core'); ?></h3>
                            <p><?php _e('Le fichier JSON doit suivre cette structure :', 'biaquiz-core'); ?></p>
                            <pre><code>{
  "title": "Titre du quiz",
  "description": "Description du quiz",
  "difficulty": "moyen",
  "questions": [
    {
      "question_text": "Texte de la question",
      "question_explanation": "Explication (optionnel)",
      "answers": [
        {"answer_text": "Réponse A", "is_correct": false},
        {"answer_text": "Réponse B", "is_correct": true},
        {"answer_text": "Réponse C", "is_correct": false},
        {"answer_text": "Réponse D", "is_correct": false}
      ]
    }
  ]
}</code></pre>
                            
                            <p class="submit">
                                <a href="<?php echo BIAQUIZ_CORE_PLUGIN_URL . 'templates/quiz-template.csv'; ?>" class="button" download>
                                    <?php _e('Télécharger le modèle CSV', 'biaquiz-core'); ?>
                                </a>
                                <a href="<?php echo BIAQUIZ_CORE_PLUGIN_URL . 'templates/quiz-template.json'; ?>" class="button" download>
                                    <?php _e('Télécharger le modèle JSON', 'biaquiz-core'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX pour importer un quiz
     */
    public static function ajax_import_quiz() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'biaquiz_import_export_nonce')) {
            wp_die(__('Accès non autorisé', 'biaquiz-core'));
        }
        
        // Vérifier le fichier uploadé
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Erreur lors de l\'upload du fichier', 'biaquiz-core'));
        }
        
        $file = $_FILES['import_file'];
        $category_slug = sanitize_text_field($_POST['import_category']);
        $update_existing = !empty($_POST['update_existing']);
        $auto_publish = !empty($_POST['auto_publish']);
        
        // Vérifier la catégorie
        $category = get_term_by('slug', $category_slug, 'quiz_category');
        if (!$category) {
            wp_send_json_error(__('Catégorie invalide', 'biaquiz-core'));
        }
        
        // Déterminer le format du fichier
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        try {
            if ($file_extension === 'csv') {
                $result = self::import_csv($file['tmp_name'], $category, $update_existing, $auto_publish);
            } elseif ($file_extension === 'json') {
                $result = self::import_json($file['tmp_name'], $category, $update_existing, $auto_publish);
            } else {
                throw new Exception(__('Format de fichier non supporté', 'biaquiz-core'));
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Importer depuis un fichier CSV
     */
    private static function import_csv($file_path, $category, $update_existing, $auto_publish) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Impossible de lire le fichier CSV', 'biaquiz-core'));
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Fichier CSV vide ou invalide', 'biaquiz-core'));
        }
        
        $imported = 0;
        $updated = 0;
        $errors = array();
        
        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($headers, $row);
                $quiz_data = self::parse_csv_row($data);
                
                $result = self::create_or_update_quiz($quiz_data, $category, $update_existing, $auto_publish);
                
                if ($result['updated']) {
                    $updated++;
                } else {
                    $imported++;
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Ligne %d: %s', 'biaquiz-core'), $imported + $updated + count($errors) + 2, $e->getMessage());
            }
        }
        
        fclose($handle);
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }
    
    /**
     * Importer depuis un fichier JSON
     */
    private static function import_json($file_path, $category, $update_existing, $auto_publish) {
        $content = file_get_contents($file_path);
        if (!$content) {
            throw new Exception(__('Impossible de lire le fichier JSON', 'biaquiz-core'));
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Fichier JSON invalide', 'biaquiz-core'));
        }
        
        $imported = 0;
        $updated = 0;
        $errors = array();
        
        // Si c'est un seul quiz
        if (isset($data['title']) && isset($data['questions'])) {
            $data = array($data);
        }
        
        foreach ($data as $index => $quiz_data) {
            try {
                $result = self::create_or_update_quiz($quiz_data, $category, $update_existing, $auto_publish);
                
                if ($result['updated']) {
                    $updated++;
                } else {
                    $imported++;
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Quiz %d: %s', 'biaquiz-core'), $index + 1, $e->getMessage());
            }
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }
    
    /**
     * Parser une ligne CSV
     */
    private static function parse_csv_row($data) {
        $quiz_data = array(
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'difficulty' => $data['difficulty'] ?? 'moyen',
            'questions' => array()
        );
        
        // Parser les 20 questions
        for ($i = 1; $i <= 20; $i++) {
            $question_key = "question_$i";
            $correct_key = "correct_$i";
            $explanation_key = "explanation_$i";
            
            if (empty($data[$question_key])) {
                continue;
            }
            
            $answers = array();
            foreach (array('a', 'b', 'c', 'd') as $letter) {
                $answer_key = "answer_{$i}_{$letter}";
                if (!empty($data[$answer_key])) {
                    $answers[] = array(
                        'answer_text' => $data[$answer_key],
                        'is_correct' => strtolower($data[$correct_key] ?? '') === $letter
                    );
                }
            }
            
            if (count($answers) === 4) {
                $quiz_data['questions'][] = array(
                    'question_text' => $data[$question_key],
                    'question_explanation' => $data[$explanation_key] ?? '',
                    'answers' => $answers
                );
            }
        }
        
        return $quiz_data;
    }
    
    /**
     * Créer ou mettre à jour un quiz
     */
    private static function create_or_update_quiz($quiz_data, $category, $update_existing, $auto_publish) {
        // Valider les données
        if (empty($quiz_data['title'])) {
            throw new Exception(__('Titre du quiz requis', 'biaquiz-core'));
        }
        
        if (empty($quiz_data['questions']) || count($quiz_data['questions']) !== 20) {
            throw new Exception(__('Le quiz doit contenir exactement 20 questions', 'biaquiz-core'));
        }
        
        // Vérifier si le quiz existe déjà
        $existing_quiz = null;
        if ($update_existing) {
            $existing_posts = get_posts(array(
                'post_type' => 'biaquiz',
                'title' => $quiz_data['title'],
                'post_status' => 'any',
                'numberposts' => 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    )
                )
            ));
            
            if (!empty($existing_posts)) {
                $existing_quiz = $existing_posts[0];
            }
        }
        
        // Créer ou mettre à jour le quiz
        $post_data = array(
            'post_title' => $quiz_data['title'],
            'post_content' => '',
            'post_excerpt' => $quiz_data['description'] ?? '',
            'post_type' => 'biaquiz',
            'post_status' => $auto_publish ? 'publish' : 'draft'
        );
        
        if ($existing_quiz) {
            $post_data['ID'] = $existing_quiz->ID;
            $quiz_id = wp_update_post($post_data);
            $updated = true;
        } else {
            $quiz_id = wp_insert_post($post_data);
            $updated = false;
        }
        
        if (is_wp_error($quiz_id)) {
            throw new Exception(__('Erreur lors de la création du quiz', 'biaquiz-core'));
        }
        
        // Assigner la catégorie
        wp_set_post_terms($quiz_id, array($category->term_id), 'quiz_category');
        
        // Sauvegarder les métadonnées
        update_post_meta($quiz_id, 'quiz_difficulty', $quiz_data['difficulty']);
        update_post_meta($quiz_id, 'quiz_active', '1');
        
        // Obtenir le prochain numéro de quiz
        $quiz_number = self::get_next_quiz_number($category->slug);
        update_post_meta($quiz_id, 'quiz_number', $quiz_number);
        
        // Sauvegarder les questions avec ACF
        update_field('questions', $quiz_data['questions'], $quiz_id);
        
        // Paramètres par défaut
        update_field('quiz_settings', array(
            'shuffle_questions' => true,
            'shuffle_answers' => true,
            'show_explanations' => true
        ), $quiz_id);
        
        return array(
            'quiz_id' => $quiz_id,
            'updated' => $updated
        );
    }
    
    /**
     * Obtenir le prochain numéro de quiz
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
            AND t.slug = %s
        ", $category_slug));
        
        return ($max_number ? $max_number + 1 : 1);
    }
    
    /**
     * AJAX pour exporter des quiz
     */
    public static function ajax_export_quiz() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'biaquiz_import_export_nonce')) {
            wp_die(__('Accès non autorisé', 'biaquiz-core'));
        }
        
        $category_slug = sanitize_text_field($_POST['export_category']);
        $format = sanitize_text_field($_POST['export_format']);
        $include_stats = !empty($_POST['include_stats']);
        $include_images = !empty($_POST['include_images']);
        
        try {
            // Obtenir les quiz
            $args = array(
                'post_type' => 'biaquiz',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );
            
            if ($category_slug) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'slug',
                        'terms' => $category_slug
                    )
                );
            }
            
            $quizzes = get_posts($args);
            
            if (empty($quizzes)) {
                throw new Exception(__('Aucun quiz à exporter', 'biaquiz-core'));
            }
            
            if ($format === 'json') {
                $result = self::export_json($quizzes, $include_stats, $include_images);
            } else {
                $result = self::export_csv($quizzes, $include_stats, $include_images);
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Exporter au format JSON
     */
    private static function export_json($quizzes, $include_stats, $include_images) {
        $export_data = array();
        
        foreach ($quizzes as $quiz) {
            $questions = get_field('questions', $quiz->ID);
            $settings = get_field('quiz_settings', $quiz->ID);
            
            $quiz_data = array(
                'title' => $quiz->post_title,
                'description' => $quiz->post_excerpt,
                'difficulty' => get_post_meta($quiz->ID, 'quiz_difficulty', true),
                'number' => get_post_meta($quiz->ID, 'quiz_number', true),
                'questions' => $questions ?: array(),
                'settings' => $settings ?: array()
            );
            
            if ($include_stats) {
                $quiz_data['stats'] = BIAQuiz_Handler::get_quiz_statistics($quiz->ID);
            }
            
            if (!$include_images && !empty($quiz_data['questions'])) {
                foreach ($quiz_data['questions'] as &$question) {
                    unset($question['question_image']);
                }
            }
            
            $export_data[] = $quiz_data;
        }
        
        $filename = 'biaquiz-export-' . date('Y-m-d-H-i-s') . '.json';
        $content = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return array(
            'filename' => $filename,
            'content' => $content,
            'mime_type' => 'application/json'
        );
    }
    
    /**
     * Exporter au format CSV
     */
    private static function export_csv($quizzes, $include_stats, $include_images) {
        $csv_data = array();
        
        // En-têtes CSV
        $headers = array('title', 'description', 'difficulty', 'number');
        
        // Ajouter les en-têtes pour les 20 questions
        for ($i = 1; $i <= 20; $i++) {
            $headers[] = "question_$i";
            $headers[] = "answer_{$i}_a";
            $headers[] = "answer_{$i}_b";
            $headers[] = "answer_{$i}_c";
            $headers[] = "answer_{$i}_d";
            $headers[] = "correct_$i";
            $headers[] = "explanation_$i";
        }
        
        if ($include_stats) {
            $headers = array_merge($headers, array('total_attempts', 'avg_score', 'success_rate'));
        }
        
        $csv_data[] = $headers;
        
        foreach ($quizzes as $quiz) {
            $questions = get_field('questions', $quiz->ID) ?: array();
            
            $row = array(
                $quiz->post_title,
                $quiz->post_excerpt,
                get_post_meta($quiz->ID, 'quiz_difficulty', true),
                get_post_meta($quiz->ID, 'quiz_number', true)
            );
            
            // Ajouter les données des questions
            for ($i = 0; $i < 20; $i++) {
                if (isset($questions[$i])) {
                    $question = $questions[$i];
                    $answers = $question['answers'] ?: array();
                    
                    $row[] = $question['question_text'] ?? '';
                    
                    // Ajouter les 4 réponses
                    $correct_letter = '';
                    for ($j = 0; $j < 4; $j++) {
                        if (isset($answers[$j])) {
                            $row[] = $answers[$j]['answer_text'] ?? '';
                            if (!empty($answers[$j]['is_correct'])) {
                                $correct_letter = chr(65 + $j); // A, B, C, D
                            }
                        } else {
                            $row[] = '';
                        }
                    }
                    
                    $row[] = $correct_letter;
                    $row[] = $question['question_explanation'] ?? '';
                } else {
                    // Question vide
                    $row = array_merge($row, array_fill(0, 7, ''));
                }
            }
            
            if ($include_stats) {
                $stats = BIAQuiz_Handler::get_quiz_statistics($quiz->ID);
                $row[] = $stats['total_attempts'];
                $row[] = $stats['avg_score'];
                $row[] = $stats['success_rate'];
            }
            
            $csv_data[] = $row;
        }
        
        // Générer le contenu CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        $filename = 'biaquiz-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        return array(
            'filename' => $filename,
            'content' => $content,
            'mime_type' => 'text/csv'
        );
    }
}

