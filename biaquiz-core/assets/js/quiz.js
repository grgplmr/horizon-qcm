/**
 * BIAQuiz - Script principal pour les quiz
 * Version plugin
 */

(function($) {
    'use strict';
    
    class BIAQuizPlugin {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            if (!this.container) {
                console.error('BIAQuiz: Container non trouvé');
                return;
            }
            
            this.quizId = this.container.dataset.quizId;
            this.currentQuestion = 0;
            this.score = 0;
            this.answers = [];
            this.timer = null;
            this.startTime = Date.now();
            this.quiz = null;
            this.incorrectQuestions = [];
            
            this.init();
        }
        
        async init() {
            try {
                this.showLoading();
                this.quiz = await this.loadQuiz();
                this.renderQuizHeader();
                this.renderQuestion();
                this.startTimer();
            } catch (error) {
                console.error('Erreur lors du chargement du quiz:', error);
                this.showError(error.message || biaquiz_ajax.strings.error);
            }
        }
        
        showLoading() {
            this.container.innerHTML = `
                <div class="biaquiz-loading">
                    <div class="loading-spinner"></div>
                    <p>${biaquiz_ajax.strings.loading}</p>
                </div>
            `;
        }
        
        showError(message) {
            this.container.innerHTML = `
                <div class="biaquiz-error">
                    <p><strong>Erreur:</strong> ${message}</p>
                    <button onclick="location.reload()" class="biaquiz-btn biaquiz-btn-primary">
                        Réessayer
                    </button>
                </div>
            `;
        }
        
        async loadQuiz() {
            const formData = new FormData();
            formData.append('action', 'biaquiz_get_quiz');
            formData.append('quiz_id', this.quizId);
            formData.append('nonce', biaquiz_ajax.nonce);
            
            const response = await fetch(biaquiz_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data?.message || 'Erreur de chargement');
            }
            
            return data.data;
        }
        
        renderQuizHeader() {
            const headerHtml = `
                <div class="biaquiz-header">
                    <h2 class="quiz-title">${this.quiz.title}</h2>
                    <div class="quiz-info">
                        <span class="quiz-questions">📝 ${this.quiz.total_questions} questions</span>
                        <span class="quiz-difficulty">🎯 ${this.quiz.difficulty}</span>
                        ${this.quiz.time_limit > 0 ? `<span class="quiz-time">⏱️ ${this.quiz.time_limit} min</span>` : ''}
                    </div>
                    <div class="quiz-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <span class="progress-text">Question 1 sur ${this.quiz.total_questions}</span>
                    </div>
                </div>
            `;
            
            this.container.innerHTML = headerHtml + '<div class="biaquiz-content"></div>';
            this.contentContainer = this.container.querySelector('.biaquiz-content');
        }
        
        renderQuestion() {
            const question = this.quiz.questions[this.currentQuestion];
            const questionNumber = this.currentQuestion + 1;
            
            const questionHtml = `
                <div class="biaquiz-question" data-question-id="${question.id}">
                    <div class="question-header">
                        <span class="question-number">Question ${questionNumber}</span>
                        <h3 class="question-text">${question.text}</h3>
                    </div>
                    
                    ${question.image ? `
                        <div class="question-image">
                            <img src="${question.image.url}" alt="${question.image.alt || ''}" />
                        </div>
                    ` : ''}
                    
                    <div class="answers-container">
                        ${question.answers.map(answer => `
                            <div class="answer-option" data-answer="${answer.id}">
                                <div class="answer-content">
                                    <div class="answer-letter">${answer.id}</div>
                                    <div class="answer-text">${answer.text}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="question-explanation" style="display: none;">
                        <div class="explanation-content"></div>
                    </div>
                </div>
            `;
            
            this.contentContainer.innerHTML = questionHtml;
            this.attachAnswerListeners();
            this.updateProgress();
        }
        
        attachAnswerListeners() {
            const options = this.contentContainer.querySelectorAll('.answer-option');
            options.forEach(option => {
                option.addEventListener('click', (e) => {
                    if (option.classList.contains('disabled')) return;
                    this.selectAnswer(option.dataset.answer);
                });
            });
        }
        
        async selectAnswer(answerId) {
            const options = this.contentContainer.querySelectorAll('.answer-option');
            options.forEach(opt => opt.classList.add('disabled'));
            
            try {
                const result = await this.submitAnswer(answerId);
                this.handleAnswerResult(answerId, result);
            } catch (error) {
                console.error('Erreur lors de la soumission:', error);
                this.showError(error.message);
            }
        }
        
        async submitAnswer(answerId) {
            const formData = new FormData();
            formData.append('action', 'biaquiz_submit_answer');
            formData.append('quiz_id', this.quizId);
            formData.append('question_id', this.currentQuestion + 1);
            formData.append('answer_id', answerId);
            formData.append('nonce', biaquiz_ajax.nonce);
            
            const response = await fetch(biaquiz_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data?.message || 'Erreur de soumission');
            }
            
            return data.data;
        }
        
        handleAnswerResult(answerId, result) {
            const selectedOption = this.contentContainer.querySelector(`[data-answer="${answerId}"]`);
            const explanationDiv = this.contentContainer.querySelector('.question-explanation');
            const explanationContent = explanationDiv.querySelector('.explanation-content');
            
            // Marquer la réponse sélectionnée
            if (result.is_correct) {
                selectedOption.classList.add('correct');
                this.score++;
            } else {
                selectedOption.classList.add('incorrect');
                
                // Marquer la bonne réponse
                if (result.correct_answer) {
                    const correctOption = this.contentContainer.querySelector(`[data-answer="${result.correct_answer}"]`);
                    if (correctOption) {
                        correctOption.classList.add('correct');
                    }
                }
                
                // Ajouter à la liste des questions incorrectes pour répétition
                if (!this.incorrectQuestions.includes(this.currentQuestion)) {
                    this.incorrectQuestions.push(this.currentQuestion);
                }
            }
            
            // Afficher l'explication si disponible
            if (result.explanation) {
                explanationContent.innerHTML = `
                    <div class="explanation-icon">💡</div>
                    <div class="explanation-text">${result.explanation}</div>
                `;
                explanationDiv.style.display = 'block';
            }
            
            // Bouton pour continuer
            const continueButton = document.createElement('button');
            continueButton.className = 'biaquiz-btn biaquiz-btn-primary continue-btn';
            continueButton.textContent = result.is_correct ? 
                biaquiz_ajax.strings.next_question : 
                'Réessayer';
            
            continueButton.addEventListener('click', () => {
                if (result.is_correct) {
                    this.nextQuestion();
                } else {
                    this.retryQuestion();
                }
            });
            
            this.contentContainer.appendChild(continueButton);
            
            // Auto-continuer après 3 secondes si correct
            if (result.is_correct) {
                setTimeout(() => {
                    if (continueButton.parentNode) {
                        this.nextQuestion();
                    }
                }, 3000);
            }
        }
        
        retryQuestion() {
            // Réinitialiser la question actuelle
            this.renderQuestion();
        }
        
        nextQuestion() {
            this.currentQuestion++;
            
            // Vérifier s'il reste des questions ou des questions incorrectes à répéter
            if (this.currentQuestion >= this.quiz.questions.length) {
                if (this.incorrectQuestions.length > 0) {
                    // Répéter les questions incorrectes
                    this.currentQuestion = this.incorrectQuestions.shift();
                    this.renderQuestion();
                } else {
                    // Quiz terminé
                    this.completeQuiz();
                }
            } else {
                this.renderQuestion();
            }
        }
        
        async completeQuiz() {
            const endTime = Date.now();
            const timeTaken = Math.floor((endTime - this.startTime) / 1000);
            
            try {
                await this.submitCompletion(timeTaken);
                this.showResults(timeTaken);
            } catch (error) {
                console.error('Erreur lors de la finalisation:', error);
                this.showResults(timeTaken);
            }
        }
        
        async submitCompletion(timeTaken) {
            const formData = new FormData();
            formData.append('action', 'biaquiz_complete_quiz');
            formData.append('quiz_id', this.quizId);
            formData.append('score', this.score);
            formData.append('total_questions', this.quiz.total_questions);
            formData.append('time_taken', timeTaken);
            formData.append('nonce', biaquiz_ajax.nonce);
            
            const response = await fetch(biaquiz_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : null;
        }
        
        showResults(timeTaken) {
            const percentage = Math.round((this.score / this.quiz.total_questions) * 100);
            const minutes = Math.floor(timeTaken / 60);
            const seconds = timeTaken % 60;
            
            let resultClass = 'excellent';
            let resultMessage = 'Excellent !';
            
            if (percentage < 60) {
                resultClass = 'needs-improvement';
                resultMessage = 'À améliorer';
            } else if (percentage < 80) {
                resultClass = 'good';
                resultMessage = 'Bien';
            } else if (percentage < 95) {
                resultClass = 'very-good';
                resultMessage = 'Très bien';
            }
            
            const resultsHtml = `
                <div class="biaquiz-results ${resultClass}">
                    <div class="results-header">
                        <h2>${biaquiz_ajax.strings.quiz_completed}</h2>
                        <div class="result-badge">${resultMessage}</div>
                    </div>
                    
                    <div class="score-display">
                        <div class="score-circle">
                            <div class="score-value">${this.score}/${this.quiz.total_questions}</div>
                            <div class="score-percentage">${percentage}%</div>
                        </div>
                    </div>
                    
                    <div class="results-details">
                        <div class="detail-item">
                            <span class="detail-label">${biaquiz_ajax.strings.score}:</span>
                            <span class="detail-value">${this.score}/${this.quiz.total_questions}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">${biaquiz_ajax.strings.time}:</span>
                            <span class="detail-value">${minutes}:${seconds.toString().padStart(2, '0')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pourcentage:</span>
                            <span class="detail-value">${percentage}%</span>
                        </div>
                    </div>
                    
                    <div class="results-actions">
                        <button onclick="location.reload()" class="biaquiz-btn biaquiz-btn-primary">
                            ${biaquiz_ajax.strings.restart}
                        </button>
                        <a href="/categories" class="biaquiz-btn biaquiz-btn-secondary">
                            ${biaquiz_ajax.strings.back_to_categories}
                        </a>
                    </div>
                </div>
            `;
            
            this.container.innerHTML = resultsHtml;
            
            // Animation du score
            this.animateScore(percentage);
        }
        
        animateScore(targetPercentage) {
            const scoreCircle = this.container.querySelector('.score-circle');
            if (!scoreCircle) return;
            
            let currentPercentage = 0;
            const increment = targetPercentage / 50; // Animation sur 50 frames
            
            const animate = () => {
                currentPercentage += increment;
                if (currentPercentage >= targetPercentage) {
                    currentPercentage = targetPercentage;
                } else {
                    requestAnimationFrame(animate);
                }
                
                scoreCircle.style.setProperty('--percentage', currentPercentage + '%');
            };
            
            requestAnimationFrame(animate);
        }
        
        updateProgress() {
            const progressFill = this.container.querySelector('.progress-fill');
            const progressText = this.container.querySelector('.progress-text');
            
            if (progressFill && progressText) {
                const percentage = ((this.currentQuestion + 1) / this.quiz.total_questions) * 100;
                progressFill.style.width = percentage + '%';
                progressText.textContent = `Question ${this.currentQuestion + 1} sur ${this.quiz.total_questions}`;
            }
        }
        
        startTimer() {
            if (this.quiz.time_limit > 0) {
                // Implémenter le timer si nécessaire
                // Pour l'instant, on laisse sans limite de temps
            }
        }
    }
    
    // Initialisation automatique
    $(document).ready(function() {
        const quizContainer = document.getElementById('biaquiz-container');
        if (quizContainer && quizContainer.dataset.quizId) {
            new BIAQuizPlugin('biaquiz-container');
        }
    });
    
    // Exposer la classe globalement pour usage externe
    window.BIAQuizPlugin = BIAQuizPlugin;
    
})(jQuery);

