<?php
/**
 * Template pour afficher un quiz individuel
 *
 * @package ACME_BIAQuiz
 */

get_header(); ?>

<main class="main-content">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('quiz-article'); ?>>
                
                <!-- En-tête du quiz -->
                <header class="quiz-header-section">
                    <div class="quiz-breadcrumb">
                        <?php
                        $categories = get_the_terms(get_the_ID(), 'quiz_category');
                        if ($categories && !is_wp_error($categories)) {
                            $category = $categories[0];
                            $category_meta = BIAQuiz_Taxonomies::get_category_meta($category->term_id);
                            echo '<a href="' . get_term_link($category) . '" class="category-link">';
                            echo '<span class="category-icon">' . ($category_meta['icon'] ?: '📚') . '</span>';
                            echo esc_html($category->name);
                            echo '</a>';
                        }
                        ?>
                    </div>
                    
                    <h1 class="quiz-title"><?php the_title(); ?></h1>
                    
                    <?php if (get_the_excerpt()) : ?>
                        <div class="quiz-description">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Contenu du quiz -->
                <div class="quiz-content-section">
                    <?php
                    // Le contenu sera filtré par BIAQuiz_Handler::filter_quiz_content()
                    // pour ajouter l'interface interactive du quiz
                    the_content();
                    ?>
                </div>

                <!-- Navigation vers d'autres quiz -->
                <?php
                $categories = get_the_terms(get_the_ID(), 'quiz_category');
                if ($categories && !is_wp_error($categories)) {
                    $category = $categories[0];
                    $related_quizzes = BIAQuiz_Handler::get_category_quizzes_with_stats($category->slug);
                    
                    // Filtrer le quiz actuel
                    $related_quizzes = array_filter($related_quizzes, function($quiz) {
                        return $quiz->ID !== get_the_ID();
                    });
                    
                    if (!empty($related_quizzes)) :
                ?>
                    <section class="related-quizzes">
                        <h3>Autres quiz de cette catégorie</h3>
                        <div class="quiz-grid">
                            <?php
                            // Limiter à 6 quiz
                            $related_quizzes = array_slice($related_quizzes, 0, 6);
                            foreach ($related_quizzes as $quiz) :
                                $quiz_number = $quiz->meta['number'];
                                $quiz_difficulty = $quiz->meta['difficulty'];
                                $quiz_stats = $quiz->stats;
                            ?>
                                <div class="quiz-card">
                                    <div class="quiz-card-header">
                                        <?php if ($quiz_number) : ?>
                                            <span class="quiz-number">Quiz #<?php echo $quiz_number; ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($quiz_difficulty) : ?>
                                            <?php
                                            $difficulty_colors = array(
                                                'facile' => '#10b981',
                                                'moyen' => '#f59e0b',
                                                'difficile' => '#ef4444'
                                            );
                                            $color = $difficulty_colors[$quiz_difficulty] ?? '#6b7280';
                                            ?>
                                            <span class="quiz-difficulty" style="color: <?php echo $color; ?>;">
                                                ● <?php echo ucfirst($quiz_difficulty); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h4 class="quiz-card-title">
                                        <a href="<?php echo get_permalink($quiz->ID); ?>">
                                            <?php echo esc_html($quiz->post_title); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if ($quiz->post_excerpt) : ?>
                                        <p class="quiz-card-excerpt">
                                            <?php echo esc_html(wp_trim_words($quiz->post_excerpt, 15)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($quiz_stats['total_attempts'] > 0) : ?>
                                        <div class="quiz-card-stats">
                                            <span class="stat">
                                                📊 <?php echo number_format($quiz_stats['avg_score'], 1); ?>/20
                                            </span>
                                            <span class="stat">
                                                🎯 <?php echo $quiz_stats['success_rate']; ?>%
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="quiz-card-actions">
                                        <a href="<?php echo get_permalink($quiz->ID); ?>" class="btn btn-primary btn-sm">
                                            Commencer
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center" style="margin-top: 2rem;">
                            <a href="<?php echo get_term_link($category); ?>" class="btn btn-secondary">
                                Voir tous les quiz de <?php echo esc_html($category->name); ?>
                            </a>
                        </div>
                    </section>
                <?php endif; } ?>

            </article>
        <?php endwhile; ?>
    </div>
</main>

<style>
/* Styles spécifiques pour la page de quiz */
.quiz-article {
    max-width: 800px;
    margin: 0 auto;
}

.quiz-header-section {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem 0;
}

.quiz-breadcrumb {
    margin-bottom: 1rem;
}

.category-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--light-surface);
    border: 1px solid var(--border-color);
    border-radius: 2rem;
    text-decoration: none;
    color: var(--primary-color);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

body.dark-mode .category-link {
    background: var(--dark-surface);
    border-color: #374151;
}

.category-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-1px);
}

.quiz-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.quiz-description {
    font-size: 1.125rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

body.dark-mode .quiz-description {
    color: #9ca3af;
}

.quiz-content-section {
    margin-bottom: 4rem;
}

/* Quiz cards pour les quiz liés */
.related-quizzes {
    margin-top: 4rem;
    padding-top: 3rem;
    border-top: 1px solid var(--border-color);
}

body.dark-mode .related-quizzes {
    border-color: #374151;
}

.related-quizzes h3 {
    text-align: center;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

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
    font-size: 1.125rem;
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

.quiz-card-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

body.dark-mode .quiz-card-stats {
    color: #9ca3af;
}

.quiz-card-actions {
    text-align: center;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .quiz-title {
        font-size: 2rem;
    }
    
    .quiz-grid {
        grid-template-columns: 1fr;
    }
    
    .quiz-card-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
}
</style>

<?php get_footer(); ?>

