<?php
/**
 * Interface d'administration pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer l'interface d'administration
 */
class BIAQuiz_Admin {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        // Menus d'administration
        add_action('admin_menu', array(__CLASS__, 'add_admin_menus'));
        
        // Scripts et styles admin
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
        
        // Notices d'administration
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
        
        // Colonnes personnalisées
        add_filter('manage_biaquiz_posts_columns', array(__CLASS__, 'add_quiz_columns'));
        add_action('manage_biaquiz_posts_custom_column', array(__CLASS__, 'populate_quiz_columns'), 10, 2);
        add_filter('manage_edit-biaquiz_sortable_columns', array(__CLASS__, 'sortable_quiz_columns'));
        
        // Filtres d'administration
        add_action('restrict_manage_posts', array(__CLASS__, 'add_admin_filters'));
        add_action('pre_get_posts', array(__CLASS__, 'filter_admin_posts'));
        
        // Actions en lot
        add_filter('bulk_actions-edit-biaquiz', array(__CLASS__, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-biaquiz', array(__CLASS__, 'handle_bulk_actions'), 10, 3);
        
        // Métaboxes personnalisées
        add_action('add_meta_boxes', array(__CLASS__, 'add_custom_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_quiz_meta'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_biaquiz_quick_edit', array(__CLASS__, 'ajax_quick_edit'));
        add_action('wp_ajax_biaquiz_duplicate_quiz', array(__CLASS__, 'ajax_duplicate_quiz'));
        add_action('wp_ajax_biaquiz_toggle_active', array(__CLASS__, 'ajax_toggle_active'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widget'));
        
        // Aide contextuelle
        add_action('load-post.php', array(__CLASS__, 'add_help_tabs'));
        add_action('load-post-new.php', array(__CLASS__, 'add_help_tabs'));
        add_action('load-edit.php', array(__CLASS__, 'add_help_tabs'));
    }
    
    /**
     * Ajouter les menus d'administration
     */
    public static function add_admin_menus() {
        // Menu principal
        add_menu_page(
            __('BIAQuiz', 'biaquiz-core'),
            __('BIAQuiz', 'biaquiz-core'),
            'manage_options',
            'biaquiz-dashboard',
            array(__CLASS__, 'dashboard_page'),
            'dashicons-clipboard',
            26
        );
        
        // Sous-menus
        add_submenu_page(
            'biaquiz-dashboard',
            __('Tableau de bord', 'biaquiz-core'),
            __('Tableau de bord', 'biaquiz-core'),
            'manage_options',
            'biaquiz-dashboard',
            array(__CLASS__, 'dashboard_page')
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Tous les Quiz', 'biaquiz-core'),
            __('Tous les Quiz', 'biaquiz-core'),
            'edit_posts',
            'edit.php?post_type=biaquiz'
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Ajouter un Quiz', 'biaquiz-core'),
            __('Ajouter un Quiz', 'biaquiz-core'),
            'edit_posts',
            'post-new.php?post_type=biaquiz'
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Catégories', 'biaquiz-core'),
            __('Catégories', 'biaquiz-core'),
            'manage_categories',
            'edit-tags.php?taxonomy=quiz_category&post_type=biaquiz'
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Import/Export', 'biaquiz-core'),
            __('Import/Export', 'biaquiz-core'),
            'manage_options',
            'biaquiz-import-export',
            array(__CLASS__, 'import_export_page')
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Statistiques', 'biaquiz-core'),
            __('Statistiques', 'biaquiz-core'),
            'manage_options',
            'biaquiz-stats',
            array(__CLASS__, 'stats_page')
        );
        
        add_submenu_page(
            'biaquiz-dashboard',
            __('Paramètres', 'biaquiz-core'),
            __('Paramètres', 'biaquiz-core'),
            'manage_options',
            'biaquiz-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    /**
     * Enqueue des scripts et styles admin
     */
    public static function enqueue_admin_scripts($hook) {
        global $post_type;
        
        // Scripts pour les pages BIAQuiz
        if ($post_type === 'biaquiz' || strpos($hook, 'biaquiz') !== false) {
            wp_enqueue_script(
                'biaquiz-admin',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-util'),
                BIAQUIZ_CORE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'biaquiz-admin',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                BIAQUIZ_CORE_VERSION
            );
            
            // Localisation
            wp_localize_script('biaquiz-admin', 'biaquiz_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer ce quiz ?', 'biaquiz-core'),
                    'confirm_duplicate' => __('Dupliquer ce quiz ?', 'biaquiz-core'),
                    'saving' => __('Sauvegarde...', 'biaquiz-core'),
                    'saved' => __('Sauvegardé !', 'biaquiz-core'),
                    'error' => __('Erreur lors de la sauvegarde', 'biaquiz-core'),
                )
            ));
        }
        
        // Scripts pour l'import/export
        if (strpos($hook, 'biaquiz-import-export') !== false) {
            wp_enqueue_script(
                'biaquiz-import-export',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/js/import-export.js',
                array('jquery'),
                BIAQUIZ_CORE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'biaquiz-import-export',
                BIAQUIZ_CORE_PLUGIN_URL . 'assets/css/import-export.css',
                array(),
                BIAQUIZ_CORE_VERSION
            );
        }
    }
    
    /**
     * Afficher les notices d'administration
     */
    public static function admin_notices() {
        global $post_type, $pagenow;
        
        // Vérifier si ACF est activé
        if ($post_type === 'biaquiz' && !function_exists('get_field')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>BIAQuiz:</strong> ';
            echo __('Advanced Custom Fields est requis pour gérer les questions des quiz.', 'biaquiz-core');
            echo '</p></div>';
        }
        
        // Notice de validation des quiz
        if ($pagenow === 'post.php' && isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            if (get_post_type($post_id) === 'biaquiz') {
                $validation_errors = get_post_meta($post_id, '_biaquiz_validation_errors', true);
                if ($validation_errors) {
                    echo '<div class="notice notice-warning"><p>' . $validation_errors . '</p></div>';
                }
            }
        }
        
        // Notices de succès pour les actions en lot
        if (isset($_GET['biaquiz_bulk_action'])) {
            $action = sanitize_text_field($_GET['biaquiz_bulk_action']);
            $count = intval($_GET['count'] ?? 0);
            
            switch ($action) {
                case 'activated':
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    printf(_n('%d quiz activé.', '%d quiz activés.', $count, 'biaquiz-core'), $count);
                    echo '</p></div>';
                    break;
                case 'deactivated':
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    printf(_n('%d quiz désactivé.', '%d quiz désactivés.', $count, 'biaquiz-core'), $count);
                    echo '</p></div>';
                    break;
                case 'duplicated':
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    printf(_n('%d quiz dupliqué.', '%d quiz dupliqués.', $count, 'biaquiz-core'), $count);
                    echo '</p></div>';
                    break;
            }
        }
    }
    
    /**
     * Page du tableau de bord
     */
    public static function dashboard_page() {
        // Statistiques globales
        global $wpdb;
        
        $stats = array(
            'total_quizzes' => wp_count_posts('biaquiz')->publish,
            'active_quizzes' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'biaquiz' 
                AND p.post_status = 'publish'
                AND pm.meta_key = 'quiz_active'
                AND pm.meta_value = '1'
            "),
            'total_categories' => wp_count_terms('quiz_category'),
            'total_attempts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}biaquiz_stats")
        );
        
        // Quiz récents
        $recent_quizzes = get_posts(array(
            'post_type' => 'biaquiz',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Statistiques par catégorie
        $categories = get_terms(array(
            'taxonomy' => 'quiz_category',
            'hide_empty' => false
        ));
        
        include BIAQUIZ_CORE_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Page d'import/export
     */
    public static function import_export_page() {
        // Traitement des formulaires
        if ($_POST) {
            if (isset($_POST['import_quiz']) && wp_verify_nonce($_POST['_wpnonce'], 'biaquiz_import')) {
                self::handle_import();
            } elseif (isset($_POST['export_quiz']) && wp_verify_nonce($_POST['_wpnonce'], 'biaquiz_export')) {
                self::handle_export();
            }
        }
        
        include BIAQUIZ_CORE_PLUGIN_DIR . 'templates/admin-import-export.php';
    }
    
    /**
     * Page des statistiques
     */
    public static function stats_page() {
        global $wpdb;
        
        // Statistiques générales
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $general_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                MAX(score) as max_score,
                MIN(score) as min_score,
                AVG(time_taken) as avg_time,
                COUNT(DISTINCT quiz_id) as quizzes_attempted
            FROM $table_name
        ");
        
        // Top 10 des quiz les plus populaires
        $popular_quizzes = $wpdb->get_results("
            SELECT 
                s.quiz_id,
                p.post_title,
                COUNT(*) as attempts,
                AVG(s.score) as avg_score
            FROM $table_name s
            INNER JOIN {$wpdb->posts} p ON s.quiz_id = p.ID
            GROUP BY s.quiz_id
            ORDER BY attempts DESC
            LIMIT 10
        ");
        
        // Statistiques par jour (30 derniers jours)
        $daily_stats = $wpdb->get_results("
            SELECT 
                DATE(completed_at) as date,
                COUNT(*) as attempts,
                AVG(score) as avg_score
            FROM $table_name
            WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(completed_at)
            ORDER BY date DESC
        ");
        
        include BIAQUIZ_CORE_PLUGIN_DIR . 'templates/admin-stats.php';
    }
    
    /**
     * Page des paramètres
     */
    public static function settings_page() {
        // Traitement du formulaire
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'biaquiz_settings')) {
            $settings = array(
                'default_time_limit' => intval($_POST['default_time_limit'] ?? 30),
                'allow_retries' => !empty($_POST['allow_retries']),
                'show_explanations' => !empty($_POST['show_explanations']),
                'shuffle_questions' => !empty($_POST['shuffle_questions']),
                'shuffle_answers' => !empty($_POST['shuffle_answers']),
                'save_stats' => !empty($_POST['save_stats']),
                'require_perfect_score' => !empty($_POST['require_perfect_score'])
            );
            
            update_option('biaquiz_settings', $settings);
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo __('Paramètres sauvegardés avec succès.', 'biaquiz-core');
            echo '</p></div>';
        }
        
        $settings = get_option('biaquiz_settings', array(
            'default_time_limit' => 30,
            'allow_retries' => true,
            'show_explanations' => true,
            'shuffle_questions' => false,
            'shuffle_answers' => true,
            'save_stats' => true,
            'require_perfect_score' => true
        ));
        
        include BIAQUIZ_CORE_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Ajouter des colonnes personnalisées
     */
    public static function add_quiz_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['quiz_number'] = __('N°', 'biaquiz-core');
                $new_columns['quiz_category'] = __('Catégorie', 'biaquiz-core');
                $new_columns['quiz_difficulty'] = __('Difficulté', 'biaquiz-core');
                $new_columns['quiz_active'] = __('Statut', 'biaquiz-core');
                $new_columns['quiz_stats'] = __('Statistiques', 'biaquiz-core');
                $new_columns['quiz_actions'] = __('Actions', 'biaquiz-core');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Remplir les colonnes personnalisées
     */
    public static function populate_quiz_columns($column, $post_id) {
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
                        $term_links[] = sprintf(
                            '<a href="%s">%s</a>',
                            admin_url('edit.php?post_type=biaquiz&quiz_category=' . $term->slug),
                            $term->name
                        );
                    }
                    echo implode(', ', $term_links);
                } else {
                    echo '-';
                }
                break;
                
            case 'quiz_difficulty':
                $difficulty = get_post_meta($post_id, 'quiz_difficulty', true);
                $difficulties = array(
                    'facile' => '<span class="difficulty-easy">●</span> ' . __('Facile', 'biaquiz-core'),
                    'moyen' => '<span class="difficulty-medium">●</span> ' . __('Moyen', 'biaquiz-core'),
                    'difficile' => '<span class="difficulty-hard">●</span> ' . __('Difficile', 'biaquiz-core')
                );
                echo isset($difficulties[$difficulty]) ? $difficulties[$difficulty] : '-';
                break;
                
            case 'quiz_active':
                $is_active = get_post_meta($post_id, 'quiz_active', true);
                if ($is_active === '1') {
                    echo '<span class="status-active">✓ ' . __('Actif', 'biaquiz-core') . '</span>';
                } else {
                    echo '<span class="status-inactive">✗ ' . __('Inactif', 'biaquiz-core') . '</span>';
                }
                break;
                
            case 'quiz_stats':
                global $wpdb;
                $table_name = $wpdb->prefix . 'biaquiz_stats';
                $stats = $wpdb->get_row($wpdb->prepare("
                    SELECT COUNT(*) as attempts, AVG(score) as avg_score
                    FROM $table_name WHERE quiz_id = %d
                ", $post_id));
                
                if ($stats && $stats->attempts > 0) {
                    printf(
                        '%d tentatives<br><small>Moyenne: %.1f/20</small>',
                        $stats->attempts,
                        $stats->avg_score
                    );
                } else {
                    echo __('Aucune', 'biaquiz-core');
                }
                break;
                
            case 'quiz_actions':
                $actions = array();
                
                $actions[] = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    get_edit_post_link($post_id),
                    __('Modifier', 'biaquiz-core'),
                    __('Modifier', 'biaquiz-core')
                );
                
                $actions[] = sprintf(
                    '<a href="#" class="biaquiz-duplicate" data-post-id="%d" title="%s">%s</a>',
                    $post_id,
                    __('Dupliquer', 'biaquiz-core'),
                    __('Dupliquer', 'biaquiz-core')
                );
                
                $is_active = get_post_meta($post_id, 'quiz_active', true);
                $toggle_text = ($is_active === '1') ? __('Désactiver', 'biaquiz-core') : __('Activer', 'biaquiz-core');
                $actions[] = sprintf(
                    '<a href="#" class="biaquiz-toggle-active" data-post-id="%d" title="%s">%s</a>',
                    $post_id,
                    $toggle_text,
                    $toggle_text
                );
                
                echo implode(' | ', $actions);
                break;
        }
    }
    
    /**
     * Colonnes triables
     */
    public static function sortable_quiz_columns($columns) {
        $columns['quiz_number'] = 'quiz_number';
        $columns['quiz_difficulty'] = 'quiz_difficulty';
        $columns['quiz_active'] = 'quiz_active';
        return $columns;
    }
    
    /**
     * Ajouter des filtres d'administration
     */
    public static function add_admin_filters() {
        global $typenow;
        
        if ($typenow === 'biaquiz') {
            // Filtre par catégorie
            $categories = get_terms(array(
                'taxonomy' => 'quiz_category',
                'hide_empty' => false
            ));
            
            if (!empty($categories)) {
                echo '<select name="quiz_category">';
                echo '<option value="">' . __('Toutes les catégories', 'biaquiz-core') . '</option>';
                
                $selected_category = $_GET['quiz_category'] ?? '';
                foreach ($categories as $category) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $category->slug,
                        selected($selected_category, $category->slug, false),
                        $category->name
                    );
                }
                echo '</select>';
            }
            
            // Filtre par difficulté
            $difficulties = array(
                'facile' => __('Facile', 'biaquiz-core'),
                'moyen' => __('Moyen', 'biaquiz-core'),
                'difficile' => __('Difficile', 'biaquiz-core')
            );
            
            echo '<select name="quiz_difficulty">';
            echo '<option value="">' . __('Toutes les difficultés', 'biaquiz-core') . '</option>';
            
            $selected_difficulty = $_GET['quiz_difficulty'] ?? '';
            foreach ($difficulties as $value => $label) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    $value,
                    selected($selected_difficulty, $value, false),
                    $label
                );
            }
            echo '</select>';
            
            // Filtre par statut
            echo '<select name="quiz_active">';
            echo '<option value="">' . __('Tous les statuts', 'biaquiz-core') . '</option>';
            echo '<option value="1"' . selected($_GET['quiz_active'] ?? '', '1', false) . '>' . __('Actifs', 'biaquiz-core') . '</option>';
            echo '<option value="0"' . selected($_GET['quiz_active'] ?? '', '0', false) . '>' . __('Inactifs', 'biaquiz-core') . '</option>';
            echo '</select>';
        }
    }
    
    /**
     * Filtrer les posts dans l'admin
     */
    public static function filter_admin_posts($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'biaquiz' && $query->is_main_query()) {
            $meta_query = array();
            
            // Filtre par catégorie
            if (!empty($_GET['quiz_category'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['quiz_category'])
                    )
                ));
            }
            
            // Filtre par difficulté
            if (!empty($_GET['quiz_difficulty'])) {
                $meta_query[] = array(
                    'key' => 'quiz_difficulty',
                    'value' => sanitize_text_field($_GET['quiz_difficulty']),
                    'compare' => '='
                );
            }
            
            // Filtre par statut
            if (isset($_GET['quiz_active']) && $_GET['quiz_active'] !== '') {
                $meta_query[] = array(
                    'key' => 'quiz_active',
                    'value' => sanitize_text_field($_GET['quiz_active']),
                    'compare' => '='
                );
            }
            
            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }
    
    /**
     * Ajouter des actions en lot
     */
    public static function add_bulk_actions($actions) {
        $actions['activate_quiz'] = __('Activer', 'biaquiz-core');
        $actions['deactivate_quiz'] = __('Désactiver', 'biaquiz-core');
        $actions['duplicate_quiz'] = __('Dupliquer', 'biaquiz-core');
        return $actions;
    }
    
    /**
     * Gérer les actions en lot
     */
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        $count = 0;
        
        switch ($action) {
            case 'activate_quiz':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, 'quiz_active', '1');
                    $count++;
                }
                $redirect_to = add_query_arg('biaquiz_bulk_action', 'activated', $redirect_to);
                break;
                
            case 'deactivate_quiz':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, 'quiz_active', '0');
                    $count++;
                }
                $redirect_to = add_query_arg('biaquiz_bulk_action', 'deactivated', $redirect_to);
                break;
                
            case 'duplicate_quiz':
                foreach ($post_ids as $post_id) {
                    self::duplicate_quiz($post_id);
                    $count++;
                }
                $redirect_to = add_query_arg('biaquiz_bulk_action', 'duplicated', $redirect_to);
                break;
        }
        
        if ($count > 0) {
            $redirect_to = add_query_arg('count', $count, $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * Dupliquer un quiz
     */
    private static function duplicate_quiz($post_id) {
        $original_post = get_post($post_id);
        if (!$original_post) {
            return false;
        }
        
        // Créer le nouveau post
        $new_post = array(
            'post_title' => $original_post->post_title . ' (Copie)',
            'post_content' => $original_post->post_content,
            'post_excerpt' => $original_post->post_excerpt,
            'post_status' => 'draft',
            'post_type' => $original_post->post_type,
            'post_author' => get_current_user_id()
        );
        
        $new_post_id = wp_insert_post($new_post);
        
        if ($new_post_id) {
            // Copier les métadonnées
            $meta_keys = array('quiz_difficulty', 'quiz_time_limit', 'quiz_active');
            foreach ($meta_keys as $key) {
                $value = get_post_meta($post_id, $key, true);
                if ($value) {
                    update_post_meta($new_post_id, $key, $value);
                }
            }
            
            // Copier les taxonomies
            $taxonomies = get_object_taxonomies($original_post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_post_terms($new_post_id, $terms, $taxonomy);
            }
            
            // Copier les champs ACF
            if (function_exists('get_fields')) {
                $fields = get_fields($post_id);
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        update_field($key, $value, $new_post_id);
                    }
                }
            }
            
            // Générer un nouveau numéro
            $categories = wp_get_post_terms($new_post_id, 'quiz_category');
            if (!empty($categories)) {
                $category_slug = $categories[0]->slug;
                $next_number = self::get_next_quiz_number($category_slug);
                update_post_meta($new_post_id, 'quiz_number', $next_number);
            }
            
            return $new_post_id;
        }
        
        return false;
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
     * AJAX: Édition rapide
     */
    public static function ajax_quick_edit() {
        check_ajax_referer('biaquiz_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        $fields = array('quiz_difficulty', 'quiz_time_limit', 'quiz_active');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        wp_send_json_success('Quiz updated');
    }
    
    /**
     * AJAX: Dupliquer un quiz
     */
    public static function ajax_duplicate_quiz() {
        check_ajax_referer('biaquiz_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id || !current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        $new_post_id = self::duplicate_quiz($post_id);
        
        if ($new_post_id) {
            wp_send_json_success(array(
                'message' => 'Quiz dupliqué avec succès',
                'edit_url' => get_edit_post_link($new_post_id, 'raw')
            ));
        } else {
            wp_send_json_error('Erreur lors de la duplication');
        }
    }
    
    /**
     * AJAX: Basculer le statut actif
     */
    public static function ajax_toggle_active() {
        check_ajax_referer('biaquiz_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        $current_status = get_post_meta($post_id, 'quiz_active', true);
        $new_status = ($current_status === '1') ? '0' : '1';
        
        update_post_meta($post_id, 'quiz_active', $new_status);
        
        wp_send_json_success(array(
            'status' => $new_status,
            'message' => ($new_status === '1') ? 'Quiz activé' : 'Quiz désactivé'
        ));
    }
    
    /**
     * Ajouter un widget au tableau de bord
     */
    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'biaquiz_dashboard_widget',
            __('BIAQuiz - Aperçu', 'biaquiz-core'),
            array(__CLASS__, 'dashboard_widget_content')
        );
    }
    
    /**
     * Contenu du widget tableau de bord
     */
    public static function dashboard_widget_content() {
        global $wpdb;
        
        $stats = array(
            'total_quizzes' => wp_count_posts('biaquiz')->publish,
            'total_attempts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}biaquiz_stats"),
            'recent_attempts' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}biaquiz_stats 
                WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ")
        );
        
        echo '<div class="biaquiz-dashboard-widget">';
        echo '<div class="stats-grid">';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $stats['total_quizzes'] . '</span>';
        echo '<span class="stat-label">' . __('Quiz publiés', 'biaquiz-core') . '</span>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $stats['total_attempts'] . '</span>';
        echo '<span class="stat-label">' . __('Tentatives totales', 'biaquiz-core') . '</span>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $stats['recent_attempts'] . '</span>';
        echo '<span class="stat-label">' . __('Cette semaine', 'biaquiz-core') . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<p><a href="' . admin_url('admin.php?page=biaquiz-dashboard') . '">' . __('Voir le tableau de bord complet', 'biaquiz-core') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Ajouter des onglets d'aide
     */
    public static function add_help_tabs() {
        $screen = get_current_screen();
        
        if ($screen->post_type === 'biaquiz') {
            $screen->add_help_tab(array(
                'id' => 'biaquiz_help_overview',
                'title' => __('Aperçu', 'biaquiz-core'),
                'content' => '<p>' . __('BIAQuiz vous permet de créer et gérer des quiz pour l\'entraînement au Brevet d\'Initiation à l\'Aéronautique.', 'biaquiz-core') . '</p>'
            ));
            
            $screen->add_help_tab(array(
                'id' => 'biaquiz_help_questions',
                'title' => __('Questions', 'biaquiz-core'),
                'content' => '<p>' . __('Chaque quiz doit contenir exactement 20 questions avec 4 réponses chacune. Une seule réponse peut être correcte par question.', 'biaquiz-core') . '</p>'
            ));
            
            $screen->add_help_tab(array(
                'id' => 'biaquiz_help_categories',
                'title' => __('Catégories', 'biaquiz-core'),
                'content' => '<p>' . __('Les quiz sont organisés en 6 catégories correspondant aux domaines du BIA : Aérodynamique, Aéronefs, Météorologie, Navigation, Histoire et Anglais.', 'biaquiz-core') . '</p>'
            ));
        }
    }
    
    /**
     * Traitement de l'import
     */
    private static function handle_import() {
        if (class_exists('BIAQuiz_Import_Export')) {
            BIAQuiz_Import_Export::handle_import();
        }
    }
    
    /**
     * Traitement de l'export
     */
    private static function handle_export() {
        if (class_exists('BIAQuiz_Import_Export')) {
            BIAQuiz_Import_Export::handle_export();
        }
    }
}

