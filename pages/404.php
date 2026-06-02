<?php http_response_code(404);
require_once __DIR__ . '/../include/config.php';
$current_page = '404';
$pageTitle = "404";
if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
    <main>
        <div class="site-hero">
        <h1>404</h1>
        <p>The road ends here. The page you're looking for doesn't exist or has been moved.</p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content text-center">
        <p class="text-muted mb-2">
            Perhaps you'd like to explore what we do have?
        </p>
        <div class="error-page-links">
            <a href="<?php echo url('/'); ?>" class="btn btn-success">Home</a>
            <a href="<?php echo url('inventory'); ?>" class="btn-outline">Inventory</a>
        </div>
    </div>
</main>

<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
