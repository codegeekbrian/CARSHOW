<?php
/**
 * About Us Page
 * Describes the company and displays key statistics.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/csrf.php';
require_once __DIR__ . '/../include/settings.php';

$current_page = 'about';
$pageTitle = "About Us";

try {
    require_once __DIR__ . '/../include/db.php';
    $settings = loadSettings($db);
} catch (Exception $e) {
    $settings = [];
    // Fallback values
}
if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
    <main>
        <div class="site-hero">
        <h1>ABOUT <span>US</span></h1>
        <p><?php echo h($settings['about_tagline'] ?? "Kenya's trusted home for quality locally available vehicles and automotive passion."); ?></p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content">
        <div class="about-box">
            <h2><?php echo h(SITE_NAME); ?></h2>
            <p><?php echo h(str_replace('{site_name}', SITE_NAME, $settings['about_p1'] ?? (SITE_NAME . " is a car dealership incorporated in Kenya, specialising in the sale of new and second-hand motor vehicles. We pride ourselves on offering quality, carefully sourced vehicles guided by our core values and a burning desire to satisfy customer needs."))); ?></p>
            <p><?php echo h($settings['about_p2'] ?? "Vehicles in our dealership are sourced locally and from reputable international markets — ensuring variety to meet every taste and budget in the Kenyan market."); ?></p>
            <p><?php echo h($settings['about_p3_intro'] ?? "Beyond the showroom, we help customers compare vehicles, arrange viewing appointments, and understand financing or trade-in options before they visit a branch."); ?></p>
            <p><?php echo h($settings['about_p4'] ?? "Whether you are a first-time buyer or a seasoned collector, our team is here to make the process easy, transparent, and safe. <strong>Own it. Drive it. Love it.</strong>"); ?></p>
        </div>

        <div class="stat-strip">
            <div class="stat-item">
                <div class="stat-num"><?php echo $settings['available_models'] ?? '40'; ?>+</div>
                <div class="stat-label"><?php echo h($settings['stat_label_available_models'] ?? 'Available Models'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?php echo $settings['source_countries'] ?? '10'; ?>+</div>
                <div class="stat-label"><?php echo h($settings['stat_label_countries'] ?? 'Source Countries'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?php echo $settings['brands_count'] ?? '12'; ?></div>
                <div class="stat-label"><?php echo h($settings['stat_label_brands'] ?? 'Brands in Inventory'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?php echo $settings['online_showcase'] ?? '24/7'; ?></div>
                <div class="stat-label"><?php echo h($settings['stat_label_showcase'] ?? 'Online Showcase'); ?></div>
            </div>
        </div>
    </div>
</main>

<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
