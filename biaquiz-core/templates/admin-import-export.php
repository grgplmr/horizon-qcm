<?php
/**
 * Template d'import/export BIAQuiz
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap biaquiz-import-export">
    <h1 class="wp-heading-inline">
        <?php _e('Import/Export de Quiz', 'biaquiz-core'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <div class="import-export-content">
        <div class="import-section">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Importer des Quiz', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e('Importez des quiz depuis un fichier CSV ou JSON. Le fichier doit respecter le format BIAQuiz.', 'biaquiz-core'); ?></p>
                    
                    <form method="post" enctype="multipart/form-data" class="import-form" id="import-form">
                        <?php wp_nonce_field('biaquiz_import'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php _e('Fichier à importer', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="import_file" name="import_file" accept=".csv,.json" required>
                                    <p class="description">
                                        <?php _e('Formats acceptés : CSV, JSON (max 10 MB)', 'biaquiz-core'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="import_category"><?php _e('Catégorie de destination', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <select id="import_category" name="import_category" required>
                                        <option value=""><?php _e('Sélectionner une catégorie', 'biaquiz-core'); ?></option>
                                        <?php 
                                        $categories = get_terms(array(
                                            'taxonomy' => 'quiz_category',
                                            'hide_empty' => false
                                        ));
                                        foreach ($categories as $category) :
                                        ?>
                                            <option value="<?php echo esc_attr($category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Tous les quiz importés seront assignés à cette catégorie', 'biaquiz-core'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="import_options"><?php _e('Options d\'import', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="import_options[]" value="auto_number" checked>
                                            <?php _e('Numéroter automatiquement les quiz', 'biaquiz-core'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="import_options[]" value="activate" checked>
                                            <?php _e('Activer les quiz après import', 'biaquiz-core'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="import_options[]" value="validate">
                                            <?php _e('Valider les questions avant import', 'biaquiz-core'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="import_quiz" class="button button-primary" value="<?php _e('Importer', 'biaquiz-core'); ?>">
                        </p>
                    </form>
                    
                    <div class="import-help">
                        <h4><?php _e('Format des fichiers', 'biaquiz-core'); ?></h4>
                        <p><?php _e('Téléchargez les modèles pour vous aider :', 'biaquiz-core'); ?></p>
                        <p>
                            <a href="<?php echo BIAQUIZ_CORE_PLUGIN_URL . 'templates/quiz-template.csv'; ?>" class="button" download>
                                <?php _e('Modèle CSV', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo BIAQUIZ_CORE_PLUGIN_URL . 'templates/quiz-template.json'; ?>" class="button" download>
                                <?php _e('Modèle JSON', 'biaquiz-core'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="export-section">
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Exporter des Quiz', 'biaquiz-core'); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e('Exportez vos quiz existants pour sauvegarde ou transfert vers un autre site.', 'biaquiz-core'); ?></p>
                    
                    <form method="post" class="export-form" id="export-form">
                        <?php wp_nonce_field('biaquiz_export'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="export_category"><?php _e('Catégorie à exporter', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <select id="export_category" name="export_category">
                                        <option value=""><?php _e('Toutes les catégories', 'biaquiz-core'); ?></option>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="export_format"><?php _e('Format d\'export', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <select id="export_format" name="export_format">
                                        <option value="json"><?php _e('JSON (recommandé)', 'biaquiz-core'); ?></option>
                                        <option value="csv"><?php _e('CSV', 'biaquiz-core'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php _e('JSON préserve mieux la structure des données', 'biaquiz-core'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="export_options"><?php _e('Options d\'export', 'biaquiz-core'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="export_options[]" value="include_stats">
                                            <?php _e('Inclure les statistiques', 'biaquiz-core'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="export_options[]" value="include_images">
                                            <?php _e('Inclure les images (URLs)', 'biaquiz-core'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="export_options[]" value="active_only" checked>
                                            <?php _e('Quiz actifs seulement', 'biaquiz-core'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="export_quiz" class="button button-primary" value="<?php _e('Exporter', 'biaquiz-core'); ?>">
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Historique des imports/exports -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><?php _e('Historique', 'biaquiz-core'); ?></h2>
        </div>
        <div class="inside">
            <?php
            $history = get_option('biaquiz_import_export_history', array());
            if (!empty($history)) :
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'biaquiz-core'); ?></th>
                            <th><?php _e('Action', 'biaquiz-core'); ?></th>
                            <th><?php _e('Fichier', 'biaquiz-core'); ?></th>
                            <th><?php _e('Résultat', 'biaquiz-core'); ?></th>
                            <th><?php _e('Utilisateur', 'biaquiz-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse(array_slice($history, -10)) as $entry) : ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry['timestamp']); ?></td>
                                <td>
                                    <?php if ($entry['action'] === 'import') : ?>
                                        <span class="dashicons dashicons-upload"></span> <?php _e('Import', 'biaquiz-core'); ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-download"></span> <?php _e('Export', 'biaquiz-core'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($entry['filename']); ?></td>
                                <td>
                                    <?php if ($entry['success']) : ?>
                                        <span style="color: green;">✓ <?php _e('Succès', 'biaquiz-core'); ?></span>
                                        <?php if (isset($entry['count'])) : ?>
                                            <br><small><?php printf(_n('%d quiz', '%d quiz', $entry['count'], 'biaquiz-core'), $entry['count']); ?></small>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span style="color: red;">✗ <?php _e('Échec', 'biaquiz-core'); ?></span>
                                        <?php if (isset($entry['error'])) : ?>
                                            <br><small><?php echo esc_html($entry['error']); ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($entry['user']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('Aucun historique d\'import/export.', 'biaquiz-core'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.biaquiz-import-export .import-export-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.biaquiz-import-export .import-help {
    background: #f0f6fc;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
}

.biaquiz-import-export .import-help h4 {
    margin-top: 0;
    color: #1d2327;
}

.biaquiz-import-export .form-table th {
    width: 200px;
}

.biaquiz-import-export fieldset label {
    display: block;
    margin-bottom: 8px;
}

@media (max-width: 782px) {
    .biaquiz-import-export .import-export-content {
        grid-template-columns: 1fr;
    }
}
</style>

