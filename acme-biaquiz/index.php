<?php
/**
 * Template principal - Page d'accueil
 *
 * @package ACME_BIAQuiz
 */

get_header(); ?>

<main class="main-content">
    <!-- Section Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-content animate-fade-in-up">
                <h1 class="hero-title">
                    🚁 ACME BIAQuiz
                </h1>
                <p class="hero-subtitle">
                    Préparez votre Brevet d'Initiation à l'Aéronautique avec des quiz interactifs et une correction immédiate
                </p>
                <div class="hero-actions">
                    <a href="#categories" class="btn btn-primary btn-lg">
                        🚀 Commencer l'entraînement
                    </a>
                    <a href="#about" class="btn btn-secondary btn-lg">
                        📖 En savoir plus
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Catégories -->
    <section id="categories" class="section">
        <div class="container">
            <div class="section-header animate-fade-in-up">
                <h2 class="section-title">Domaines d'entraînement</h2>
                <p class="section-subtitle">
                    Explorez les 6 domaines du BIA avec des quiz adaptés à votre niveau
                </p>
            </div>

            <div class="categories-grid">
                <?php
                // Récupérer les catégories de quiz
                $categories = get_terms(array(
                    'taxonomy' => 'quiz_category',
                    'hide_empty' => false,
                    'orderby' => 'term_order',
                    'order' => 'ASC'
                ));

                // Catégories par défaut si aucune n'existe
                $default_categories = array(
                    array(
                        'name' => 'Aérodynamique et mécanique du vol',
                        'slug' => 'aerodynamique',
                        'description' => 'Principes de vol, portance, traînée, et mécanique du vol des aéronefs.',
                        'icon' => '✈️',
                        'color' => '#3b82f6',
                        'count' => 0
                    ),
                    array(
                        'name' => 'Connaissance des aéronefs',
                        'slug' => 'aeronefs',
                        'description' => 'Structure, systèmes et équipements des différents types d\'aéronefs.',
                        'icon' => '🛩️',
                        'color' => '#10b981',
                        'count' => 0
                    ),
                    array(
                        'name' => 'Météorologie',
                        'slug' => 'meteorologie',
                        'description' => 'Phénomènes météorologiques, prévisions et leur impact sur l\'aviation.',
                        'icon' => '🌤️',
                        'color' => '#f59e0b',
                        'count' => 0
                    ),
                    array(
                        'name' => 'Navigation, règlementation et sécurité',
                        'slug' => 'navigation',
                        'description' => 'Techniques de navigation, règles de l\'air et procédures de sécurité.',
                        'icon' => '🧭',
                        'color' => '#ef4444',
                        'count' => 0
                    ),
                    array(
                        'name' => 'Histoire de l\'aéronautique et de l\'espace',
                        'slug' => 'histoire',
                        'description' => 'Grandes étapes de l\'aviation et de la conquête spatiale.',
                        'icon' => '🚀',
                        'color' => '#8b5cf6',
                        'count' => 0
                    ),
                    array(
                        'name' => 'Anglais aéronautique',
                        'slug' => 'anglais',
                        'description' => 'Vocabulaire et phraséologie anglaise utilisés en aéronautique.',
                        'icon' => '🗣️',
                        'color' => '#06b6d4',
                        'count' => 0
                    )
                );

                $categories_to_display = !empty($categories) ? $categories : $default_categories;

                foreach ($categories_to_display as $index => $category) :
                    if (is_object($category)) {
                        // Catégorie WordPress existante
                        $category_meta = function_exists('BIAQuiz_Taxonomies::get_category_meta') 
                            ? BIAQuiz_Taxonomies::get_category_meta($category->term_id) 
                            : array('icon' => '📚', 'color' => '#6b7280');
                        
                        $category_data = array(
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'description' => $category->description ?: 'Description de la catégorie',
                            'icon' => $category_meta['icon'] ?: '📚',
                            'color' => $category_meta['color'] ?: '#6b7280',
                            'count' => $category->count,
                            'link' => get_term_link($category)
                        );
                    } else {
                        // Catégorie par défaut
                        $category_data = $category;
                        $category_data['link'] = '#';
                    }
                    
                    $animation_delay = $index * 0.1;
                ?>
                    <a href="<?php echo esc_url($category_data['link']); ?>" 
                       class="category-card animate-fade-in-up" 
                       style="animation-delay: <?php echo $animation_delay; ?>s;">
                        
                        <div class="category-icon" style="color: <?php echo esc_attr($category_data['color']); ?>;">
                            <?php echo $category_data['icon']; ?>
                        </div>
                        
                        <h3 class="category-title" style="color: <?php echo esc_attr($category_data['color']); ?>;">
                            <?php echo esc_html($category_data['name']); ?>
                        </h3>
                        
                        <p class="category-description">
                            <?php echo esc_html($category_data['description']); ?>
                        </p>
                        
                        <div class="category-stats">
                            <span class="category-quiz-count">
                                <?php echo $category_data['count']; ?> quiz disponible<?php echo $category_data['count'] > 1 ? 's' : ''; ?>
                            </span>
                            <span class="category-arrow">→</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section À propos -->
    <section id="about" class="section" style="background: var(--light-surface);">
        <div class="container">
            <div class="grid grid-2" style="align-items: center; gap: 4rem;">
                <div class="animate-fade-in-left">
                    <h2 class="section-title" style="text-align: left; margin-bottom: 2rem;">
                        Pourquoi choisir ACME BIAQuiz ?
                    </h2>
                    
                    <div class="features-list">
                        <div class="feature-item" style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.5rem; color: var(--success-color);">✅</div>
                            <div>
                                <h4 style="margin-bottom: 0.5rem;">Quiz interactifs</h4>
                                <p style="margin-bottom: 0;">Correction immédiate avec répétition des erreurs jusqu'à la maîtrise complète.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item" style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.5rem; color: var(--info-color);">📱</div>
                            <div>
                                <h4 style="margin-bottom: 0.5rem;">Responsive design</h4>
                                <p style="margin-bottom: 0;">Accessible sur tous vos appareils : ordinateur, tablette et smartphone.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item" style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.5rem; color: var(--warning-color);">🎯</div>
                            <div>
                                <h4 style="margin-bottom: 0.5rem;">Aucune inscription</h4>
                                <p style="margin-bottom: 0;">Commencez immédiatement votre entraînement sans créer de compte.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item" style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="font-size: 1.5rem; color: var(--primary-color);">📊</div>
                            <div>
                                <h4 style="margin-bottom: 0.5rem;">Suivi des progrès</h4>
                                <p style="margin-bottom: 0;">Visualisez vos scores et votre progression dans chaque domaine.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="animate-fade-in-right">
                    <div class="stats-showcase" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 3rem; border-radius: var(--border-radius-xl); text-align: center;">
                        <h3 style="color: white; margin-bottom: 2rem; font-size: 1.5rem;">Statistiques de l'application</h3>
                        
                        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                            <div class="stat-item">
                                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?php
                                    // Compter les quiz actifs
                                    $quiz_count = wp_count_posts('biaquiz');
                                    echo $quiz_count ? $quiz_count->publish : '120+';
                                    ?>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Quiz disponibles</div>
                            </div>
                            
                            <div class="stat-item">
                                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">6</div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Domaines couverts</div>
                            </div>
                            
                            <div class="stat-item">
                                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">20</div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Questions par quiz</div>
                            </div>
                            
                            <div class="stat-item">
                                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">100%</div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Gratuit</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section CTA -->
    <section class="section">
        <div class="container">
            <div class="cta-section" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 4rem 2rem; border-radius: var(--border-radius-xl); text-align: center;">
                <h2 style="color: white; font-size: 2.5rem; margin-bottom: 1rem;">
                    Prêt à commencer votre préparation ?
                </h2>
                <p style="font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9;">
                    Rejoignez les milliers d'étudiants qui se préparent au BIA avec ACME BIAQuiz
                </p>
                <a href="#categories" class="btn btn-lg" style="background: white; color: var(--primary-color); font-weight: 600;">
                    🎯 Commencer maintenant
                </a>
            </div>
        </div>
    </section>
</main>

<style>
/* Styles spécifiques à la page d'accueil */
.category-arrow {
    font-size: 1.25rem;
    color: var(--primary-color);
    transition: transform var(--transition-fast);
}

.category-card:hover .category-arrow {
    transform: translateX(5px);
}

.feature-item h4 {
    color: var(--text-dark);
    font-weight: 600;
}

.stats-showcase {
    box-shadow: var(--shadow-lg);
}

.cta-section {
    box-shadow: var(--shadow-lg);
}

/* Animations au scroll */
@media (prefers-reduced-motion: no-preference) {
    .animate-fade-in-up,
    .animate-fade-in-left,
    .animate-fade-in-right {
        opacity: 0;
        animation-fill-mode: forwards;
    }
}

/* Responsive pour la page d'accueil */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.125rem;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .grid-2 {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .cta-section {
        padding: 2rem 1rem;
    }
    
    .cta-section h2 {
        font-size: 2rem;
    }
}
</style>

<script>
// Animation au scroll
document.addEventListener('DOMContentLoaded', function() {
    // Observer pour les animations au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0) translateX(0)';
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec animation
    document.querySelectorAll('.animate-fade-in-up, .animate-fade-in-left, .animate-fade-in-right').forEach(el => {
        observer.observe(el);
    });

    // Smooth scroll pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php get_footer(); ?>

