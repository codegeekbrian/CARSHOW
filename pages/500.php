<?php
http_response_code(500);
require_once __DIR__ . '/../include/config.php';
$current_page = '500';
$pageTitle = 'Server Error';
if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
<main>
    <section class="maintenance-page" aria-labelledby="maintenance-title">
        <p class="eyebrow">Service notice</p>
        <h1 id="maintenance-title">We are tuning the showroom experience</h1>
        <p>Something behind the scenes is temporarily unavailable. Please try again shortly, or contact the dealership team if you need immediate assistance.</p>
        <div class="maintenance-actions">
            <a href="<?php echo url('/'); ?>" class="btn">Back to Home</a>
            <a href="<?php echo url('contact'); ?>" class="btn-outline">Contact Us</a>
        </div>
    </section>
</main>
<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
