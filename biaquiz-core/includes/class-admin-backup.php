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
        add_action('admin_menu', array(__CLASS__, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
        add_action('admin_init', array(__CLASS__, 'admin_init'));
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
        add_filter('manage_biaquiz_posts_columns', array(__CLASS__, 'customize_quiz_columns'));
        add_action('manage_biaquiz_posts_custom_column', array(__CLASS__, 'populate_quiz_columns'), 10, 2);
        add_filter('post_row_actions', array(__CLASS__, 'add_quiz_row_actions'), 10, 2);
        add_action('wp_ajax_biaquiz_quick_action', array(__CLASS__, 'ajax_quick_action'));
        add_action('admin_head', array(__CLASS__, 'admin_head'));
    }
    
    /**
     * Ajouter les menus d'administration
     */
    public static function add_admin_menus() {
        // Menu principal déjà créé par le Custom Post Type
        
        // Tableau de bord
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Tableau de bord', 'biaquiz-core'),
            __('Tableau de bord', 'biaquiz-core'),
            'manage_options',
            'biaquiz-dashboard',
            array(__CLASS__, 'dashboard_page'),
            0
        );
        
        // Statistiques
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Statistiques', 'biaquiz-core'),
            __('Statistiques', 'biaquiz-core'),
            'manage_options',
            'biaquiz-statistics',
            array(__CLASS__, 'statistics_page')
        );
        
        // Paramètres
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Paramètres', 'biaquiz-core'),
            __('Paramètres', 'biaquiz-core'),
            'manage_options',
            'biaquiz-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    /**
     * Enregistrer les scripts et styles admin
     */
    public static function enqueue_admin_scripts($hook) {
        // Scripts pour toutes les pages BIAQuiz
        if (strpos($hook, 'biaquiz') !== false || get_post_type() === 'biaquiz') {
            wp_enqueue_style('biaquiz-admin', BIAQUIZ_CORE_PLUGIN_URL . 'assets/css/admin.css', array(), BIAQUIZ_CORE_VERSION);
            wp_enqueue_script('biaquiz-admin', BIAQUIZ_CORE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BIAQUIZ_CORE_VERSION, true);
            
            wp_localize_script('biaquiz-admin', 'biaquiz_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer cet élément ?', 'biaquiz-core'),
                    'confirm_activate' => __('Activer ce quiz ?', 'biaquiz-core'),
                    'confirm_deactivate' => __('Désactiver ce quiz ?', 'biaquiz-core'),
                    'loading' => __('Chargement...', 'biaquiz-core'),
                    'error' => __('Une erreur est survenue', 'biaquiz-core'),
                    'success' => __('Opération réussie', 'biaquiz-core'),
                )
            ));
        }
        
        // Scripts spécifiques pour les graphiques
        if ($hook === 'biaquiz_page_biaquiz-dashboard' || $hook === 'biaquiz_page_biaquiz-statistics') {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }
    
    /**
     * Initialisation admin
     */
    public static function admin_init() {
        // Enregistrer les paramètres
        register_setting('biaquiz_settings', 'biaquiz_options');
        
        // Sections de paramètres
        add_settings_section(
            'biaquiz_general',
            __('Paramètres généraux', 'biaquiz-core'),
            array(__CLASS__, 'general_settings_callback'),
            'biaquiz_settings'
        );
        
        add_settings_section(
            'biaquiz_quiz',
            __('Paramètres des quiz', 'biaquiz-core'),
            array(__CLASS__, 'quiz_settings_callback'),
            'biaquiz_settings'
        );
        
        // Champs de paramètres
        add_settings_field(
            'site_name',
            __('Nom du site', 'biaquiz-core'),
            array(__CLASS__, 'text_field_callback'),
            'biaquiz_settings',
            'biaquiz_general',
            array('field' => 'site_name', 'default' => 'ACME BIAQuiz')
        );
        
        add_settings_field(
            'default_time_limit',
            __('Temps limite par défaut (minutes)', 'biaquiz-core'),
            array(__CLASS__, 'number_field_callback'),
            'biaquiz_settings',
            'biaquiz_quiz',
            array('field' => 'default_time_limit', 'default' => 30, 'min' => 5, 'max' => 180)
        );
        
        add_settings_field(
            'allow_retries',
            __('Autoriser les tentatives multiples', 'biaquiz-core'),
            array(__CLASS__, 'checkbox_field_callback'),
            'biaquiz_settings',
            'biaquiz_quiz',
            array('field' => 'allow_retries', 'default' => true)
        );
        
        add_settings_field(
            'show_statistics',
            __('Afficher les statistiques publiques', 'biaquiz-core'),
            array(__CLASS__, 'checkbox_field_callback'),
            'biaquiz_settings',
            'biaquiz_quiz',
            array('field' => 'show_statistics', 'default' => true)
        );
    }
    
    /**
     * Page du tableau de bord
     */
    public static function dashboard_page() {
        // Récupérer les statistiques globales
        $stats = self::get_global_statistics();
        $recent_attempts = self::get_recent_attempts(10);
        $popular_quizzes = self::get_popular_quizzes(5);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tableau de bord BIAQuiz', 'biaquiz-core'); ?></h1>
            
            <!-- Statistiques globales -->
            <div class="biaquiz-dashboard-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total_quizzes']); ?></div>
                            <div class="stat-label"><?php _e('Quiz actifs', 'biaquiz-core'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total_attempts']); ?></div>
                            <div class="stat-label"><?php _e('Tentatives totales', 'biaquiz-core'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['avg_score'], 1); ?>/20</div>
                            <div class="stat-label"><?php _e('Score moyen', 'biaquiz-core'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['success_rate']; ?>%</div>
                            <div class="stat-label"><?php _e('Taux de réussite', 'biaquiz-core'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="biaquiz-dashboard-content">
                <div class="dashboard-left">
                    <!-- Graphique des tentatives -->
                    <div class="dashboard-widget">
                        <h3><?php _e('Tentatives des 30 derniers jours', 'biaquiz-core'); ?></h3>
                        <canvas id="attempts-chart" width="400" height="200"></canvas>
                    </div>
                    
                    <!-- Quiz populaires -->
                    <div class="dashboard-widget">
                        <h3><?php _e('Quiz les plus populaires', 'biaquiz-core'); ?></h3>
                        <div class="popular-quizzes">
                            <?php foreach ($popular_quizzes as $quiz) : ?>
                                <div class="popular-quiz-item">
                                    <div class="quiz-info">
                                        <strong><?php echo esc_html($quiz->post_title); ?></strong>
                                        <span class="quiz-category">
                                            <?php
                                            $categories = get_the_terms($quiz->ID, 'quiz_category');
                                            if ($categories && !is_wp_error($categories)) {
                                                echo esc_html($categories[0]->name);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="quiz-stats">
                                        <span class="attempts"><?php echo number_format($quiz->attempt_count); ?> tentatives</span>
                                        <span class="avg-score"><?php echo number_format($quiz->avg_score, 1); ?>/20</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-right">
                    <!-- Actions rapides -->
                    <div class="dashboard-widget">
                        <h3><?php _e('Actions rapides', 'biaquiz-core'); ?></h3>
                        <div class="quick-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=biaquiz'); ?>" class="button button-primary">
                                ➕ <?php _e('Nouveau quiz', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz&page=biaquiz-import-export'); ?>" class="button">
                                📥 <?php _e('Importer des quiz', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=quiz_category&post_type=biaquiz'); ?>" class="button">
                                🏷️ <?php _e('Gérer les catégories', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz&page=biaquiz-statistics'); ?>" class="button">
                                📊 <?php _e('Voir les statistiques', 'biaquiz-core'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tentatives récentes -->
                    <div class="dashboard-widget">
                        <h3><?php _e('Tentatives récentes', 'biaquiz-core'); ?></h3>
                        <div class="recent-attempts">
                            <?php foreach ($recent_attempts as $attempt) : ?>
                                <div class="attempt-item">
                                    <div class="attempt-info">
                                        <strong><?php echo esc_html($attempt->quiz_title); ?></strong>
                                        <span class="attempt-time"><?php echo human_time_diff(strtotime($attempt->completed_at), current_time('timestamp')); ?> ago</span>
                                    </div>
                                    <div class="attempt-score">
                                        <span class="score <?php echo $attempt->score == $attempt->total_questions ? 'perfect' : ($attempt->score >= $attempt->total_questions * 0.75 ? 'good' : 'average'); ?>">
                                            <?php echo $attempt->score; ?>/<?php echo $attempt->total_questions; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Graphique des tentatives
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('attempts-chart').getContext('2d');
            const attemptsData = <?php echo json_encode(self::get_attempts_chart_data()); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: attemptsData.labels,
                    datasets: [{
                        label: 'Tentatives',
                        data: attemptsData.data,
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Page des statistiques
     */
    public static function statistics_page() {
        $category_stats = self::get_category_statistics();
        $quiz_performance = self::get_quiz_performance();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Statistiques détaillées', 'biaquiz-core'); ?></h1>
            
            <!-- Filtres -->
            <div class="statistics-filters">
                <form method="get">
                    <input type="hidden" name="post_type" value="biaquiz">
                    <input type="hidden" name="page" value="biaquiz-statistics">
                    
                    <select name="category" onchange="this.form.submit()">
                        <option value=""><?php _e('Toutes les catégories', 'biaquiz-core'); ?></option>
                        <?php
                        $categories = get_terms(array('taxonomy' => 'quiz_category', 'hide_empty' => false));
                        $selected_category = $_GET['category'] ?? '';
                        foreach ($categories as $category) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($category->slug),
                                selected($selected_category, $category->slug, false),
                                esc_html($category->name)
                            );
                        }
                        ?>
                    </select>
                    
                    <select name="period" onchange="this.form.submit()">
                        <option value="30" <?php selected($_GET['period'] ?? '30', '30'); ?>><?php _e('30 derniers jours', 'biaquiz-core'); ?></option>
                        <option value="90" <?php selected($_GET['period'] ?? '30', '90'); ?>><?php _e('90 derniers jours', 'biaquiz-core'); ?></option>
                        <option value="365" <?php selected($_GET['period'] ?? '30', '365'); ?>><?php _e('1 an', 'biaquiz-core'); ?></option>
                        <option value="all" <?php selected($_GET['period'] ?? '30', 'all'); ?>><?php _e('Toute la période', 'biaquiz-core'); ?></option>
                    </select>
                </form>
            </div>
            
            <!-- Statistiques par catégorie -->
            <div class="statistics-section">
                <h2><?php _e('Performance par catégorie', 'biaquiz-core'); ?></h2>
                <canvas id="category-chart" width="400" height="200"></canvas>
            </div>
            
            <!-- Tableau des quiz -->
            <div class="statistics-section">
                <h2><?php _e('Performance des quiz', 'biaquiz-core'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Quiz', 'biaquiz-core'); ?></th>
                            <th><?php _e('Catégorie', 'biaquiz-core'); ?></th>
                            <th><?php _e('Tentatives', 'biaquiz-core'); ?></th>
                            <th><?php _e('Score moyen', 'biaquiz-core'); ?></th>
                            <th><?php _e('Taux de réussite', 'biaquiz-core'); ?></th>
                            <th><?php _e('Temps moyen', 'biaquiz-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quiz_performance as $quiz) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($quiz->post_title); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo get_edit_post_link($quiz->ID); ?>"><?php _e('Modifier', 'biaquiz-core'); ?></a>
                                        </span>
                                        |
                                        <span class="view">
                                            <a href="<?php echo get_permalink($quiz->ID); ?>" target="_blank"><?php _e('Voir', 'biaquiz-core'); ?></a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($quiz->category_name); ?></td>
                                <td><?php echo number_format($quiz->total_attempts); ?></td>
                                <td>
                                    <span class="score-badge <?php echo $quiz->avg_score >= 15 ? 'good' : ($quiz->avg_score >= 10 ? 'average' : 'poor'); ?>">
                                        <?php echo number_format($quiz->avg_score, 1); ?>/20
                                    </span>
                                </td>
                                <td><?php echo number_format($quiz->success_rate, 1); ?>%</td>
                                <td><?php echo gmdate('H:i:s', $quiz->avg_time); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        // Graphique par catégorie
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('category-chart').getContext('2d');
            const categoryData = <?php echo json_encode($category_stats); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: categoryData.map(cat => cat.name),
                    datasets: [
                        {
                            label: 'Tentatives',
                            data: categoryData.map(cat => cat.attempts),
                            backgroundColor: 'rgba(0, 124, 186, 0.8)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Score moyen',
                            data: categoryData.map(cat => cat.avg_score),
                            backgroundColor: 'rgba(46, 204, 113, 0.8)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Tentatives'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Score moyen'
                            },
                            max: 20,
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Page des paramètres
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Paramètres BIAQuiz', 'biaquiz-core'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('biaquiz_settings');
                do_settings_sections('biaquiz_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Callbacks pour les sections de paramètres
     */
    public static function general_settings_callback() {
        echo '<p>' . __('Paramètres généraux de l\'application BIAQuiz.', 'biaquiz-core') . '</p>';
    }
    
    public static function quiz_settings_callback() {
        echo '<p>' . __('Paramètres par défaut pour les quiz.', 'biaquiz-core') . '</p>';
    }
    
    /**
     * Callbacks pour les champs de paramètres
     */
    public static function text_field_callback($args) {
        $options = get_option('biaquiz_options', array());
        $value = $options[$args['field']] ?? $args['default'];
        printf('<input type="text" name="biaquiz_options[%s]" value="%s" class="regular-text" />', $args['field'], esc_attr($value));
    }
    
    public static function number_field_callback($args) {
        $options = get_option('biaquiz_options', array());
        $value = $options[$args['field']] ?? $args['default'];
        printf(
            '<input type="number" name="biaquiz_options[%s]" value="%s" min="%s" max="%s" class="small-text" />',
            $args['field'],
            esc_attr($value),
            $args['min'],
            $args['max']
        );
    }
    
    public static function checkbox_field_callback($args) {
        $options = get_option('biaquiz_options', array());
        $value = $options[$args['field']] ?? $args['default'];
        printf(
            '<input type="checkbox" name="biaquiz_options[%s]" value="1" %s />',
            $args['field'],
            checked($value, true, false)
        );
    }
    
    /**
     * Personnaliser les colonnes de la liste des quiz
     */
    public static function customize_quiz_columns($columns) {
        // Réorganiser les colonnes
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['quiz_status'] = __('Statut', 'biaquiz-core');
                $new_columns['quiz_attempts'] = __('Tentatives', 'biaquiz-core');
                $new_columns['quiz_avg_score'] = __('Score moyen', 'biaquiz-core');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Remplir les colonnes personnalisées
     */
    public static function populate_quiz_columns($column, $post_id) {
        switch ($column) {
            case 'quiz_status':
                $active = get_post_meta($post_id, 'quiz_active', true);
                $validation_errors = get_post_meta($post_id, '_biaquiz_validation_errors', true);
                
                if ($validation_errors) {
                    echo '<span class="status-error">❌ Erreurs</span>';
                } elseif ($active === '0') {
                    echo '<span class="status-inactive">⏸️ Inactif</span>';
                } else {
                    echo '<span class="status-active">✅ Actif</span>';
                }
                break;
                
            case 'quiz_attempts':
                $stats = BIAQuiz_Handler::get_quiz_statistics($post_id);
                echo number_format($stats['total_attempts']);
                break;
                
            case 'quiz_avg_score':
                $stats = BIAQuiz_Handler::get_quiz_statistics($post_id);
                if ($stats['total_attempts'] > 0) {
                    $score_class = $stats['avg_score'] >= 15 ? 'good' : ($stats['avg_score'] >= 10 ? 'average' : 'poor');
                    echo '<span class="score-badge ' . $score_class . '">' . number_format($stats['avg_score'], 1) . '/20</span>';
                } else {
                    echo '-';
                }
                break;
        }
    }
    
    /**
     * Ajouter des actions rapides
     */
    public static function add_quiz_row_actions($actions, $post) {
        if ($post->post_type === 'biaquiz') {
            $active = get_post_meta($post->ID, 'quiz_active', true);
            
            if ($active === '0') {
                $actions['activate'] = sprintf(
                    '<a href="#" class="biaquiz-quick-action" data-action="activate" data-quiz-id="%d">%s</a>',
                    $post->ID,
                    __('Activer', 'biaquiz-core')
                );
            } else {
                $actions['deactivate'] = sprintf(
                    '<a href="#" class="biaquiz-quick-action" data-action="deactivate" data-quiz-id="%d">%s</a>',
                    $post->ID,
                    __('Désactiver', 'biaquiz-core')
                );
            }
            
            $actions['view_stats'] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('edit.php?post_type=biaquiz&page=biaquiz-statistics&quiz=' . $post->ID),
                __('Statistiques', 'biaquiz-core')
            );
        }
        
        return $actions;
    }
    
    /**
     * AJAX pour les actions rapides
     */
    public static function ajax_quick_action() {
        if (!current_user_can('edit_posts') || !wp_verify_nonce($_POST['nonce'], 'biaquiz_admin_nonce')) {
            wp_die(__('Accès non autorisé', 'biaquiz-core'));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $quiz_id = intval($_POST['quiz_id']);
        
        if (!$quiz_id || get_post_type($quiz_id) !== 'biaquiz') {
            wp_send_json_error(__('Quiz invalide', 'biaquiz-core'));
        }
        
        switch ($action) {
            case 'activate':
                update_post_meta($quiz_id, 'quiz_active', '1');
                wp_send_json_success(__('Quiz activé', 'biaquiz-core'));
                break;
                
            case 'deactivate':
                update_post_meta($quiz_id, 'quiz_active', '0');
                wp_send_json_success(__('Quiz désactivé', 'biaquiz-core'));
                break;
                
            default:
                wp_send_json_error(__('Action non reconnue', 'biaquiz-core'));
        }
    }
    
    /**
     * Ajouter du CSS dans l'admin
     */
    public static function admin_head() {
        $screen = get_current_screen();
        if ($screen && (strpos($screen->id, 'biaquiz') !== false || $screen->post_type === 'biaquiz')) {
            ?>
            <style>
            .status-active { color: #46b450; font-weight: bold; }
            .status-inactive { color: #dc3232; font-weight: bold; }
            .status-error { color: #dc3232; font-weight: bold; }
            .score-badge { padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; }
            .score-badge.good { background: #d4edda; color: #155724; }
            .score-badge.average { background: #fff3cd; color: #856404; }
            .score-badge.poor { background: #f8d7da; color: #721c24; }
            </style>
            <?php
        }
    }
    
    /**
     * Afficher les notices admin
     */
    public static function admin_notices() {
        // Vérifier si ACF est installé
        if (!function_exists('acf_add_local_field_group')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('BIAQuiz Core:', 'biaquiz-core'); ?></strong>
                    <?php _e('Le plugin Advanced Custom Fields (ACF) est requis pour le bon fonctionnement de BIAQuiz.', 'biaquiz-core'); ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>">
                        <?php _e('Installer ACF', 'biaquiz-core'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Obtenir les statistiques globales
     */
    private static function get_global_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT quiz_id) as total_quizzes,
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                COUNT(CASE WHEN score = total_questions THEN 1 END) as perfect_scores
            FROM $table_name
        ");
        
        $total_quizzes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'biaquiz' AND post_status = 'publish'");
        
        $success_rate = $stats && $stats->total_attempts > 0 ? round(($stats->perfect_scores / $stats->total_attempts) * 100, 1) : 0;
        
        return array(
            'total_quizzes' => $total_quizzes ?: 0,
            'total_attempts' => $stats ? $stats->total_attempts : 0,
            'avg_score' => $stats ? $stats->avg_score : 0,
            'success_rate' => $success_rate
        );
    }
    
    /**
     * Obtenir les tentatives récentes
     */
    private static function get_recent_attempts($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*, p.post_title as quiz_title
            FROM $table_name s
            INNER JOIN {$wpdb->posts} p ON s.quiz_id = p.ID
            ORDER BY s.completed_at DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Obtenir les quiz populaires
     */
    private static function get_popular_quizzes($limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT p.*, COUNT(s.id) as attempt_count, AVG(s.score) as avg_score
            FROM {$wpdb->posts} p
            INNER JOIN $table_name s ON p.ID = s.quiz_id
            WHERE p.post_type = 'biaquiz' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY attempt_count DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Obtenir les données pour le graphique des tentatives
     */
    private static function get_attempts_chart_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $results = $wpdb->get_results("
            SELECT DATE(completed_at) as date, COUNT(*) as attempts
            FROM $table_name
            WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(completed_at)
            ORDER BY date ASC
        ");
        
        $labels = array();
        $data = array();
        
        // Créer un tableau pour les 30 derniers jours
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));
            $data[] = 0;
        }
        
        // Remplir avec les données réelles
        foreach ($results as $result) {
            $index = array_search(date('d/m', strtotime($result->date)), $labels);
            if ($index !== false) {
                $data[$index] = intval($result->attempts);
            }
        }
        
        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
    
    /**
     * Obtenir les statistiques par catégorie
     */
    private static function get_category_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        return $wpdb->get_results("
            SELECT 
                t.name,
                COUNT(s.id) as attempts,
                AVG(s.score) as avg_score,
                COUNT(CASE WHEN s.score = s.total_questions THEN 1 END) as perfect_scores
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            INNER JOIN $table_name s ON p.ID = s.quiz_id
            WHERE tt.taxonomy = 'quiz_category'
            GROUP BY t.term_id
            ORDER BY attempts DESC
        ");
    }
    
    /**
     * Obtenir la performance des quiz
     */
    private static function get_quiz_performance() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        return $wpdb->get_results("
            SELECT 
                p.*,
                t.name as category_name,
                COUNT(s.id) as total_attempts,
                AVG(s.score) as avg_score,
                AVG(s.time_taken) as avg_time,
                (COUNT(CASE WHEN s.score = s.total_questions THEN 1 END) / COUNT(s.id) * 100) as success_rate
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id AND tt.taxonomy = 'quiz_category'
            LEFT JOIN $table_name s ON p.ID = s.quiz_id
            WHERE p.post_type = 'biaquiz' AND p.post_status = 'publish'
            GROUP BY p.ID
            HAVING total_attempts > 0
            ORDER BY total_attempts DESC
        ");
    }
}

