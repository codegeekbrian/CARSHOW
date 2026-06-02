<?php
/**
 * Site Footer Component
 * Renders footer navigation and copyright notice.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings.php';

if (!isset($settings)) {
    $settings = loadSettings($db ?? null);
}
?>
<footer class="site-footer">
    <div class="footer-inner">
        <nav class="footer-links" aria-label="Footer navigation">
            <a href="<?php echo url('inventory'); ?>">Inventory</a>
            <a href="<?php echo url('testimonials'); ?>">Testimonials</a>
            <a href="<?php echo url('inquire'); ?>">Inquire</a>
        </nav>
        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?php echo date('Y'); ?>
                / <?php echo h(SITE_NAME); ?>
                / <?php echo h(SITE_LOCATION); ?>
            </p>
        </div>
    </div>
</footer>

<button id="back-top" class="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Back to top">Top</button>
<script src="<?php echo url('assets/js/main.js'); ?>"></script>
<?php include __DIR__ . '/analytics.php'; ?>
</body>
</html>
