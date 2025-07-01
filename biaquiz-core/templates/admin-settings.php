<?php
/**
 * Template des paramètres BIAQuiz
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap biaquiz-settings">
    <h1 class="wp-heading-inline">
        <?php _e('Paramètres BIAQuiz', 'biaquiz-core'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <form method="post" action="">
        <?php wp_nonce_field('biaquiz_settings'); ?>
        
        <div class="settings-sections">
            <!-- Paramètres généraux -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Paramètres généraux', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="default_time_limit"><?php _e('Temps limite par défaut', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="default_time_limit" name="default_time_limit" 
                                       value="<?php echo esc_attr($settings['default_time_limit']); ?>" 
                                       min="0" max="180" class="small-text"> minutes
                                <p class="description">
                                    <?php _e('Temps limite par défaut pour les nouveaux quiz (0 = pas de limite)', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Comportement des quiz', 'biaquiz-core'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="allow_retries" value="1" 
                                               <?php checked($settings['allow_retries']); ?>>
                                        <?php _e('Autoriser les tentatives multiples', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Les utilisateurs peuvent refaire les quiz autant de fois qu\'ils le souhaitent', 'biaquiz-core'); ?>
                                    </p>
                                    
                                    <label>
                                        <input type="checkbox" name="require_perfect_score" value="1" 
                                               <?php checked($settings['require_perfect_score']); ?>>
                                        <?php _e('Exiger un score parfait', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Les questions incorrectes sont répétées jusqu\'à obtention de 20/20', 'biaquiz-core'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Paramètres d'affichage -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Affichage des quiz', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Options d\'affichage', 'biaquiz-core'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="show_explanations" value="1" 
                                               <?php checked($settings['show_explanations']); ?>>
                                        <?php _e('Afficher les explications', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Afficher les explications après chaque réponse', 'biaquiz-core'); ?>
                                    </p>
                                    
                                    <label>
                                        <input type="checkbox" name="shuffle_questions" value="1" 
                                               <?php checked($settings['shuffle_questions']); ?>>
                                        <?php _e('Mélanger les questions', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Présenter les questions dans un ordre aléatoire', 'biaquiz-core'); ?>
                                    </p>
                                    
                                    <label>
                                        <input type="checkbox" name="shuffle_answers" value="1" 
                                               <?php checked($settings['shuffle_answers']); ?>>
                                        <?php _e('Mélanger les réponses', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Présenter les réponses dans un ordre aléatoire', 'biaquiz-core'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Paramètres de données -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Gestion des données', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Statistiques', 'biaquiz-core'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="save_stats" value="1" 
                                               <?php checked($settings['save_stats']); ?>>
                                        <?php _e('Enregistrer les statistiques', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Sauvegarder les scores et temps de completion des utilisateurs', 'biaquiz-core'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="data_retention"><?php _e('Rétention des données', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <select id="data_retention" name="data_retention">
                                    <option value="0" <?php selected($settings['data_retention'] ?? 0, 0); ?>>
                                        <?php _e('Conserver indéfiniment', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="30" <?php selected($settings['data_retention'] ?? 0, 30); ?>>
                                        <?php _e('30 jours', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="90" <?php selected($settings['data_retention'] ?? 0, 90); ?>>
                                        <?php _e('90 jours', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="365" <?php selected($settings['data_retention'] ?? 0, 365); ?>>
                                        <?php _e('1 an', 'biaquiz-core'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Durée de conservation des statistiques des utilisateurs', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Paramètres avancés -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Paramètres avancés', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cache_duration"><?php _e('Durée du cache', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <select id="cache_duration" name="cache_duration">
                                    <option value="0" <?php selected($settings['cache_duration'] ?? 3600, 0); ?>>
                                        <?php _e('Désactivé', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="300" <?php selected($settings['cache_duration'] ?? 3600, 300); ?>>
                                        <?php _e('5 minutes', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="1800" <?php selected($settings['cache_duration'] ?? 3600, 1800); ?>>
                                        <?php _e('30 minutes', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="3600" <?php selected($settings['cache_duration'] ?? 3600, 3600); ?>>
                                        <?php _e('1 heure', 'biaquiz-core'); ?>
                                    </option>
                                    <option value="86400" <?php selected($settings['cache_duration'] ?? 3600, 86400); ?>>
                                        <?php _e('24 heures', 'biaquiz-core'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Durée de mise en cache des données de quiz pour améliorer les performances', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Mode debug', 'biaquiz-core'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="debug_mode" value="1" 
                                               <?php checked($settings['debug_mode'] ?? false); ?>>
                                        <?php _e('Activer le mode debug', 'biaquiz-core'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Affiche des informations de débogage dans la console du navigateur', 'biaquiz-core'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="api_rate_limit"><?php _e('Limite de taux API', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="api_rate_limit" name="api_rate_limit" 
                                       value="<?php echo esc_attr($settings['api_rate_limit'] ?? 60); ?>" 
                                       min="10" max="1000" class="small-text"> requêtes/minute
                                <p class="description">
                                    <?php _e('Nombre maximum de requêtes AJAX par minute par utilisateur', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Intégrations -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Intégrations', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="google_analytics"><?php _e('Google Analytics', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="google_analytics" name="google_analytics" 
                                       value="<?php echo esc_attr($settings['google_analytics'] ?? ''); ?>" 
                                       class="regular-text" placeholder="G-XXXXXXXXXX">
                                <p class="description">
                                    <?php _e('ID de suivi Google Analytics pour les événements de quiz', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="webhook_url"><?php _e('Webhook de notification', 'biaquiz-core'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="webhook_url" name="webhook_url" 
                                       value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" 
                                       class="regular-text" placeholder="https://example.com/webhook">
                                <p class="description">
                                    <?php _e('URL pour recevoir des notifications lors de la completion de quiz', 'biaquiz-core'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <?php submit_button(__('Enregistrer les paramètres', 'biaquiz-core')); ?>
    </form>
    
    <!-- Actions de maintenance -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><?php _e('Maintenance', 'biaquiz-core'); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e('Actions de maintenance et de diagnostic.', 'biaquiz-core'); ?></p>
            
            <div class="maintenance-actions">
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=biaquiz-settings&action=clear_cache'), 'biaquiz_maintenance'); ?>" 
                       class="button">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Vider le cache', 'biaquiz-core'); ?>
                    </a>
                </p>
                
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=biaquiz-settings&action=recount_stats'), 'biaquiz_maintenance'); ?>" 
                       class="button">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Recalculer les statistiques', 'biaquiz-core'); ?>
                    </a>
                </p>
                
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=biaquiz-settings&action=validate_all'), 'biaquiz_maintenance'); ?>" 
                       class="button">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Valider tous les quiz', 'biaquiz-core'); ?>
                    </a>
                </p>
                
                <hr>
                
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=biaquiz-settings&action=reset_settings'), 'biaquiz_maintenance'); ?>" 
                       class="button button-secondary"
                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?', 'biaquiz-core'); ?>')">
                        <span class="dashicons dashicons-undo"></span>
                        <?php _e('Réinitialiser les paramètres', 'biaquiz-core'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.biaquiz-settings .settings-sections {
    margin: 20px 0;
}

.biaquiz-settings .postbox {
    margin-bottom: 20px;
}

.biaquiz-settings .form-table th {
    width: 250px;
}

.biaquiz-settings fieldset label {
    display: block;
    margin-bottom: 10px;
}

.biaquiz-settings .maintenance-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.biaquiz-settings .maintenance-actions .button {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    padding: 10px;
}

.biaquiz-settings .description {
    margin-top: 5px !important;
    font-style: italic;
    color: #646970;
}

@media (max-width: 782px) {
    .biaquiz-settings .maintenance-actions {
        grid-template-columns: 1fr;
    }
}
</style>

