/**
 * ACME BIAQuiz - Script principal pour les quiz interactifs
 * 
 * @package ACME_BIAQuiz
 */

(function($) {
    'use strict';

    /**
     * Classe principale pour gérer les quiz
     */
    class BIAQuiz {
        constructor() {
            this.currentQuiz = null;
            this.currentQuestionIndex = 0;
            this.userAnswers = [];
            this.incorrectQuestions = [];
            this.isRetryMode = false;
            this.startTime = null;
            this.questionStartTime = null;
            this.questionTimes = [];
            
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.loadQuizIfPresent();
        }

        /**
         * Lier les événements
         */
        bindEvents() {
            // Bouton de démarrage de quiz
            $(document).on('click', '.start-quiz-btn', (e) => {
                e.preventDefault();
                const quizId = $(e.target).data('quiz-id');
                this.startQuiz(quizId);
            });

            // Sélection de réponse
            $(document).on('click', '.answer-option', (e) => {
                this.selectAnswer($(e.currentTarget));
            });

            // Validation de la réponse
            $(document).on('click', '.validate-answer-btn', (e) => {
                e.preventDefault();
                this.validateAnswer();
            });

            // Question suivante
            $(document).on('click', '.next-question-btn', (e) => {
                e.preventDefault();
                this.nextQuestion();
            });

            // Recommencer le quiz
            $(document).on('click', '.restart-quiz-btn', (e) => {
                e.preventDefault();
                this.restartQuiz();
            });

            // Retour à l'accueil
            $(document).on('click', '.back-home-btn', (e) => {
                e.preventDefault();
                window.location.href = acme_biaquiz_ajax.home_url || '/';
            });
        }

        /**
         * Charger un quiz si présent sur la page
         */
        loadQuizIfPresent() {
            const quizContainer = $('.quiz-container');
            if (quizContainer.length && quizContainer.data('quiz-id')) {
                const quizId = quizContainer.data('quiz-id');
                this.startQuiz(quizId);
            }
        }

        /**
         * Démarrer un quiz
         */
        startQuiz(quizId) {
            this.showLoading();
            
            $.ajax({
                url: acme_biaquiz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_quiz',
                    quiz_id: quizId,
                    nonce: acme_biaquiz_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentQuiz = response.data;
                        this.currentQuiz.id = quizId;
                        this.initializeQuiz();
                    } else {
                        this.showError(response.data || acme_biaquiz_ajax.strings.error);
                    }
                },
                error: () => {
                    this.showError(acme_biaquiz_ajax.strings.error);
                }
            });
        }

        /**
         * Initialiser le quiz
         */
        initializeQuiz() {
            this.currentQuestionIndex = 0;
            this.userAnswers = [];
            this.incorrectQuestions = [];
            this.isRetryMode = false;
            this.startTime = Date.now();
            this.questionTimes = [];

            // Mélanger les questions si configuré
            if (this.currentQuiz.settings && this.currentQuiz.settings.shuffle_questions) {
                this.shuffleArray(this.currentQuiz.questions);
            }

            // Mélanger les réponses pour chaque question si configuré
            if (this.currentQuiz.settings && this.currentQuiz.settings.shuffle_answers) {
                this.currentQuiz.questions.forEach(question => {
                    this.shuffleArray(question.answers);
                });
            }

            this.renderQuizInterface();
            this.showQuestion(0);
        }

        /**
         * Mélanger un tableau (Fisher-Yates)
         */
        shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        /**
         * Rendre l'interface du quiz
         */
        renderQuizInterface() {
            const container = $('.quiz-container');
            const totalQuestions = this.currentQuiz.questions.length;
            
            const html = `
                <div class="quiz-header">
                    <h1 class="quiz-title">${this.currentQuiz.title}</h1>
                    <div class="quiz-progress">
                        <div class="quiz-progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="quiz-info">
                        <span class="question-counter">Question <span class="current-question">1</span> sur ${totalQuestions}</span>
                        ${this.isRetryMode ? '<span class="retry-mode">Mode révision</span>' : ''}
                    </div>
                </div>
                <div class="quiz-content">
                    <div class="question-container">
                        <!-- Question sera insérée ici -->
                    </div>
                </div>
            `;
            
            container.html(html).addClass('active');
        }

        /**
         * Afficher une question
         */
        showQuestion(index) {
            const question = this.currentQuiz.questions[index];
            const totalQuestions = this.currentQuiz.questions.length;
            const questionNumber = index + 1;
            
            this.questionStartTime = Date.now();
            
            // Mettre à jour la barre de progression
            const progress = ((index) / totalQuestions) * 100;
            $('.quiz-progress-bar').css('width', progress + '%');
            $('.current-question').text(questionNumber);
            
            // Construire le HTML de la question
            let questionHtml = `
                <div class="question-content fade-in">
                    <h2 class="question-text">${question.question_text}</h2>
            `;
            
            // Ajouter l'image si présente
            if (question.question_image && question.question_image.url) {
                questionHtml += `
                    <div class="question-image">
                        <img src="${question.question_image.url}" alt="${question.question_image.alt || 'Image de la question'}" />
                    </div>
                `;
            }
            
            // Ajouter les réponses
            questionHtml += '<div class="answers-grid">';
            question.answers.forEach((answer, answerIndex) => {
                questionHtml += `
                    <div class="answer-option" data-answer-index="${answerIndex}">
                        <div class="answer-icon">${String.fromCharCode(65 + answerIndex)}</div>
                        <span class="answer-text">${answer.answer_text}</span>
                    </div>
                `;
            });
            questionHtml += '</div>';
            
            // Bouton de validation
            questionHtml += `
                <div class="question-actions">
                    <button class="btn btn-primary validate-answer-btn" disabled>
                        Valider la réponse
                    </button>
                </div>
            `;
            
            questionHtml += '</div>';
            
            $('.question-container').html(questionHtml);
        }

        /**
         * Sélectionner une réponse
         */
        selectAnswer($option) {
            // Désélectionner toutes les autres réponses
            $('.answer-option').removeClass('selected');
            
            // Sélectionner la réponse cliquée
            $option.addClass('selected');
            
            // Activer le bouton de validation
            $('.validate-answer-btn').prop('disabled', false);
        }

        /**
         * Valider la réponse
         */
        validateAnswer() {
            const selectedOption = $('.answer-option.selected');
            if (!selectedOption.length) return;
            
            const answerIndex = selectedOption.data('answer-index');
            const question = this.currentQuiz.questions[this.currentQuestionIndex];
            const selectedAnswer = question.answers[answerIndex];
            const isCorrect = selectedAnswer.is_correct;
            
            // Enregistrer le temps de réponse
            const responseTime = Date.now() - this.questionStartTime;
            this.questionTimes.push(responseTime);
            
            // Enregistrer la réponse
            this.userAnswers[this.currentQuestionIndex] = {
                questionIndex: this.currentQuestionIndex,
                answerIndex: answerIndex,
                isCorrect: isCorrect,
                responseTime: responseTime
            };
            
            // Afficher le résultat
            this.showAnswerResult(isCorrect, question);
            
            // Si incorrect et pas en mode révision, ajouter à la liste des questions à revoir
            if (!isCorrect && !this.isRetryMode) {
                if (!this.incorrectQuestions.includes(this.currentQuestionIndex)) {
                    this.incorrectQuestions.push(this.currentQuestionIndex);
                }
            }
        }

        /**
         * Afficher le résultat de la réponse
         */
        showAnswerResult(isCorrect, question) {
            // Marquer toutes les réponses
            $('.answer-option').each((index, element) => {
                const $option = $(element);
                const answerIndex = $option.data('answer-index');
                const answer = question.answers[answerIndex];
                
                if (answer.is_correct) {
                    $option.addClass('correct');
                    $option.find('.answer-icon').html('✓');
                } else if ($option.hasClass('selected')) {
                    $option.addClass('incorrect');
                    $option.find('.answer-icon').html('✗');
                }
                
                // Désactiver le clic
                $option.css('pointer-events', 'none');
            });
            
            // Afficher l'explication si disponible et configurée
            if (question.question_explanation && this.currentQuiz.settings && this.currentQuiz.settings.show_explanations) {
                const explanationHtml = `
                    <div class="answer-explanation fade-in">
                        <h4>Explication :</h4>
                        <p>${question.question_explanation}</p>
                    </div>
                `;
                $('.question-content').append(explanationHtml);
            }
            
            // Changer le bouton
            const isLastQuestion = this.currentQuestionIndex >= this.currentQuiz.questions.length - 1;
            const buttonText = isLastQuestion ? 'Voir les résultats' : 'Question suivante';
            const buttonClass = isLastQuestion ? 'btn-success' : 'btn-primary';
            
            $('.question-actions').html(`
                <button class="btn ${buttonClass} next-question-btn">
                    ${buttonText}
                </button>
            `);
            
            // Animation de feedback
            if (isCorrect) {
                this.showFeedback('correct', acme_biaquiz_ajax.strings.correct);
            } else {
                this.showFeedback('incorrect', acme_biaquiz_ajax.strings.incorrect);
            }
        }

        /**
         * Afficher un feedback visuel
         */
        showFeedback(type, message) {
            const feedbackClass = type === 'correct' ? 'success' : 'error';
            const icon = type === 'correct' ? '✓' : '✗';
            
            const feedback = $(`
                <div class="feedback-popup ${feedbackClass}">
                    <span class="feedback-icon">${icon}</span>
                    <span class="feedback-message">${message}</span>
                </div>
            `);
            
            $('body').append(feedback);
            
            setTimeout(() => {
                feedback.addClass('show');
            }, 100);
            
            setTimeout(() => {
                feedback.removeClass('show');
                setTimeout(() => feedback.remove(), 300);
            }, 2000);
        }

        /**
         * Passer à la question suivante
         */
        nextQuestion() {
            this.currentQuestionIndex++;
            
            if (this.currentQuestionIndex >= this.currentQuiz.questions.length) {
                this.finishQuiz();
            } else {
                this.showQuestion(this.currentQuestionIndex);
            }
        }

        /**
         * Terminer le quiz
         */
        finishQuiz() {
            const totalQuestions = this.currentQuiz.questions.length;
            const correctAnswers = this.userAnswers.filter(answer => answer.isCorrect).length;
            const score = correctAnswers;
            const percentage = Math.round((score / totalQuestions) * 100);
            const totalTime = Date.now() - this.startTime;
            
            // Si score parfait ou pas de questions incorrectes, afficher les résultats finaux
            if (score === totalQuestions || this.incorrectQuestions.length === 0) {
                this.showFinalResults(score, totalQuestions, percentage, totalTime);
                this.saveScore(score, totalQuestions, totalTime);
            } else {
                // Proposer de refaire les questions incorrectes
                this.showRetryOption(score, totalQuestions, percentage);
            }
        }

        /**
         * Afficher l'option de révision
         */
        showRetryOption(score, totalQuestions, percentage) {
            const incorrectCount = this.incorrectQuestions.length;
            
            const html = `
                <div class="quiz-results">
                    <div class="score-container">
                        <h2 class="score-title">Résultats intermédiaires</h2>
                        <div class="score-value">${score}/${totalQuestions}</div>
                        <div class="score-percentage">${percentage}%</div>
                        <p class="score-message">
                            Vous avez ${incorrectCount} question${incorrectCount > 1 ? 's' : ''} incorrecte${incorrectCount > 1 ? 's' : ''}.
                            Voulez-vous les réviser pour obtenir 20/20 ?
                        </p>
                        <div class="quiz-actions">
                            <button class="btn btn-primary retry-incorrect-btn">
                                Réviser les questions incorrectes
                            </button>
                            <button class="btn btn-secondary finish-quiz-btn">
                                Terminer avec ce score
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('.quiz-container').html(html);
            
            // Lier les événements
            $('.retry-incorrect-btn').on('click', () => {
                this.retryIncorrectQuestions();
            });
            
            $('.finish-quiz-btn').on('click', () => {
                this.showFinalResults(score, totalQuestions, percentage, Date.now() - this.startTime);
                this.saveScore(score, totalQuestions, Date.now() - this.startTime);
            });
        }

        /**
         * Refaire les questions incorrectes
         */
        retryIncorrectQuestions() {
            this.isRetryMode = true;
            this.currentQuestionIndex = 0;
            
            // Créer un nouveau quiz avec seulement les questions incorrectes
            const incorrectQuestions = this.incorrectQuestions.map(index => this.currentQuiz.questions[index]);
            this.currentQuiz.questions = incorrectQuestions;
            
            // Réinitialiser les réponses pour ces questions
            this.userAnswers = [];
            this.incorrectQuestions = [];
            
            this.renderQuizInterface();
            this.showQuestion(0);
        }

        /**
         * Afficher les résultats finaux
         */
        showFinalResults(score, totalQuestions, percentage, totalTime) {
            const isPerfect = score === totalQuestions;
            const minutes = Math.floor(totalTime / 60000);
            const seconds = Math.floor((totalTime % 60000) / 1000);
            const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            let messageClass = 'score-excellent';
            let message = 'Score parfait ! Félicitations !';
            let emoji = '🎉';
            
            if (percentage < 50) {
                messageClass = 'score-poor';
                message = 'Continuez à vous entraîner !';
                emoji = '📚';
            } else if (percentage < 75) {
                messageClass = 'score-good';
                message = 'Bon travail ! Vous progressez !';
                emoji = '👍';
            } else if (percentage < 100) {
                messageClass = 'score-very-good';
                message = 'Très bon score !';
                emoji = '⭐';
            }
            
            const html = `
                <div class="quiz-results">
                    <div class="score-container ${messageClass}">
                        <div class="score-emoji">${emoji}</div>
                        <h2 class="score-title">${isPerfect ? acme_biaquiz_ajax.strings.perfect_score : 'Quiz terminé !'}</h2>
                        <div class="score-value">${score}/${totalQuestions}</div>
                        <div class="score-percentage">${percentage}%</div>
                        <div class="score-time">Temps : ${timeString}</div>
                        <p class="score-message">${message}</p>
                        <div class="quiz-actions">
                            <button class="btn btn-primary restart-quiz-btn">
                                Recommencer ce quiz
                            </button>
                            <button class="btn btn-secondary back-home-btn">
                                Retour à l'accueil
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('.quiz-container').html(html);
            
            // Mettre à jour la barre de progression à 100%
            $('.quiz-progress-bar').css('width', '100%');
            
            // Animation de célébration pour un score parfait
            if (isPerfect) {
                this.celebrateSuccess();
            }
        }

        /**
         * Animation de célébration
         */
        celebrateSuccess() {
            // Créer des confettis ou une animation de célébration
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = $('<div class="confetti"></div>');
                    confetti.css({
                        position: 'fixed',
                        left: Math.random() * 100 + '%',
                        top: '-10px',
                        width: '10px',
                        height: '10px',
                        backgroundColor: colors[Math.floor(Math.random() * colors.length)],
                        zIndex: 9999,
                        borderRadius: '50%'
                    });
                    
                    $('body').append(confetti);
                    
                    confetti.animate({
                        top: '100vh',
                        left: '+=' + (Math.random() * 200 - 100) + 'px'
                    }, 3000, function() {
                        $(this).remove();
                    });
                }, i * 100);
            }
        }

        /**
         * Recommencer le quiz
         */
        restartQuiz() {
            if (this.currentQuiz && this.currentQuiz.id) {
                this.startQuiz(this.currentQuiz.id);
            }
        }

        /**
         * Sauvegarder le score
         */
        saveScore(score, totalQuestions, totalTime) {
            if (!this.currentQuiz || !this.currentQuiz.id) return;
            
            $.ajax({
                url: acme_biaquiz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_score',
                    quiz_id: this.currentQuiz.id,
                    score: score,
                    total: totalQuestions,
                    time: Math.floor(totalTime / 1000),
                    nonce: acme_biaquiz_ajax.nonce
                },
                success: (response) => {
                    console.log('Score sauvegardé:', response);
                },
                error: (xhr, status, error) => {
                    console.error('Erreur lors de la sauvegarde du score:', error);
                }
            });
        }

        /**
         * Afficher le chargement
         */
        showLoading() {
            $('.quiz-container').html(`
                <div class="quiz-loading">
                    <div class="loading-spinner"></div>
                    <p>${acme_biaquiz_ajax.strings.loading}</p>
                </div>
            `);
        }

        /**
         * Afficher une erreur
         */
        showError(message) {
            $('.quiz-container').html(`
                <div class="quiz-error">
                    <h3>Erreur</h3>
                    <p>${message}</p>
                    <button class="btn btn-primary back-home-btn">Retour à l'accueil</button>
                </div>
            `);
        }
    }

    // Initialiser le quiz au chargement du DOM
    $(document).ready(function() {
        window.BIAQuiz = new BIAQuiz();
    });

})(jQuery);

