<?php
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
            <a href="<?php echo url('contact'); ?>">Inquire</a>
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

<button id="back-top" class="back-to-top" aria-label="Back to top">Top</button>
<script src="<?php echo url('assets/js/theme.js'); ?>"></script>
<script src="<?php echo url('assets/js/navigation.js'); ?>"></script>
<script src="<?php echo url('assets/js/app-helpers.js'); ?>"></script>
<script src="<?php echo url('assets/js/customer-journey.js'); ?>"></script>
<script src="<?php echo url('assets/js/inventory-filter.js'); ?>"></script>
<script src="<?php echo url('assets/js/forms.js'); ?>"></script>
<script src="<?php echo url('assets/js/carousel.js'); ?>"></script>
<script src="<?php echo url('assets/js/ui.js'); ?>"></script>
<?php include __DIR__ . '/analytics.php'; ?>
</body>
</html>
