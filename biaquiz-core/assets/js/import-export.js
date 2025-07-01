/**
 * Script pour l'interface d'import/export de BIAQuiz
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Gestion du formulaire d'import
        $('#import-form').on('submit', function(e) {
            e.preventDefault();
            handleImport();
        });

        // Gestion du formulaire d'export
        $('#export-form').on('submit', function(e) {
            e.preventDefault();
            handleExport();
        });

        // Validation du fichier d'import
        $('#import-file').on('change', function() {
            validateImportFile(this);
        });
    });

    /**
     * Gérer l'import de quiz
     */
    function handleImport() {
        const form = document.getElementById('import-form');
        const formData = new FormData(form);
        
        // Ajouter les données AJAX
        formData.append('action', 'biaquiz_import');
        formData.append('nonce', biaquiz_import_export.nonce);

        // Afficher la barre de progression
        showImportProgress();

        $.ajax({
            url: biaquiz_import_export.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        updateProgress(percentComplete, biaquiz_import_export.strings.importing);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                hideImportProgress();
                
                if (response.success) {
                    showImportResults(response.data);
                } else {
                    showError(response.data || biaquiz_import_export.strings.error);
                }
            },
            error: function() {
                hideImportProgress();
                showError(biaquiz_import_export.strings.error);
            }
        });
    }

    /**
     * Gérer l'export de quiz
     */
    function handleExport() {
        const formData = new FormData(document.getElementById('export-form'));
        
        // Ajouter les données AJAX
        formData.append('action', 'biaquiz_export');
        formData.append('nonce', biaquiz_import_export.nonce);

        // Afficher le message de chargement
        showExportProgress();

        $.ajax({
            url: biaquiz_import_export.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideExportProgress();
                
                if (response.success) {
                    downloadFile(response.data);
                } else {
                    showError(response.data || biaquiz_import_export.strings.error);
                }
            },
            error: function() {
                hideExportProgress();
                showError(biaquiz_import_export.strings.error);
            }
        });
    }

    /**
     * Valider le fichier d'import
     */
    function validateImportFile(input) {
        const file = input.files[0];
        const maxSize = 10 * 1024 * 1024; // 10 MB
        const allowedTypes = ['text/csv', 'application/json'];
        
        if (!file) return;

        // Vérifier la taille
        if (file.size > maxSize) {
            showError('Le fichier est trop volumineux. Taille maximale : 10 MB');
            input.value = '';
            return;
        }

        // Vérifier le type
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'json'].includes(fileExtension)) {
            showError('Format de fichier non supporté. Utilisez CSV ou JSON.');
            input.value = '';
            return;
        }

        // Afficher les informations du fichier
        showFileInfo(file);
    }

    /**
     * Afficher les informations du fichier
     */
    function showFileInfo(file) {
        const fileSize = (file.size / 1024).toFixed(2);
        const fileType = file.name.split('.').pop().toUpperCase();
        
        const infoHtml = `
            <div class="file-info">
                <strong>Fichier sélectionné :</strong> ${file.name}<br>
                <strong>Taille :</strong> ${fileSize} KB<br>
                <strong>Type :</strong> ${fileType}
            </div>
        `;
        
        // Supprimer l'ancienne info si elle existe
        $('.file-info').remove();
        
        // Ajouter la nouvelle info après le champ de fichier
        $('#import-file').after(infoHtml);
    }

    /**
     * Afficher la barre de progression d'import
     */
    function showImportProgress() {
        $('#import-progress').show();
        $('#import-form button[type="submit"]').prop('disabled', true);
        updateProgress(0, biaquiz_import_export.strings.importing);
    }

    /**
     * Masquer la barre de progression d'import
     */
    function hideImportProgress() {
        $('#import-progress').hide();
        $('#import-form button[type="submit"]').prop('disabled', false);
    }

    /**
     * Afficher le message d'export
     */
    function showExportProgress() {
        const button = $('#export-form button[type="submit"]');
        button.prop('disabled', true);
        button.text(biaquiz_import_export.strings.exporting);
    }

    /**
     * Masquer le message d'export
     */
    function hideExportProgress() {
        const button = $('#export-form button[type="submit"]');
        button.prop('disabled', false);
        button.text('Exporter');
    }

    /**
     * Mettre à jour la barre de progression
     */
    function updateProgress(percent, text) {
        $('#import-progress .progress-fill').css('width', percent + '%');
        $('#import-progress .progress-text').text(text + ' (' + Math.round(percent) + '%)');
    }

    /**
     * Afficher les résultats d'import
     */
    function showImportResults(data) {
        let html = '<div class="import-success">';
        html += '<h3>Import terminé avec succès !</h3>';
        
        if (data.imported > 0) {
            html += `<p><strong>${data.imported}</strong> quiz importé(s)</p>`;
        }
        
        if (data.updated > 0) {
            html += `<p><strong>${data.updated}</strong> quiz mis à jour</p>`;
        }
        
        if (data.errors && data.errors.length > 0) {
            html += '<h4>Erreurs rencontrées :</h4>';
            html += '<ul>';
            data.errors.forEach(function(error) {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#import-results').html(html).show();
        
        // Faire défiler vers les résultats
        $('html, body').animate({
            scrollTop: $('#import-results').offset().top
        }, 500);
    }

    /**
     * Télécharger un fichier
     */
    function downloadFile(data) {
        const blob = new Blob([data.content], { type: data.mime_type });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = data.filename;
        document.body.appendChild(a);
        a.click();
        
        // Nettoyer
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showSuccess('Export terminé ! Le fichier a été téléchargé.');
    }

    /**
     * Afficher un message d'erreur
     */
    function showError(message) {
        const errorHtml = `
            <div class="notice notice-error">
                <p><strong>Erreur :</strong> ${message}</p>
            </div>
        `;
        
        // Supprimer les anciens messages
        $('.notice').remove();
        
        // Ajouter le nouveau message en haut de la page
        $('.wrap h1').after(errorHtml);
        
        // Faire défiler vers le haut
        $('html, body').animate({ scrollTop: 0 }, 500);
    }

    /**
     * Afficher un message de succès
     */
    function showSuccess(message) {
        const successHtml = `
            <div class="notice notice-success">
                <p><strong>Succès :</strong> ${message}</p>
            </div>
        `;
        
        // Supprimer les anciens messages
        $('.notice').remove();
        
        // Ajouter le nouveau message en haut de la page
        $('.wrap h1').after(successHtml);
        
        // Faire défiler vers le haut
        $('html, body').animate({ scrollTop: 0 }, 500);
    }

})(jQuery);

