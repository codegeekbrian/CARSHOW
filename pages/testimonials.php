<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/csrf.php';
require_once __DIR__ . '/../include/settings.php';
require_once __DIR__ . '/../include/testimonials.php';

$current_page = 'testimonials';
$pageTitle = 'Testimonials';
$pageDescription = 'Read customer notes and share your visit with ' . SITE_NAME . '.';

try {
    require_once __DIR__ . '/../include/db.php';
    $settings = loadSettings($db);
    $testimonialRows = getActiveTestimonials(12, $db);
} catch (Exception $e) {
    $settings = [];
    $testimonialRows = [];
}

if (empty($testimonialRows)) {
    $testimonialRows = testimonialFallbackRows($settings);
}

if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
<main>
    <div class="site-hero">
        <h1>Client <span>Stories</span></h1>
        <p>Feedback from customers who have visited, reserved, or bought through our branches.</p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content testimonials-page">
        <section class="testimonial-wall" aria-label="Client testimonials">
            <?php foreach ($testimonialRows as $t): ?>
                <blockquote class="testimonial-card">
                    <p>"<?php echo h($t['quote']); ?>"</p>
                    <cite>
                        <?php echo h($t['client_name']); ?> / <?php echo h($t['location']); ?>
                        <?php if (!empty($t['verified_buyer'])): ?>
                            <span class="verified-buyer-badge">
                                Verified Buyer<?php echo !empty(trim((string)($t['vehicle_label'] ?? ''))) ? ' / Bought a ' . h(trim((string)$t['vehicle_label'])) : ''; ?>
                            </span>
                        <?php endif; ?>
                    </cite>
                </blockquote>
            <?php endforeach; ?>
        </section>

        <section class="comment-wrap testimonial-submit" aria-labelledby="testimonial-form-title">
            <p class="comment-kicker">Share feedback</p>
            <h2 id="testimonial-form-title">Leave us a comment</h2>
            <p class="comment-copy">Share a short note about your visit, purchase, or vehicle search. We review comments before publishing them.</p>
            <form action="<?php echo url('api/testimonial-comment'); ?>" method="POST" data-ajax-form data-required-fields="client_name,quote" data-submit-label="Submit Comment">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="client_name">Name</label>
                    <input type="text" id="client_name" name="client_name" placeholder="Your name" maxlength="120" required>
                </div>
                <div class="form-group">
                    <label for="comment_location">Location</label>
                    <input type="text" id="comment_location" name="location" placeholder="Nairobi, Mombasa, etc." maxlength="120">
                </div>
                <div class="form-group">
                    <label for="quote">Comment</label>
                    <textarea id="quote" name="quote" rows="4" placeholder="Tell us what stood out..." maxlength="700" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Comment</button>
                <div class="form-response" aria-live="polite"></div>
            </form>
        </section>
    </div>
</main>

<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
