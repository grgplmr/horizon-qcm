<?php
/**
 * Gestion des Custom Post Types pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les Custom Post Types
 */
class BIAQuiz_Post_Types {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_boxes'));
        add_filter('manage_biaquiz_posts_columns', array(__CLASS__, 'add_admin_columns'));
        add_action('manage_biaquiz_posts_custom_column', array(__CLASS__, 'populate_admin_columns'), 10, 2);
        add_filter('manage_edit-biaquiz_sortable_columns', array(__CLASS__, 'sortable_columns'));
        add_action('pre_get_posts', array(__CLASS__, 'admin_posts_filter'));
    }
    
    /**
     * Enregistrer les Custom Post Types
     */
    public static function register_post_types() {
        // Custom Post Type pour les Quiz
        $labels = array(
            'name'                  => _x('Quiz BIA', 'Post Type General Name', 'biaquiz-core'),
            'singular_name'         => _x('Quiz BIA', 'Post Type Singular Name', 'biaquiz-core'),
            'menu_name'             => __('Quiz BIA', 'biaquiz-core'),
            'name_admin_bar'        => __('Quiz BIA', 'biaquiz-core'),
            'archives'              => __('Archives des Quiz', 'biaquiz-core'),
            'attributes'            => __('Attributs du Quiz', 'biaquiz-core'),
            'parent_item_colon'     => __('Quiz Parent:', 'biaquiz-core'),
            'all_items'             => __('Tous les Quiz', 'biaquiz-core'),
            'add_new_item'          => __('Ajouter un Nouveau Quiz', 'biaquiz-core'),
            'add_new'               => __('Ajouter Nouveau', 'biaquiz-core'),
            'new_item'              => __('Nouveau Quiz', 'biaquiz-core'),
            'edit_item'             => __('Modifier le Quiz', 'biaquiz-core'),
            'update_item'           => __('Mettre à jour le Quiz', 'biaquiz-core'),
            'view_item'             => __('Voir le Quiz', 'biaquiz-core'),
            'view_items'            => __('Voir les Quiz', 'biaquiz-core'),
            'search_items'          => __('Rechercher un Quiz', 'biaquiz-core'),
            'not_found'             => __('Aucun quiz trouvé', 'biaquiz-core'),
            'not_found_in_trash'    => __('Aucun quiz trouvé dans la corbeille', 'biaquiz-core'),
            'featured_image'        => __('Image du Quiz', 'biaquiz-core'),
            'set_featured_image'    => __('Définir l\'image du quiz', 'biaquiz-core'),
            'remove_featured_image' => __('Supprimer l\'image du quiz', 'biaquiz-core'),
            'use_featured_image'    => __('Utiliser comme image du quiz', 'biaquiz-core'),
            'insert_into_item'      => __('Insérer dans le quiz', 'biaquiz-core'),
            'uploaded_to_this_item' => __('Téléchargé vers ce quiz', 'biaquiz-core'),
            'items_list'            => __('Liste des quiz', 'biaquiz-core'),
            'items_list_navigation' => __('Navigation de la liste des quiz', 'biaquiz-core'),
            'filter_items_list'     => __('Filtrer la liste des quiz', 'biaquiz-core'),
        );
        
        $args = array(
            'label'                 => __('Quiz BIA', 'biaquiz-core'),
            'description'           => __('Quiz pour l\'entraînement au Brevet d\'Initiation à l\'Aéronautique', 'biaquiz-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'taxonomies'            => array('quiz_category'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            // Place the Quiz BIA menu under the main BIAQuiz dashboard to
            // avoid displaying two separate admin links in the sidebar
            'show_in_menu'          => 'biaquiz-dashboard',
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-clipboard',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'biaquiz',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite'               => array(
                'slug'       => 'quiz',
                'with_front' => false,
            ),
        );
        
        register_post_type('biaquiz', $args);
    }
    
    /**
     * Ajouter les meta boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'biaquiz_settings',
            __('Paramètres du Quiz', 'biaquiz-core'),
            array(__CLASS__, 'settings_meta_box'),
            'biaquiz',
            'side',
            'high'
        );
        
        add_meta_box(
            'biaquiz_stats',
            __('Statistiques du Quiz', 'biaquiz-core'),
            array(__CLASS__, 'stats_meta_box'),
            'biaquiz',
            'side',
            'low'
        );
    }
    
    /**
     * Meta box pour les paramètres du quiz
     */
    public static function settings_meta_box($post) {
        wp_nonce_field('biaquiz_settings_nonce', 'biaquiz_settings_nonce');
        
        $quiz_number = get_post_meta($post->ID, 'quiz_number', true);
        $quiz_difficulty = get_post_meta($post->ID, 'quiz_difficulty', true);
        $quiz_time_limit = get_post_meta($post->ID, 'quiz_time_limit', true);
        $quiz_active = get_post_meta($post->ID, 'quiz_active', true);
        
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <label for="quiz_number"><strong><?php _e('Numéro du Quiz', 'biaquiz-core'); ?></strong></label>
                    <input type="number" id="quiz_number" name="quiz_number" value="<?php echo esc_attr($quiz_number); ?>" min="1" style="width: 100%;" />
                    <p class="description"><?php _e('Numéro d\'ordre du quiz dans sa catégorie', 'biaquiz-core'); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="quiz_difficulty"><strong><?php _e('Difficulté', 'biaquiz-core'); ?></strong></label>
                    <select id="quiz_difficulty" name="quiz_difficulty" style="width: 100%;">
                        <option value="facile" <?php selected($quiz_difficulty, 'facile'); ?>><?php _e('Facile', 'biaquiz-core'); ?></option>
                        <option value="moyen" <?php selected($quiz_difficulty, 'moyen'); ?>><?php _e('Moyen', 'biaquiz-core'); ?></option>
                        <option value="difficile" <?php selected($quiz_difficulty, 'difficile'); ?>><?php _e('Difficile', 'biaquiz-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="quiz_time_limit"><strong><?php _e('Temps limite (minutes)', 'biaquiz-core'); ?></strong></label>
                    <input type="number" id="quiz_time_limit" name="quiz_time_limit" value="<?php echo esc_attr($quiz_time_limit ?: 30); ?>" min="5" max="180" style="width: 100%;" />
                    <p class="description"><?php _e('0 = pas de limite de temps', 'biaquiz-core'); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" id="quiz_active" name="quiz_active" value="1" <?php checked($quiz_active, '1'); ?> />
                        <strong><?php _e('Quiz actif', 'biaquiz-core'); ?></strong>
                    </label>
                    <p class="description"><?php _e('Décocher pour désactiver temporairement ce quiz', 'biaquiz-core'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Meta box pour les statistiques du quiz
     */
    public static function stats_meta_box($post) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        // Récupérer les statistiques
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                MAX(score) as max_score,
                MIN(score) as min_score,
                AVG(time_taken) as avg_time
            FROM $table_name 
            WHERE quiz_id = %d
        ", $post->ID));
        
        if ($stats && $stats->total_attempts > 0) {
            ?>
            <table class="form-table">
                <tr>
                    <td><strong><?php _e('Tentatives totales', 'biaquiz-core'); ?>:</strong></td>
                    <td><?php echo number_format($stats->total_attempts); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Score moyen', 'biaquiz-core'); ?>:</strong></td>
                    <td><?php echo number_format($stats->avg_score, 1); ?>/20</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Meilleur score', 'biaquiz-core'); ?>:</strong></td>
                    <td><?php echo $stats->max_score; ?>/20</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Score le plus bas', 'biaquiz-core'); ?>:</strong></td>
                    <td><?php echo $stats->min_score; ?>/20</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Temps moyen', 'biaquiz-core'); ?>:</strong></td>
                    <td><?php echo gmdate('H:i:s', $stats->avg_time); ?></td>
                </tr>
            </table>
            <?php
        } else {
            echo '<p>' . __('Aucune statistique disponible pour ce quiz.', 'biaquiz-core') . '</p>';
        }
    }
    
    /**
     * Sauvegarder les meta boxes
     */
    public static function save_meta_boxes($post_id) {
        // Vérifier le nonce
        if (!isset($_POST['biaquiz_settings_nonce']) || !wp_verify_nonce($_POST['biaquiz_settings_nonce'], 'biaquiz_settings_nonce')) {
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Éviter l'auto-save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Sauvegarder les champs
        $fields = array('quiz_number', 'quiz_difficulty', 'quiz_time_limit', 'quiz_active');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $field);
            }
        }
        
        // Auto-générer le numéro si pas défini
        if (empty($_POST['quiz_number'])) {
            $quiz_categories = wp_get_post_terms($post_id, 'quiz_category');
            if (!empty($quiz_categories)) {
                $category_slug = $quiz_categories[0]->slug;
                $next_number = self::get_next_quiz_number($category_slug);
                update_post_meta($post_id, 'quiz_number', $next_number);
            }
        }
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
            AND t.slug = %s
        ", $category_slug));
        
        return ($max_number ? $max_number + 1 : 1);
    }
    
    /**
     * Ajouter des colonnes dans l'admin
     */
    public static function add_admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['quiz_number'] = __('N°', 'biaquiz-core');
                $new_columns['quiz_category'] = __('Catégorie', 'biaquiz-core');
                $new_columns['quiz_difficulty'] = __('Difficulté', 'biaquiz-core');
                $new_columns['quiz_stats'] = __('Statistiques', 'biaquiz-core');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Remplir les colonnes personnalisées
     */
    public static function populate_admin_columns($column, $post_id) {
        switch ($column) {
            case 'quiz_number':
                $number = get_post_meta($post_id, 'quiz_number', true);
                echo $number ? '#' . $number : '-';
                break;
                
            case 'quiz_category':
                $terms = get_the_terms($post_id, 'quiz_category');
                if ($terms && !is_wp_error($terms)) {
                    $term_links = array();
                    foreach ($terms as $term) {
                        $term_links[] = '<a href="' . admin_url('edit.php?post_type=biaquiz&quiz_category=' . $term->slug) . '">' . $term->name . '</a>';
                    }
                    echo implode(', ', $term_links);
                } else {
                    echo '-';
                }
                break;
                
            case 'quiz_difficulty':
                $difficulty = get_post_meta($post_id, 'quiz_difficulty', true);
                $difficulties = array(
                    'facile' => '<span style="color: green;">●</span> ' . __('Facile', 'biaquiz-core'),
                    'moyen' => '<span style="color: orange;">●</span> ' . __('Moyen', 'biaquiz-core'),
                    'difficile' => '<span style="color: red;">●</span> ' . __('Difficile', 'biaquiz-core')
                );
                echo isset($difficulties[$difficulty]) ? $difficulties[$difficulty] : '-';
                break;
                
            case 'quiz_stats':
                global $wpdb;
                $table_name = $wpdb->prefix . 'biaquiz_stats';
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE quiz_id = %d", $post_id));
                echo $count ? sprintf(__('%d tentatives', 'biaquiz-core'), $count) : __('Aucune', 'biaquiz-core');
                break;
        }
    }
    
    /**
     * Colonnes triables
     */
    public static function sortable_columns($columns) {
        $columns['quiz_number'] = 'quiz_number';
        $columns['quiz_difficulty'] = 'quiz_difficulty';
        return $columns;
    }
    
    /**
     * Filtrer les posts dans l'admin
     */
    public static function admin_posts_filter($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'biaquiz') {
            return;
        }
        
        // Tri par numéro de quiz
        if ($query->get('orderby') === 'quiz_number') {
            $query->set('meta_key', 'quiz_number');
            $query->set('orderby', 'meta_value_num');
        }
        
        // Tri par difficulté
        if ($query->get('orderby') === 'quiz_difficulty') {
            $query->set('meta_key', 'quiz_difficulty');
            $query->set('orderby', 'meta_value');
        }
    }
}

