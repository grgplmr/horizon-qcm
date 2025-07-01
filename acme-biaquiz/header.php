<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ACME BIAQuiz - Entraînement au Brevet d'Initiation à l'Aéronautique. Quiz interactifs, correction immédiate, sans inscription.">
    <meta name="keywords" content="BIA, Brevet Initiation Aéronautique, quiz, aviation, aéronautique, formation">
    <meta name="author" content="ACME">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✈️</text></svg>">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="site-branding">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="logo" rel="home">
                        <span class="logo-icon">✈️</span>
                        <span class="logo-text">ACME BIAQuiz</span>
                    </a>
                    <?php if (is_front_page()) : ?>
                        <p class="site-description" style="margin: 0; font-size: 0.875rem; opacity: 0.8;">
                            Entraînement au Brevet d'Initiation à l'Aéronautique
                        </p>
                    <?php endif; ?>
                </div>

                <nav class="main-navigation" style="display: flex; align-items: center; gap: 1rem;">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'container'      => false,
                        'menu_class'     => 'nav-menu',
                        'fallback_cb'    => false,
                    ));
                    ?>
                    
                    <!-- Navigation rapide -->
                    <div class="quick-nav" style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if (!is_front_page()) : ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-link" style="color: white; text-decoration: none; padding: 0.5rem;">
                                🏠 Accueil
                            </a>
                        <?php endif; ?>
                        
                        <?php if (is_user_logged_in() && current_user_can('manage_options')) : ?>
                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz'); ?>" class="nav-link" style="color: white; text-decoration: none; padding: 0.5rem;">
                                ⚙️ Admin
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Toggle thème sombre/clair -->
                    <button id="theme-toggle" class="theme-toggle" aria-label="Basculer le thème">
                        <span class="theme-toggle-light">☀️</span>
                        <span class="theme-toggle-dark">🌙</span>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <?php if (!is_front_page()) : ?>
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav" style="background: var(--light-surface); padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
            <div class="container">
                <div class="breadcrumb" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                    <a href="<?php echo esc_url(home_url('/')); ?>" style="color: var(--primary-color); text-decoration: none;">
                        Accueil
                    </a>
                    <?php
                    if (is_singular('biaquiz')) {
                        $quiz_categories = get_the_terms(get_the_ID(), 'quiz_category');
                        if ($quiz_categories && !is_wp_error($quiz_categories)) {
                            $category = $quiz_categories[0];
                            echo '<span>›</span>';
                            echo '<a href="' . get_term_link($category) . '" style="color: var(--primary-color); text-decoration: none;">';
                            echo esc_html($category->name);
                            echo '</a>';
                        }
                        echo '<span>›</span>';
                        echo '<span>' . get_the_title() . '</span>';
                    } elseif (is_tax('quiz_category')) {
                        echo '<span>›</span>';
                        echo '<span>' . single_term_title('', false) . '</span>';
                    }
                    ?>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <div id="content" class="site-content">

