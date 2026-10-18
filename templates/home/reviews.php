<?php $reviews = db_all('SELECT * FROM reviews WHERE is_active = 1 ORDER BY sort_order, id LIMIT 4'); ?>
<?php if ($reviews): ?>
<section class="section section--tint reviews">
    <div class="container">
        <div class="reviews__head">
            <div>
                <h2 class="section-title"><?= e(setting('home_reviews_title')) ?></h2>
                <?php if (setting('home_reviews_text') !== ''): ?><p class="section-sub"><?= e(setting('home_reviews_text')) ?></p><?php endif; ?>
            </div>
            <a class="reviews__badge" href="<?= e(setting('google_reviews_url')) ?>" target="_blank" rel="noopener">
                <?= google_logo(26) ?>
                <span class="reviews__badge-score"><?= e(setting('google_rating')) ?></span>
                <span class="reviews__badge-meta">
                    <?= rating_stars((float) setting('google_rating'), 16) ?>
                    <span><?= e(setting('google_review_count')) ?> reviews</span>
                </span>
            </a>
        </div>
        <div class="reviews__list">
            <?php foreach ($reviews as $review): ?>
                <figure class="review-card">
                    <svg class="review-card__quote" width="28" height="22" viewBox="0 0 28 22" aria-hidden="true"><path fill="currentColor" d="M0 22V13.2C0 5.7 4.1 1.3 11.2 0l1.3 3.1C8.6 4.4 6.6 7 6.4 10.6H12V22H0Zm16 0V13.2C16 5.7 20.1 1.3 27.2 0l1.3 3.1c-3.9 1.3-5.9 3.9-6.1 7.5H28V22H16Z"/></svg>
                    <blockquote><?= e($review['body']) ?></blockquote>
                    <figcaption class="review-card__foot">
                        <span class="review-card__avatar" style="--avatar: <?= e(avatar_colour($review['author'])) ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($review['author'], 0, 1))) ?></span>
                        <span class="review-card__who"><strong><?= e($review['author']) ?></strong><span><?= e($review['source']) ?> review</span></span>
                        <?= google_logo(18) ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
