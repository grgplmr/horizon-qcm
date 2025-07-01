<?php
/**
 * Gestion des Taxonomies pour BIAQuiz
 *
 * @package BIAQuiz_Core
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les taxonomies
 */
class BIAQuiz_Taxonomies {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        add_action('quiz_category_add_form_fields', array(__CLASS__, 'add_category_fields'));
        add_action('quiz_category_edit_form_fields', array(__CLASS__, 'edit_category_fields'));
        add_action('created_quiz_category', array(__CLASS__, 'save_category_fields'));
        add_action('edited_quiz_category', array(__CLASS__, 'save_category_fields'));
        add_filter('manage_edit-quiz_category_columns', array(__CLASS__, 'add_category_columns'));
        add_filter('manage_quiz_category_custom_column', array(__CLASS__, 'populate_category_columns'), 10, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enregistrer les taxonomies
     */
    public static function register_taxonomies() {
        // Taxonomie pour les catégories de quiz
        $labels = array(
            'name'                       => _x('Catégories de Quiz', 'Taxonomy General Name', 'biaquiz-core'),
            'singular_name'              => _x('Catégorie de Quiz', 'Taxonomy Singular Name', 'biaquiz-core'),
            'menu_name'                  => __('Catégories', 'biaquiz-core'),
            'all_items'                  => __('Toutes les Catégories', 'biaquiz-core'),
            'parent_item'                => __('Catégorie Parente', 'biaquiz-core'),
            'parent_item_colon'          => __('Catégorie Parente:', 'biaquiz-core'),
            'new_item_name'              => __('Nouvelle Catégorie', 'biaquiz-core'),
            'add_new_item'               => __('Ajouter une Nouvelle Catégorie', 'biaquiz-core'),
            'edit_item'                  => __('Modifier la Catégorie', 'biaquiz-core'),
            'update_item'                => __('Mettre à jour la Catégorie', 'biaquiz-core'),
            'view_item'                  => __('Voir la Catégorie', 'biaquiz-core'),
            'separate_items_with_commas' => __('Séparer les catégories par des virgules', 'biaquiz-core'),
            'add_or_remove_items'        => __('Ajouter ou supprimer des catégories', 'biaquiz-core'),
            'choose_from_most_used'      => __('Choisir parmi les plus utilisées', 'biaquiz-core'),
            'popular_items'              => __('Catégories Populaires', 'biaquiz-core'),
            'search_items'               => __('Rechercher des Catégories', 'biaquiz-core'),
            'not_found'                  => __('Aucune catégorie trouvée', 'biaquiz-core'),
            'no_terms'                   => __('Aucune catégorie', 'biaquiz-core'),
            'items_list'                 => __('Liste des catégories', 'biaquiz-core'),
            'items_list_navigation'      => __('Navigation de la liste des catégories', 'biaquiz-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rest_base'                  => 'quiz-categories',
            'rest_controller_class'      => 'WP_REST_Terms_Controller',
            'rewrite'                    => array(
                'slug'         => 'category',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'capabilities'               => array(
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ),
        );
        
        register_taxonomy('quiz_category', array('biaquiz'), $args);
    }
    
    /**
     * Ajouter des champs personnalisés pour les catégories (nouveau)
     */
    public static function add_category_fields($taxonomy) {
        ?>
        <div class="form-field term-icon-wrap">
            <label for="category_icon"><?php _e('Icône', 'biaquiz-core'); ?></label>
            <input type="text" id="category_icon" name="category_icon" value="" size="40" placeholder="✈️" />
            <p><?php _e('Emoji ou icône représentant cette catégorie', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field term-color-wrap">
            <label for="category_color"><?php _e('Couleur', 'biaquiz-core'); ?></label>
            <input type="color" id="category_color" name="category_color" value="#1e40af" />
            <p><?php _e('Couleur associée à cette catégorie', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field term-order-wrap">
            <label for="category_order"><?php _e('Ordre d\'affichage', 'biaquiz-core'); ?></label>
            <input type="number" id="category_order" name="category_order" value="0" min="0" />
            <p><?php _e('Ordre d\'affichage sur la page d\'accueil (0 = premier)', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field term-active-wrap">
            <label for="category_active">
                <input type="checkbox" id="category_active" name="category_active" value="1" checked />
                <?php _e('Catégorie active', 'biaquiz-core'); ?>
            </label>
            <p><?php _e('Décocher pour masquer cette catégorie temporairement', 'biaquiz-core'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Modifier des champs personnalisés pour les catégories (édition)
     */
    public static function edit_category_fields($term, $taxonomy) {
        $icon = get_term_meta($term->term_id, 'category_icon', true);
        $color = get_term_meta($term->term_id, 'category_color', true);
        $order = get_term_meta($term->term_id, 'category_order', true);
        $active = get_term_meta($term->term_id, 'category_active', true);
        
        ?>
        <tr class="form-field term-icon-wrap">
            <th scope="row"><label for="category_icon"><?php _e('Icône', 'biaquiz-core'); ?></label></th>
            <td>
                <input type="text" id="category_icon" name="category_icon" value="<?php echo esc_attr($icon); ?>" size="40" placeholder="✈️" />
                <p class="description"><?php _e('Emoji ou icône représentant cette catégorie', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-color-wrap">
            <th scope="row"><label for="category_color"><?php _e('Couleur', 'biaquiz-core'); ?></label></th>
            <td>
                <input type="color" id="category_color" name="category_color" value="<?php echo esc_attr($color ?: '#1e40af'); ?>" />
                <p class="description"><?php _e('Couleur associée à cette catégorie', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-order-wrap">
            <th scope="row"><label for="category_order"><?php _e('Ordre d\'affichage', 'biaquiz-core'); ?></label></th>
            <td>
                <input type="number" id="category_order" name="category_order" value="<?php echo esc_attr($order ?: 0); ?>" min="0" />
                <p class="description"><?php _e('Ordre d\'affichage sur la page d\'accueil (0 = premier)', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-active-wrap">
            <th scope="row"><label for="category_active"><?php _e('Statut', 'biaquiz-core'); ?></label></th>
            <td>
                <label for="category_active">
                    <input type="checkbox" id="category_active" name="category_active" value="1" <?php checked($active, '1'); ?> />
                    <?php _e('Catégorie active', 'biaquiz-core'); ?>
                </label>
                <p class="description"><?php _e('Décocher pour masquer cette catégorie temporairement', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Sauvegarder les champs personnalisés des catégories
     */
    public static function save_category_fields($term_id) {
        if (isset($_POST['category_icon'])) {
            update_term_meta($term_id, 'category_icon', sanitize_text_field($_POST['category_icon']));
        }
        
        if (isset($_POST['category_color'])) {
            update_term_meta($term_id, 'category_color', sanitize_hex_color($_POST['category_color']));
        }
        
        if (isset($_POST['category_order'])) {
            update_term_meta($term_id, 'category_order', intval($_POST['category_order']));
        }
        
        if (isset($_POST['category_active'])) {
            update_term_meta($term_id, 'category_active', '1');
        } else {
            update_term_meta($term_id, 'category_active', '0');
        }
    }
    
    /**
     * Ajouter des colonnes personnalisées dans l'admin des catégories
     */
    public static function add_category_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'description') {
                $new_columns['category_icon'] = __('Icône', 'biaquiz-core');
                $new_columns['category_color'] = __('Couleur', 'biaquiz-core');
                $new_columns['category_order'] = __('Ordre', 'biaquiz-core');
                $new_columns['category_quiz_count'] = __('Quiz', 'biaquiz-core');
                $new_columns['category_status'] = __('Statut', 'biaquiz-core');
            }
            $new_columns[$key] = $value;
        }
        
        return $new_columns;
    }
    
    /**
     * Remplir les colonnes personnalisées des catégories
     */
    public static function populate_category_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'category_icon':
                $icon = get_term_meta($term_id, 'category_icon', true);
                return $icon ? '<span style="font-size: 1.5em;">' . esc_html($icon) . '</span>' : '-';
                
            case 'category_color':
                $color = get_term_meta($term_id, 'category_color', true);
                if ($color) {
                    return '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border-radius: 50%; border: 1px solid #ccc;"></span>';
                }
                return '-';
                
            case 'category_order':
                $order = get_term_meta($term_id, 'category_order', true);
                return $order !== '' ? intval($order) : '0';
                
            case 'category_quiz_count':
                $term = get_term($term_id);
                $count = $term->count;
                if ($count > 0) {
                    $link = admin_url('edit.php?post_type=biaquiz&quiz_category=' . $term->slug);
                    return '<a href="' . esc_url($link) . '">' . sprintf(_n('%d quiz', '%d quiz', $count, 'biaquiz-core'), $count) . '</a>';
                }
                return '0';
                
            case 'category_status':
                $active = get_term_meta($term_id, 'category_active', true);
                if ($active === '1' || $active === '') { // Par défaut actif
                    return '<span style="color: green;">●</span> ' . __('Actif', 'biaquiz-core');
                } else {
                    return '<span style="color: red;">●</span> ' . __('Inactif', 'biaquiz-core');
                }
        }
        
        return $content;
    }
    
    /**
     * Enregistrer les scripts admin
     */
    public static function enqueue_admin_scripts($hook) {
        if ($hook === 'edit-tags.php' || $hook === 'term.php') {
            global $taxonomy;
            if ($taxonomy === 'quiz_category') {
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_style('wp-color-picker');
                
                wp_add_inline_script('wp-color-picker', '
                    jQuery(document).ready(function($) {
                        $("#category_color").wpColorPicker();
                    });
                ');
            }
        }
    }
    
    /**
     * Obtenir les catégories actives triées par ordre
     */
    public static function get_active_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'quiz_category',
            'hide_empty' => false,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'category_active',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'category_active',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        if (is_wp_error($categories)) {
            return array();
        }
        
        // Trier par ordre d'affichage
        usort($categories, function($a, $b) {
            $order_a = get_term_meta($a->term_id, 'category_order', true);
            $order_b = get_term_meta($b->term_id, 'category_order', true);
            
            $order_a = $order_a !== '' ? intval($order_a) : 999;
            $order_b = $order_b !== '' ? intval($order_b) : 999;
            
            return $order_a - $order_b;
        });
        
        return $categories;
    }
    
    /**
     * Obtenir les métadonnées d'une catégorie
     */
    public static function get_category_meta($term_id) {
        return array(
            'icon' => get_term_meta($term_id, 'category_icon', true),
            'color' => get_term_meta($term_id, 'category_color', true) ?: '#1e40af',
            'order' => get_term_meta($term_id, 'category_order', true) ?: 0,
            'active' => get_term_meta($term_id, 'category_active', true) !== '0'
        );
    }
}

