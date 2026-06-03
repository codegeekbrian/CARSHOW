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
}

if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
<main>
    <div class="site-hero">
        <h1>ABOUT <span>THE SHOWROOM</span></h1>
        <p><?php echo h($settings['about_tagline'] ?? "Direct from source, locally available. We bring quality vehicles to your doorstep without the usual showroom jargon."); ?></p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content">
        <section class="about-layout" aria-label="About the showroom">
            <div class="about-box">
                <p class="profile-kicker">How we work</p>
                <h2><?php echo h(SITE_NAME); ?></h2>
                <p><?php echo h(str_replace('{site_name}', SITE_NAME, $settings['about_p1'] ?? (SITE_NAME . " is a Nairobi-based dealership focused on clean, well-maintained vehicles. We handle the sourcing and inspections so you can focus on choosing the right car."))); ?></p>
                <p><?php echo h($settings['about_p2'] ?? "Our stock is a mix of locally sourced units and hand-picked imports from Japan and the UK, covering everything from reliable daily drivers to premium SUVs."); ?></p>
                <p><?php echo h($settings['about_p3_intro'] ?? "We help you compare specs, schedule viewings across our branches, and figure out the best way to get behind the wheel."); ?></p>
                <p><?php echo h($settings['about_p4'] ?? "Transparency is the point: clear details, practical guidance, and no pressure while you decide."); ?></p>
            </div>
            <aside class="about-note">
                <span>Built around inspection, viewing, and practical buying support.</span>
            </aside>
        </section>

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
