    </div><!-- #content -->

    <footer id="colophon" class="site-footer" style="background: var(--dark-surface); color: var(--text-light); margin-top: 4rem;">
        <div class="container">
            <!-- Footer widgets -->
            <?php if (is_active_sidebar('footer-1')) : ?>
                <div class="footer-widgets" style="padding: 3rem 0 2rem;">
                    <div class="footer-widget-area">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer content -->
            <div class="footer-content" style="padding: 2rem 0;">
                <div class="footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <!-- À propos -->
                    <div class="footer-section">
                        <h3 style="color: var(--accent-color); margin-bottom: 1rem; font-size: 1.125rem;">
                            À propos d'ACME BIAQuiz
                        </h3>
                        <p style="color: #9ca3af; line-height: 1.6; font-size: 0.875rem;">
                            Plateforme d'entraînement dédiée au Brevet d'Initiation à l'Aéronautique. 
                            Préparez-vous efficacement avec nos quiz interactifs et notre système de répétition intelligent.
                        </p>
                    </div>

                    <!-- Catégories -->
                    <div class="footer-section">
                        <h3 style="color: var(--accent-color); margin-bottom: 1rem; font-size: 1.125rem;">
                            Domaines d'entraînement
                        </h3>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php
                            $categories = get_quiz_categories();
                            $count = 0;
                            foreach ($categories as $slug => $category) :
                                if ($count >= 4) break; // Limiter à 4 catégories dans le footer
                                $count++;
                            ?>
                                <li style="margin-bottom: 0.5rem;">
                                    <a href="<?php echo home_url('/category/' . $slug); ?>" 
                                       style="color: #9ca3af; text-decoration: none; font-size: 0.875rem; transition: color 0.3s ease;">
                                        <?php echo $category['icon'] . ' ' . esc_html($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (count($categories) > 4) : ?>
                                <li style="margin-top: 0.5rem;">
                                    <a href="<?php echo home_url('/'); ?>" 
                                       style="color: var(--accent-color); text-decoration: none; font-size: 0.875rem;">
                                        Voir toutes les catégories →
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Fonctionnalités -->
                    <div class="footer-section">
                        <h3 style="color: var(--accent-color); margin-bottom: 1rem; font-size: 1.125rem;">
                            Fonctionnalités
                        </h3>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af; font-size: 0.875rem;">✅ Correction immédiate</span>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af; font-size: 0.875rem;">🔄 Répétition des erreurs</span>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af; font-size: 0.875rem;">📱 Interface responsive</span>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af; font-size: 0.875rem;">⚡ Aucune inscription</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact/Info -->
                    <div class="footer-section">
                        <h3 style="color: var(--accent-color); margin-bottom: 1rem; font-size: 1.125rem;">
                            Informations
                        </h3>
                        <div style="color: #9ca3af; font-size: 0.875rem; line-height: 1.6;">
                            <p style="margin-bottom: 0.5rem;">
                                <strong>Version:</strong> 1.0.0
                            </p>
                            <p style="margin-bottom: 0.5rem;">
                                <strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y'); ?>
                            </p>
                            <?php if (is_user_logged_in() && current_user_can('manage_options')) : ?>
                                <p style="margin-top: 1rem;">
                                    <a href="<?php echo admin_url('edit.php?post_type=biaquiz'); ?>" 
                                       style="color: var(--accent-color); text-decoration: none;">
                                        🛠️ Administration
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer bottom -->
            <div class="footer-bottom" style="border-top: 1px solid #374151; padding: 1.5rem 0; text-align: center;">
                <div class="footer-bottom-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div class="copyright" style="color: #9ca3af; font-size: 0.875rem;">
                        © <?php echo date('Y'); ?> ACME BIAQuiz. Tous droits réservés.
                    </div>
                    
                    <div class="footer-links" style="display: flex; gap: 1.5rem;">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'footer',
                            'menu_id'        => 'footer-menu',
                            'container'      => false,
                            'menu_class'     => 'footer-menu',
                            'fallback_cb'    => false,
                            'depth'          => 1,
                        ));
                        ?>
                    </div>
                    
                    <div class="footer-meta" style="color: #9ca3af; font-size: 0.875rem;">
                        Propulsé par <span style="color: var(--accent-color);">WordPress</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<!-- Scripts additionnels -->
<script>
// Initialisation du thème au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier le thème sauvegardé
    const savedTheme = localStorage.getItem('acme-biaquiz-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
    
    // Animation d'entrée pour les éléments
    const animateElements = document.querySelectorAll('.category-card, .feature-card, .quiz-container');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });
    
    animateElements.forEach(el => observer.observe(el));
});

// Gestion des liens hover
document.addEventListener('mouseover', function(e) {
    if (e.target.matches('.category-card, .feature-card')) {
        e.target.style.transform = 'translateY(-2px)';
    }
});

document.addEventListener('mouseout', function(e) {
    if (e.target.matches('.category-card, .feature-card')) {
        e.target.style.transform = 'translateY(0)';
    }
});
</script>

<?php wp_footer(); ?>

</body>
</html>

