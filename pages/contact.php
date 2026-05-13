<?php
/**
 * Contact Us Page
 * Displays contact form and business information.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/csrf.php';
$current_page = 'contact';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <meta name="description" content="Get in touch with <?php echo htmlspecialchars(SITE_NAME); ?>. Send us a message about vehicles, pricing, or availability.">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/../include/nav.php'; ?>

    <div class="site-hero">
        <h1>GET IN <span>TOUCH</span></h1>
        <p>We'd love to help you find your perfect vehicle. Send us a message and we'll respond promptly.</p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content">
        <div class="contact-wrap">
            <h2>Send a Message</h2>
            <form action="../include/contact_handler.php" method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Your name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Vehicle inquiry, pricing, etc.">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Tell us what you're looking for..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
                <div id="response"></div>
            </form>
        </div>

        <div class="contact-info-strip">
            <div class="contact-info-item">
                <div class="ci-icon">📍</div>
                <div class="ci-label">Location</div>
                <div class="ci-val"><?php echo htmlspecialchars(SITE_LOCATION); ?></div>
            </div>
            <div class="contact-info-item">
                <div class="ci-icon">📧</div>
                <div class="ci-label">Email</div>
                <div class="ci-val"><?php echo htmlspecialchars(CONTACT_EMAIL); ?></div>
            </div>
            <div class="contact-info-item">
                <div class="ci-icon">⏰</div>
                <div class="ci-label">Hours</div>
                <div class="ci-val">Mon–Sat: 8am – 6pm</div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>