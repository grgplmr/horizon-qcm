<?php
/**
 * Template des statistiques BIAQuiz
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap biaquiz-stats">
    <h1 class="wp-heading-inline">
        <?php _e('Statistiques BIAQuiz', 'biaquiz-core'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Statistiques générales -->
    <div class="stats-overview">
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($general_stats->total_attempts ?? 0); ?></div>
                    <div class="stat-label"><?php _e('Tentatives totales', 'biaquiz-core'); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($general_stats->avg_score ?? 0, 1); ?>/20</div>
                    <div class="stat-label"><?php _e('Score moyen', 'biaquiz-core'); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $general_stats->max_score ?? 0; ?>/20</div>
                    <div class="stat-label"><?php _e('Meilleur score', 'biaquiz-core'); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-content">
                    <div class="stat-number">
                        <?php 
                        if ($general_stats && $general_stats->avg_time) {
                            echo gmdate('i:s', $general_stats->avg_time);
                        } else {
                            echo '00:00';
                        }
                        ?>
                    </div>
                    <div class="stat-label"><?php _e('Temps moyen', 'biaquiz-core'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stats-content">
        <div class="stats-left">
            <!-- Quiz les plus populaires -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Quiz les plus populaires', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (!empty($popular_quizzes)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Quiz', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Tentatives', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Score moyen', 'biaquiz-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_quizzes as $quiz) : ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <a href="<?php echo get_edit_post_link($quiz->quiz_id); ?>">
                                                    <?php echo esc_html($quiz->post_title); ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td><?php echo number_format($quiz->attempts); ?></td>
                                        <td>
                                            <span class="score-badge score-<?php echo $quiz->avg_score >= 16 ? 'excellent' : ($quiz->avg_score >= 12 ? 'good' : 'needs-work'); ?>">
                                                <?php echo number_format($quiz->avg_score, 1); ?>/20
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('Aucune donnée disponible.', 'biaquiz-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Activité récente -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Activité des 30 derniers jours', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (!empty($daily_stats)) : ?>
                        <div class="activity-chart">
                            <canvas id="activityChart" width="400" height="200"></canvas>
                        </div>
                        
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Tentatives', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Score moyen', 'biaquiz-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($daily_stats, 0, 10) as $day) : ?>
                                    <tr>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($day->date)); ?></td>
                                        <td><?php echo $day->attempts; ?></td>
                                        <td><?php echo number_format($day->avg_score, 1); ?>/20</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('Aucune activité récente.', 'biaquiz-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="stats-right">
            <!-- Statistiques par catégorie -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Performance par catégorie', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'quiz_category',
                        'hide_empty' => false
                    ));
                    
                    if (!empty($categories)) :
                    ?>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Catégorie', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Quiz', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Tentatives', 'biaquiz-core'); ?></th>
                                    <th><?php _e('Score moyen', 'biaquiz-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category) : 
                                    $category_stats = $wpdb->get_row($wpdb->prepare("
                                        SELECT 
                                            COUNT(DISTINCT s.quiz_id) as quiz_count,
                                            COUNT(*) as attempts,
                                            AVG(s.score) as avg_score
                                        FROM {$wpdb->prefix}biaquiz_stats s
                                        INNER JOIN {$wpdb->posts} p ON s.quiz_id = p.ID
                                        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                        WHERE tt.term_id = %d
                                    ", $category->term_id));
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($category->name); ?></strong>
                                        </td>
                                        <td><?php echo $category_stats->quiz_count ?? 0; ?></td>
                                        <td><?php echo $category_stats->attempts ?? 0; ?></td>
                                        <td>
                                            <?php if ($category_stats && $category_stats->avg_score) : ?>
                                                <span class="score-badge score-<?php echo $category_stats->avg_score >= 16 ? 'excellent' : ($category_stats->avg_score >= 12 ? 'good' : 'needs-work'); ?>">
                                                    <?php echo number_format($category_stats->avg_score, 1); ?>/20
                                                </span>
                                            <?php else : ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('Aucune catégorie trouvée.', 'biaquiz-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Actions', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=biaquiz-stats&export=csv'); ?>" class="button">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Exporter les statistiques (CSV)', 'biaquiz-core'); ?>
                        </a>
                    </p>
                    
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=biaquiz-stats&clear=confirm'); ?>" class="button button-secondary" onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir effacer toutes les statistiques ?', 'biaquiz-core'); ?>')">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Effacer les statistiques', 'biaquiz-core'); ?>
                        </a>
                    </p>
                    
                    <hr>
                    
                    <h4><?php _e('Période d\'analyse', 'biaquiz-core'); ?></h4>
                    <form method="get">
                        <input type="hidden" name="page" value="biaquiz-stats">
                        <select name="period">
                            <option value="30" <?php selected($_GET['period'] ?? '30', '30'); ?>><?php _e('30 derniers jours', 'biaquiz-core'); ?></option>
                            <option value="90" <?php selected($_GET['period'] ?? '30', '90'); ?>><?php _e('90 derniers jours', 'biaquiz-core'); ?></option>
                            <option value="365" <?php selected($_GET['period'] ?? '30', '365'); ?>><?php _e('1 an', 'biaquiz-core'); ?></option>
                            <option value="all" <?php selected($_GET['period'] ?? '30', 'all'); ?>><?php _e('Toute la période', 'biaquiz-core'); ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php _e('Filtrer', 'biaquiz-core'); ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.biaquiz-stats .stats-overview {
    margin: 20px 0;
}

.biaquiz-stats .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.biaquiz-stats .stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.biaquiz-stats .stat-icon {
    font-size: 2em;
    opacity: 0.7;
}

.biaquiz-stats .stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #1d2327;
    line-height: 1;
}

.biaquiz-stats .stat-label {
    color: #646970;
    font-size: 0.9em;
}

.biaquiz-stats .stats-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.biaquiz-stats .score-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.9em;
    font-weight: 500;
}

.biaquiz-stats .score-excellent {
    background: #d1e7dd;
    color: #0f5132;
}

.biaquiz-stats .score-good {
    background: #fff3cd;
    color: #664d03;
}

.biaquiz-stats .score-needs-work {
    background: #f8d7da;
    color: #721c24;
}

.biaquiz-stats .activity-chart {
    margin: 20px 0;
    text-align: center;
}

@media (max-width: 782px) {
    .biaquiz-stats .stats-content {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if (!empty($daily_stats)) : ?>
<script>
// Graphique d'activité simple (sans dépendance externe)
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('activityChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const data = <?php echo json_encode(array_reverse($daily_stats)); ?>;
    
    // Configuration simple du graphique
    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;
    const chartWidth = width - 2 * padding;
    const chartHeight = height - 2 * padding;
    
    // Trouver les valeurs min/max
    const maxAttempts = Math.max(...data.map(d => parseInt(d.attempts)));
    const minAttempts = 0;
    
    // Dessiner les axes
    ctx.strokeStyle = '#c3c4c7';
    ctx.lineWidth = 1;
    
    // Axe Y
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, height - padding);
    ctx.stroke();
    
    // Axe X
    ctx.beginPath();
    ctx.moveTo(padding, height - padding);
    ctx.lineTo(width - padding, height - padding);
    ctx.stroke();
    
    // Dessiner la ligne
    if (data.length > 1) {
        ctx.strokeStyle = '#1e40af';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * chartWidth;
            const y = height - padding - ((parseInt(point.attempts) - minAttempts) / (maxAttempts - minAttempts)) * chartHeight;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Points
        ctx.fillStyle = '#1e40af';
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * chartWidth;
            const y = height - padding - ((parseInt(point.attempts) - minAttempts) / (maxAttempts - minAttempts)) * chartHeight;
            
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, 2 * Math.PI);
            ctx.fill();
        });
    }
    
    // Labels
    ctx.fillStyle = '#646970';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    
    // Label Y max
    ctx.textAlign = 'right';
    ctx.fillText(maxAttempts.toString(), padding - 5, padding + 5);
    
    // Label Y min
    ctx.fillText('0', padding - 5, height - padding + 5);
});
</script>
<?php endif; ?>

