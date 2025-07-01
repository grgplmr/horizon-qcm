/**
 * ACME BIAQuiz - Gestion du thème sombre/clair
 * 
 * @package ACME_BIAQuiz
 */

(function($) {
    'use strict';

    /**
     * Classe pour gérer le toggle du thème
     */
    class ThemeToggle {
        constructor() {
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.loadSavedTheme();
            this.bindEvents();
            this.updateToggleButton();
        }

        /**
         * Charger le thème sauvegardé
         */
        loadSavedTheme() {
            const savedTheme = localStorage.getItem('acme-biaquiz-theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        }

        /**
         * Lier les événements
         */
        bindEvents() {
            $(document).on('click', '#theme-toggle', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });

            // Écouter les changements de préférence système
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                mediaQuery.addListener((e) => {
                    // Seulement si l'utilisateur n'a pas de préférence sauvegardée
                    if (!localStorage.getItem('acme-biaquiz-theme')) {
                        this.setTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        }

        /**
         * Basculer le thème
         */
        toggleTheme() {
            const isDark = document.body.classList.contains('dark-mode');
            const newTheme = isDark ? 'light' : 'dark';
            
            this.setTheme(newTheme);
            this.saveTheme(newTheme);
            this.updateToggleButton();
            
            // Animation de transition
            this.animateThemeChange();
        }

        /**
         * Définir le thème
         */
        setTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }

        /**
         * Sauvegarder le thème
         */
        saveTheme(theme) {
            localStorage.setItem('acme-biaquiz-theme', theme);
        }

        /**
         * Mettre à jour le bouton de toggle
         */
        updateToggleButton() {
            const isDark = document.body.classList.contains('dark-mode');
            const button = $('#theme-toggle');
            
            if (isDark) {
                button.find('.theme-toggle-light').hide();
                button.find('.theme-toggle-dark').show();
                button.attr('aria-label', 'Passer au thème clair');
            } else {
                button.find('.theme-toggle-light').show();
                button.find('.theme-toggle-dark').hide();
                button.attr('aria-label', 'Passer au thème sombre');
            }
        }

        /**
         * Animation de changement de thème
         */
        animateThemeChange() {
            // Créer un overlay pour une transition douce
            const overlay = $('<div class="theme-transition-overlay"></div>');
            overlay.css({
                position: 'fixed',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                backgroundColor: document.body.classList.contains('dark-mode') ? '#0f172a' : '#f8fafc',
                opacity: 0,
                zIndex: 9999,
                pointerEvents: 'none',
                transition: 'opacity 0.3s ease'
            });
            
            $('body').append(overlay);
            
            // Animation
            setTimeout(() => {
                overlay.css('opacity', 0.3);
            }, 10);
            
            setTimeout(() => {
                overlay.css('opacity', 0);
                setTimeout(() => overlay.remove(), 300);
            }, 150);
        }

        /**
         * Obtenir le thème actuel
         */
        getCurrentTheme() {
            return document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        }

        /**
         * Détecter la préférence système
         */
        getSystemPreference() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }
    }

    // Initialiser le toggle de thème au chargement du DOM
    $(document).ready(function() {
        window.ThemeToggle = new ThemeToggle();
    });

})(jQuery);

