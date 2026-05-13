<?php
/**
 * Site Footer Component
 * Renders testimonials strip and copyright notice.
 */
require_once __DIR__ . '/config.php';
?>
<footer class="site-footer">
    <div class="footer-inner">
        <p class="footer-title">What Our <span>Clients</span> Say</p>
        <div class="testimonials">
            <div class="testimonial">
                <p>"This website has been so useful in searching for a car. As a first-time buyer the process was overwhelming, but having everything in one place made it so much easier."</p>
                <cite>— Brenda, Nairobi</cite>
            </div>
            <div class="testimonial">
                <p>"It's rare that I don't find what I'm looking for. Own It &amp; Drive It will be my first and last stop the next time I'm in the market for a vehicle."</p>
                <cite>— Geoffrey, Mombasa</cite>
            </div>
        </div>
        <p class="footer-copy">
            &copy; <?php echo date('Y'); ?> &nbsp;·&nbsp;
            <?php echo htmlspecialchars(SITE_NAME); ?> &nbsp;·&nbsp;
            <?php echo htmlspecialchars(SITE_LOCATION); ?>
        </p>
    </div>
</footer>

<button id="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">↑</button>
<script src="/assets/js/main.js"></script>
