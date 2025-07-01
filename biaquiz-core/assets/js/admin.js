/**
 * Script pour l'interface d'administration de BIAQuiz
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialiser les fonctionnalités
        initQuickActions();
        initTooltips();
        initConfirmDialogs();
        initAutoRefresh();
        initCharts();
    });

    /**
     * Initialiser les actions rapides
     */
    function initQuickActions() {
        $(document).on('click', '.biaquiz-quick-action', function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const action = $this.data('action');
            const quizId = $this.data('quiz-id');
            const confirmMessage = getConfirmMessage(action);
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            performQuickAction(action, quizId, $this);
        });
    }

    /**
     * Obtenir le message de confirmation
     */
    function getConfirmMessage(action) {
        switch (action) {
            case 'activate':
                return biaquiz_admin.strings.confirm_activate;
            case 'deactivate':
                return biaquiz_admin.strings.confirm_deactivate;
            case 'delete':
                return biaquiz_admin.strings.confirm_delete;
            default:
                return null;
        }
    }

    /**
     * Exécuter une action rapide
     */
    function performQuickAction(action, quizId, $element) {
        const originalText = $element.text();
        $element.text(biaquiz_admin.strings.loading);
        $element.addClass('loading');

        $.ajax({
            url: biaquiz_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'biaquiz_quick_action',
                action_type: action,
                quiz_id: quizId,
                nonce: biaquiz_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data, 'success');
                    updateUIAfterAction(action, $element);
                    
                    // Recharger la page après un délai
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || biaquiz_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(biaquiz_admin.strings.error, 'error');
            },
            complete: function() {
                $element.text(originalText);
                $element.removeClass('loading');
            }
        });
    }

    /**
     * Mettre à jour l'interface après une action
     */
    function updateUIAfterAction(action, $element) {
        const $row = $element.closest('tr');
        
        switch (action) {
            case 'activate':
                $row.find('.column-quiz_status').html('<span class="status-active">✅ Actif</span>');
                $element.text('Désactiver').data('action', 'deactivate');
                break;
                
            case 'deactivate':
                $row.find('.column-quiz_status').html('<span class="status-inactive">⏸️ Inactif</span>');
                $element.text('Activer').data('action', 'activate');
                break;
                
            case 'delete':
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
                break;
        }
    }

    /**
     * Afficher une notification
     */
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible biaquiz-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        // Supprimer les anciennes notifications
        $('.biaquiz-notice').remove();

        // Ajouter la nouvelle notification
        $('.wrap h1').after(notice);

        // Gérer la fermeture
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Auto-fermeture pour les succès
        if (type === 'success') {
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }

        // Faire défiler vers le haut
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    /**
     * Initialiser les tooltips
     */
    function initTooltips() {
        // Ajouter des tooltips pour les badges de score
        $('.score-badge').each(function() {
            const $this = $(this);
            const score = parseFloat($this.text());
            let tooltip = '';
            
            if (score >= 15) {
                tooltip = 'Excellent score';
            } else if (score >= 10) {
                tooltip = 'Score correct';
            } else {
                tooltip = 'Score à améliorer';
            }
            
            $this.attr('title', tooltip);
        });

        // Ajouter des tooltips pour les statuts
        $('.status-active').attr('title', 'Ce quiz est actif et visible par les utilisateurs');
        $('.status-inactive').attr('title', 'Ce quiz est inactif et non visible par les utilisateurs');
        $('.status-error').attr('title', 'Ce quiz contient des erreurs et doit être corrigé');
    }

    /**
     * Initialiser les dialogues de confirmation
     */
    function initConfirmDialogs() {
        // Confirmation pour la suppression
        $('.submitdelete').on('click', function(e) {
            if (!confirm(biaquiz_admin.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });

        // Confirmation pour les actions en lot
        $('#doaction, #doaction2').on('click', function(e) {
            const action = $(this).siblings('select').val();
            
            if (action === 'trash' || action === 'delete') {
                const selectedItems = $('input[name="post[]"]:checked').length;
                
                if (selectedItems === 0) {
                    alert('Veuillez sélectionner au moins un élément.');
                    e.preventDefault();
                    return false;
                }
                
                const confirmMessage = `Êtes-vous sûr de vouloir supprimer ${selectedItems} élément(s) ?`;
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    /**
     * Initialiser le rafraîchissement automatique
     */
    function initAutoRefresh() {
        // Rafraîchir les statistiques toutes les 5 minutes sur le tableau de bord
        if ($('.biaquiz-dashboard-stats').length > 0) {
            setInterval(function() {
                refreshDashboardStats();
            }, 300000); // 5 minutes
        }
    }

    /**
     * Rafraîchir les statistiques du tableau de bord
     */
    function refreshDashboardStats() {
        $.ajax({
            url: biaquiz_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'biaquiz_refresh_stats',
                nonce: biaquiz_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                }
            }
        });
    }

    /**
     * Mettre à jour les statistiques du tableau de bord
     */
    function updateDashboardStats(stats) {
        $('.stat-card').each(function() {
            const $card = $(this);
            const $number = $card.find('.stat-number');
            const label = $card.find('.stat-label').text().toLowerCase();
            
            let newValue = '';
            
            if (label.includes('quiz')) {
                newValue = stats.total_quizzes;
            } else if (label.includes('tentatives')) {
                newValue = stats.total_attempts;
            } else if (label.includes('score')) {
                newValue = stats.avg_score + '/20';
            } else if (label.includes('réussite')) {
                newValue = stats.success_rate + '%';
            }
            
            if (newValue && $number.text() !== newValue) {
                $number.fadeOut(200, function() {
                    $(this).text(newValue).fadeIn(200);
                });
            }
        });
    }

    /**
     * Initialiser les graphiques
     */
    function initCharts() {
        // Vérifier si Chart.js est disponible
        if (typeof Chart === 'undefined') {
            return;
        }

        // Configuration par défaut pour tous les graphiques
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = '#666';
        Chart.defaults.borderColor = '#ddd';

        // Initialiser les graphiques spécifiques
        initAttemptsChart();
        initCategoryChart();
    }

    /**
     * Initialiser le graphique des tentatives
     */
    function initAttemptsChart() {
        const canvas = document.getElementById('attempts-chart');
        if (!canvas) return;

        // Les données sont déjà injectées par PHP dans le template
        // Le graphique est initialisé dans le template HTML
    }

    /**
     * Initialiser le graphique des catégories
     */
    function initCategoryChart() {
        const canvas = document.getElementById('category-chart');
        if (!canvas) return;

        // Les données sont déjà injectées par PHP dans le template
        // Le graphique est initialisé dans le template HTML
    }

    /**
     * Utilitaires pour les formulaires
     */
    function initFormUtilities() {
        // Auto-sauvegarde des brouillons
        let autoSaveTimer;
        
        $('.biaquiz-form input, .biaquiz-form textarea, .biaquiz-form select').on('change input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                autoSaveDraft();
            }, 5000); // 5 secondes
        });

        // Validation des formulaires
        $('.biaquiz-form').on('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Auto-sauvegarde des brouillons
     */
    function autoSaveDraft() {
        const $form = $('.biaquiz-form');
        if ($form.length === 0) return;

        const formData = new FormData($form[0]);
        formData.append('action', 'biaquiz_auto_save');
        formData.append('nonce', biaquiz_admin.nonce);

        $.ajax({
            url: biaquiz_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAutoSaveIndicator();
                }
            }
        });
    }

    /**
     * Afficher l'indicateur d'auto-sauvegarde
     */
    function showAutoSaveIndicator() {
        const indicator = $('<div class="auto-save-indicator">Brouillon sauvegardé</div>');
        $('body').append(indicator);
        
        indicator.fadeIn(200).delay(2000).fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Valider un formulaire
     */
    function validateForm(form) {
        const $form = $(form);
        let isValid = true;
        
        // Supprimer les anciennes erreurs
        $form.find('.field-error').removeClass('field-error');
        $form.find('.error-message').remove();

        // Valider les champs requis
        $form.find('[required]').each(function() {
            const $field = $(this);
            
            if (!$field.val() || $field.val().trim() === '') {
                showFieldError($field, 'Ce champ est requis');
                isValid = false;
            }
        });

        // Valider les emails
        $form.find('input[type="email"]').each(function() {
            const $field = $(this);
            const email = $field.val();
            
            if (email && !isValidEmail(email)) {
                showFieldError($field, 'Adresse email invalide');
                isValid = false;
            }
        });

        // Valider les URLs
        $form.find('input[type="url"]').each(function() {
            const $field = $(this);
            const url = $field.val();
            
            if (url && !isValidUrl(url)) {
                showFieldError($field, 'URL invalide');
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Afficher une erreur de champ
     */
    function showFieldError($field, message) {
        $field.addClass('field-error');
        
        const errorElement = $(`<div class="error-message">${message}</div>`);
        $field.after(errorElement);
    }

    /**
     * Valider une adresse email
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Valider une URL
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Initialiser les fonctionnalités avancées
     */
    function initAdvancedFeatures() {
        // Recherche en temps réel
        initLiveSearch();
        
        // Tri des colonnes
        initColumnSorting();
        
        // Filtres avancés
        initAdvancedFilters();
    }

    /**
     * Initialiser la recherche en temps réel
     */
    function initLiveSearch() {
        const $searchInput = $('#post-search-input');
        if ($searchInput.length === 0) return;

        let searchTimer;
        
        $searchInput.on('input', function() {
            clearTimeout(searchTimer);
            const query = $(this).val();
            
            searchTimer = setTimeout(function() {
                performLiveSearch(query);
            }, 500);
        });
    }

    /**
     * Effectuer une recherche en temps réel
     */
    function performLiveSearch(query) {
        if (query.length < 3) return;

        $.ajax({
            url: biaquiz_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'biaquiz_live_search',
                query: query,
                nonce: biaquiz_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateSearchResults(response.data);
                }
            }
        });
    }

    /**
     * Mettre à jour les résultats de recherche
     */
    function updateSearchResults(results) {
        const $tbody = $('.wp-list-table tbody');
        
        if (results.length === 0) {
            $tbody.html('<tr><td colspan="100%">Aucun résultat trouvé</td></tr>');
            return;
        }

        let html = '';
        results.forEach(function(item) {
            html += buildTableRow(item);
        });
        
        $tbody.html(html);
    }

    /**
     * Construire une ligne de tableau
     */
    function buildTableRow(item) {
        // Cette fonction devrait être adaptée selon la structure des données
        return `
            <tr>
                <td><strong>${item.title}</strong></td>
                <td>${item.category}</td>
                <td>${item.attempts}</td>
                <td>${item.score}</td>
            </tr>
        `;
    }

    // Initialiser les fonctionnalités avancées
    initFormUtilities();
    initAdvancedFeatures();

})(jQuery);

/**
 * Styles CSS pour les indicateurs JavaScript
 */
const additionalStyles = `
<style>
.field-error {
    border-color: #dc3232 !important;
    box-shadow: 0 0 2px rgba(220, 50, 50, 0.8);
}

.error-message {
    color: #dc3232;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.auto-save-indicator {
    position: fixed;
    top: 32px;
    right: 20px;
    background: #46b450;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.875rem;
    z-index: 9999;
    display: none;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

@media (max-width: 782px) {
    .auto-save-indicator {
        top: 46px;
    }
}
</style>
`;

// Injecter les styles
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalStyles);
}

