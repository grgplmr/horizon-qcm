<?php
/**
 * Template pour afficher les quiz d'une catégorie
 *
 * @package ACME_BIAQuiz
 */

get_header(); ?>

<main class="main-content">
    <div class="container">
        <?php
        $term = get_queried_object();
        $category_meta = BIAQuiz_Taxonomies::get_category_meta($term->term_id);
        $quizzes = BIAQuiz_Handler::get_category_quizzes_with_stats($term->slug);
        ?>
        
        <!-- En-tête de la catégorie -->
        <header class="category-header">
            <div class="category-icon" style="font-size: 3rem; margin-bottom: 1rem;">
                <?php echo $category_meta['icon'] ?: '📚'; ?>
            </div>
            
            <h1 class="category-title" style="color: <?php echo $category_meta['color']; ?>;">
                <?php echo esc_html($term->name); ?>
            </h1>
            
            <?php if ($term->description) : ?>
                <p class="category-description">
                    <?php echo esc_html($term->description); ?>
                </p>
            <?php endif; ?>
            
            <!-- Statistiques de la catégorie -->
            <div class="category-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($quizzes); ?></span>
                        <span class="stat-label">Quiz disponibles</span>
                    </div>
                    <?php
                    $total_attempts = 0;
                    $total_avg_score = 0;
                    $quiz_count = 0;
                    
                    foreach ($quizzes as $quiz) {
                        if ($quiz->stats['total_attempts'] > 0) {
                            $total_attempts += $quiz->stats['total_attempts'];
                            $total_avg_score += $quiz->stats['avg_score'];
                            $quiz_count++;
                        }
                    }
                    
                    $overall_avg = $quiz_count > 0 ? $total_avg_score / $quiz_count : 0;
                    ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($total_attempts); ?></span>
                        <span class="stat-label">Tentatives totales</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($overall_avg, 1); ?>/20</span>
                        <span class="stat-label">Score moyen</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filtres et tri -->
        <div class="quiz-filters">
            <div class="filter-group">
                <label for="difficulty-filter">Difficulté :</label>
                <select id="difficulty-filter" class="filter-select">
                    <option value="">Toutes</option>
                    <option value="facile">Facile</option>
                    <option value="moyen">Moyen</option>
                    <option value="difficile">Difficile</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-filter">Trier par :</label>
                <select id="sort-filter" class="filter-select">
                    <option value="number">Numéro</option>
                    <option value="title">Titre</option>
                    <option value="difficulty">Difficulté</option>
                    <option value="attempts">Popularité</option>
                    <option value="score">Score moyen</option>
                </select>
            </div>
        </div>

        <!-- Liste des quiz -->
        <?php if (!empty($quizzes)) : ?>
            <section class="quizzes-section">
                <div class="quiz-grid" id="quiz-grid">
                    <?php foreach ($quizzes as $quiz) : 
                        if (!$quiz->meta['active']) continue; // Ignorer les quiz inactifs
                        
                        $quiz_number = $quiz->meta['number'];
                        $quiz_difficulty = $quiz->meta['difficulty'];
                        $quiz_time_limit = $quiz->meta['time_limit'];
                        $quiz_stats = $quiz->stats;
                    ?>
                        <div class="quiz-card" 
                             data-difficulty="<?php echo esc_attr($quiz_difficulty); ?>"
                             data-number="<?php echo esc_attr($quiz_number ?: 999); ?>"
                             data-title="<?php echo esc_attr($quiz->post_title); ?>"
                             data-attempts="<?php echo esc_attr($quiz_stats['total_attempts']); ?>"
                             data-score="<?php echo esc_attr($quiz_stats['avg_score']); ?>">
                            
                            <div class="quiz-card-header">
                                <?php if ($quiz_number) : ?>
                                    <span class="quiz-number">Quiz #<?php echo $quiz_number; ?></span>
                                <?php endif; ?>
                                
                                <?php if ($quiz_difficulty) : ?>
                                    <?php
                                    $difficulty_config = array(
                                        'facile' => array('label' => 'Facile', 'color' => '#10b981'),
                                        'moyen' => array('label' => 'Moyen', 'color' => '#f59e0b'),
                                        'difficile' => array('label' => 'Difficile', 'color' => '#ef4444')
                                    );
                                    $diff = $difficulty_config[$quiz_difficulty] ?? array('label' => ucfirst($quiz_difficulty), 'color' => '#6b7280');
                                    ?>
                                    <span class="quiz-difficulty" style="color: <?php echo $diff['color']; ?>;">
                                        ● <?php echo $diff['label']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="quiz-card-title">
                                <a href="<?php echo get_permalink($quiz->ID); ?>">
                                    <?php echo esc_html($quiz->post_title); ?>
                                </a>
                            </h3>
                            
                            <?php if ($quiz->post_excerpt) : ?>
                                <p class="quiz-card-excerpt">
                                    <?php echo esc_html(wp_trim_words($quiz->post_excerpt, 20)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Métadonnées du quiz -->
                            <div class="quiz-card-meta">
                                <?php if ($quiz_time_limit && $quiz_time_limit > 0) : ?>
                                    <span class="meta-item">
                                        ⏱️ <?php echo $quiz_time_limit; ?> min
                                    </span>
                                <?php endif; ?>
                                <span class="meta-item">
                                    📝 20 questions
                                </span>
                            </div>
                            
                            <!-- Statistiques -->
                            <?php if ($quiz_stats['total_attempts'] > 0) : ?>
                                <div class="quiz-card-stats">
                                    <div class="stat-row">
                                        <span class="stat">
                                            📊 Score moyen : <?php echo number_format($quiz_stats['avg_score'], 1); ?>/20
                                        </span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat">
                                            🎯 Taux de réussite : <?php echo $quiz_stats['success_rate']; ?>%
                                        </span>
                                        <span class="stat">
                                            👥 <?php echo number_format($quiz_stats['total_attempts']); ?> tentatives
                                        </span>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="quiz-card-stats">
                                    <span class="stat new-quiz">🆕 Nouveau quiz</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="quiz-card-actions">
                                <a href="<?php echo get_permalink($quiz->ID); ?>" class="btn btn-primary">
                                    🚀 Commencer le quiz
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else : ?>
            <div class="no-quizzes">
                <div class="no-quizzes-content">
                    <div class="no-quizzes-icon">📚</div>
                    <h3>Aucun quiz disponible</h3>
                    <p>Il n'y a pas encore de quiz dans cette catégorie.</p>
                    <a href="<?php echo home_url('/'); ?>" class="btn btn-primary">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation vers d'autres catégories -->
        <section class="other-categories">
            <h3>Autres domaines d'entraînement</h3>
            <div class="category-grid">
                <?php
                $all_categories = BIAQuiz_Taxonomies::get_active_categories();
                $other_categories = array_filter($all_categories, function($cat) use ($term) {
                    return $cat->term_id !== $term->term_id;
                });
                
                foreach (array_slice($other_categories, 0, 5) as $category) :
                    $cat_meta = BIAQuiz_Taxonomies::get_category_meta($category->term_id);
                    $cat_quiz_count = $category->count;
                ?>
                    <a href="<?php echo get_term_link($category); ?>" class="category-card">
                        <div class="category-icon" style="font-size: 2rem;">
                            <?php echo $cat_meta['icon'] ?: '📚'; ?>
                        </div>
                        <h4 class="category-title" style="color: <?php echo $cat_meta['color']; ?>;">
                            <?php echo esc_html($category->name); ?>
                        </h4>
                        <p class="category-description">
                            <?php echo esc_html(wp_trim_words($category->description, 15)); ?>
                        </p>
                        <div class="category-stats">
                            <?php echo $cat_quiz_count; ?> quiz disponible<?php echo $cat_quiz_count > 1 ? 's' : ''; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<style>
/* Styles pour la page de catégorie */
.category-header {
    text-align: center;
    padding: 3rem 0;
    margin-bottom: 3rem;
}

.category-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.category-description {
    font-size: 1.125rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto 2rem;
}

body.dark-mode .category-description {
    color: #9ca3af;
}

.category-stats {
    margin-top: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 2rem;
    max-width: 600px;
    margin: 0 auto;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

body.dark-mode .stat-label {
    color: #9ca3af;
}

/* Filtres */
.quiz-filters {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--light-surface);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

body.dark-mode .quiz-filters {
    background: var(--dark-surface);
    border-color: #374151;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: var(--text-dark);
}

body.dark-mode .filter-group label {
    color: var(--text-light);
}

.filter-select {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
    background: var(--light-surface);
    color: var(--text-dark);
}

body.dark-mode .filter-select {
    background: var(--dark-surface);
    border-color: #374151;
    color: var(--text-light);
}

/* Quiz cards */
.quiz-card {
    background: var(--light-surface);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
}

body.dark-mode .quiz-card {
    background: var(--dark-surface);
    border-color: #374151;
}

.quiz-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.quiz-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.quiz-number {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-weight: 500;
}

.quiz-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.quiz-card-title a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.3s ease;
}

body.dark-mode .quiz-card-title a {
    color: var(--text-light);
}

.quiz-card-title a:hover {
    color: var(--primary-color);
}

.quiz-card-excerpt {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

body.dark-mode .quiz-card-excerpt {
    color: #9ca3af;
}

.quiz-card-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

body.dark-mode .quiz-card-meta {
    color: #9ca3af;
}

.quiz-card-stats {
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    color: #6b7280;
}

body.dark-mode .stat-row {
    color: #9ca3af;
}

.new-quiz {
    color: var(--success-color);
    font-weight: 500;
}

.quiz-card-actions {
    text-align: center;
}

/* Pas de quiz */
.no-quizzes {
    text-align: center;
    padding: 4rem 0;
}

.no-quizzes-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.no-quizzes h3 {
    font-size: 1.5rem;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

body.dark-mode .no-quizzes h3 {
    color: var(--text-light);
}

.no-quizzes p {
    color: #6b7280;
    margin-bottom: 2rem;
}

body.dark-mode .no-quizzes p {
    color: #9ca3af;
}

/* Autres catégories */
.other-categories {
    margin-top: 4rem;
    padding-top: 3rem;
    border-top: 1px solid var(--border-color);
}

body.dark-mode .other-categories {
    border-color: #374151;
}

.other-categories h3 {
    text-align: center;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .quiz-filters {
        flex-direction: column;
        gap: 1rem;
    }
    
    .category-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .quiz-card-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .stat-row {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
// Script pour les filtres et le tri
document.addEventListener('DOMContentLoaded', function() {
    const difficultyFilter = document.getElementById('difficulty-filter');
    const sortFilter = document.getElementById('sort-filter');
    const quizGrid = document.getElementById('quiz-grid');
    const quizCards = Array.from(quizGrid.querySelectorAll('.quiz-card'));

    function filterAndSort() {
        const difficulty = difficultyFilter.value;
        const sortBy = sortFilter.value;

        // Filtrer
        let filteredCards = quizCards.filter(card => {
            if (!difficulty) return true;
            return card.dataset.difficulty === difficulty;
        });

        // Trier
        filteredCards.sort((a, b) => {
            switch (sortBy) {
                case 'number':
                    return parseInt(a.dataset.number) - parseInt(b.dataset.number);
                case 'title':
                    return a.dataset.title.localeCompare(b.dataset.title);
                case 'difficulty':
                    const diffOrder = { 'facile': 1, 'moyen': 2, 'difficile': 3 };
                    return (diffOrder[a.dataset.difficulty] || 0) - (diffOrder[b.dataset.difficulty] || 0);
                case 'attempts':
                    return parseInt(b.dataset.attempts) - parseInt(a.dataset.attempts);
                case 'score':
                    return parseFloat(b.dataset.score) - parseFloat(a.dataset.score);
                default:
                    return 0;
            }
        });

        // Masquer toutes les cartes
        quizCards.forEach(card => card.style.display = 'none');

        // Afficher les cartes filtrées et triées
        filteredCards.forEach(card => card.style.display = 'block');

        // Réorganiser dans le DOM
        filteredCards.forEach(card => quizGrid.appendChild(card));
    }

    difficultyFilter.addEventListener('change', filterAndSort);
    sortFilter.addEventListener('change', filterAndSort);
});
</script>

<?php get_footer(); ?>

