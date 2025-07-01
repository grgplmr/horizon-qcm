<?php
/**
 * Template du tableau de bord BIAQuiz
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap biaquiz-admin-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('Tableau de bord BIAQuiz', 'biaquiz-core'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Statistiques principales -->
    <div class="biaquiz-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total_quizzes']; ?></div>
                <div class="stat-label"><?php _e('Quiz publiés', 'biaquiz-core'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['active_quizzes']; ?></div>
                <div class="stat-label"><?php _e('Quiz actifs', 'biaquiz-core'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📂</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                <div class="stat-label"><?php _e('Catégories', 'biaquiz-core'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats['total_attempts']); ?></div>
                <div class="stat-label"><?php _e('Tentatives totales', 'biaquiz-core'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="biaquiz-dashboard-content">
        <div class="dashboard-left">
            <!-- Quiz récents -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Quiz récents', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (!empty($recent_quizzes)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Titre', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Catégorie', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Statut', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Date', 'biaquiz-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_quizzes as $quiz) : 
                                    $category = wp_get_post_terms($quiz->ID, 'quiz_category');
                                    $is_active = get_post_meta($quiz->ID, 'quiz_active', true);
                                ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <a href="<?php echo get_edit_post_link($quiz->ID); ?>">
                                                    <?php echo esc_html($quiz->post_title); ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($category)) {
                                                echo esc_html($category[0]->name);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($is_active === '1') : ?>
                                                <span class="status-active">✓ <?php _e('Actif', 'biaquiz-core'); ?></span>
                                            <?php else : ?>
                                                <span class="status-inactive">✗ <?php _e('Inactif', 'biaquiz-core'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo get_the_date('d/m/Y', $quiz->ID); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p class="textright">
                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz'); ?>" class="button">
                                <?php _e('Voir tous les quiz', 'biaquiz-core'); ?>
                            </a>
                        </p>
                    <?php else : ?>
                        <p><?php _e('Aucun quiz trouvé.', 'biaquiz-core'); ?></p>
                        <p>
                            <a href="<?php echo admin_url('post-new.php?post_type=biaquiz'); ?>" class="button button-primary">
                                <?php _e('Créer votre premier quiz', 'biaquiz-core'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Actions rapides', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=biaquiz'); ?>" class="button button-primary button-large">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Nouveau Quiz', 'biaquiz-core'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=biaquiz-import-export'); ?>" class="button button-large">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Importer des Quiz', 'biaquiz-core'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=quiz_category&post_type=biaquiz'); ?>" class="button button-large">
                            <span class="dashicons dashicons-category"></span>
                            <?php _e('Gérer les Catégories', 'biaquiz-core'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=biaquiz-stats'); ?>" class="button button-large">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Voir les Statistiques', 'biaquiz-core'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-right">
            <!-- Statistiques par catégorie -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Quiz par catégorie', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (!empty($categories)) : ?>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Catégorie', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Quiz', 'biaquiz-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category) : 
                                    $quiz_count = $wpdb->get_var($wpdb->prepare("
                                        SELECT COUNT(*) FROM {$wpdb->posts} p
                                        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                        WHERE p.post_type = 'biaquiz'
                                        AND p.post_status = 'publish'
                                        AND tt.term_id = %d
                                    ", $category->term_id));
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz&quiz_category=' . $category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $quiz_count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('Aucune catégorie trouvée.', 'biaquiz-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations système -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Informations système', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Version du plugin', 'biaquiz-core'); ?></th>
                            <td><?php echo BIAQUIZ_CORE_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Version WordPress', 'biaquiz-core'); ?></th>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Advanced Custom Fields', 'biaquiz-core'); ?></th>
                            <td>
                                <?php if (function_exists('get_field')) : ?>
                                    <span style="color: green;">✓ <?php _e('Activé', 'biaquiz-core'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php _e('Non activé', 'biaquiz-core'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Table des statistiques', 'biaquiz-core'); ?></th>
                            <td>
                                <?php 
                                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}biaquiz_stats'");
                                if ($table_exists) : ?>
                                    <span style="color: green;">✓ <?php _e('Créée', 'biaquiz-core'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php _e('Non créée', 'biaquiz-core'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=biaquiz-settings'); ?>" class="button">
                            <?php _e('Paramètres', 'biaquiz-core'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.biaquiz-admin-dashboard .biaquiz-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.biaquiz-admin-dashboard .stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.biaquiz-admin-dashboard .stat-icon {
    font-size: 2em;
    opacity: 0.7;
}

.biaquiz-admin-dashboard .stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #1d2327;
    line-height: 1;
}

.biaquiz-admin-dashboard .stat-label {
    color: #646970;
    font-size: 0.9em;
}

.biaquiz-admin-dashboard .biaquiz-dashboard-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.biaquiz-admin-dashboard .quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.biaquiz-admin-dashboard .quick-actions .button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    text-align: center;
}

.biaquiz-admin-dashboard .status-active {
    color: #00a32a;
    font-weight: 500;
}

.biaquiz-admin-dashboard .status-inactive {
    color: #d63638;
    font-weight: 500;
}

@media (max-width: 782px) {
    .biaquiz-admin-dashboard .biaquiz-dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .biaquiz-admin-dashboard .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

