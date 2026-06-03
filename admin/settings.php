<?php
require_once __DIR__ . '/admin_db.php';
require_permission('settings.manage');
require_csrf();

$message = '';
$settings = [];

$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $keys = [
            'hero_headline', 'hero_subtitle', 'hero_badge',
            'about_tagline', 'about_p1', 'about_p2', 'about_p3_intro', 'about_p4', 'site_motto',
            'stat_label_heritage', 'stat_label_countries', 'stat_label_brands', 'stat_label_showcase',
            'contact_tagline', 'inventory_tagline',
            'meta_description_default',
            'testimonials_title', 'testimonial_fallback_quote', 'testimonial_fallback_author',
            'heritage_models', 'source_countries', 'brands_count', 'online_showcase',
            'business_hours', 'contact_phone', 'contact_email', 'contact_address',
            'finance_deposit_percent', 'finance_rate_percent', 'finance_min_term_months',
            'finance_max_term_months', 'finance_disclaimer'
        ];

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        $success = true;
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                if (!$stmt->execute([$key, $_POST[$key]])) {
                    $success = false;
                }
            }
        }

        if ($success) {
            recordAdminAuditLog($db, 'settings.updated', 'settings', null, 'Updated site settings');
            $message = "Settings updated.";
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } else {
            $message = "Error updating settings.";
        }
    }
}

$pageTitle = 'Site Settings';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="settings-workbench-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update">

            <div class="settings-workbench-head">
                <div>
                    <p class="admin-kicker">Configuration</p>
                    <h2 class="section-title">Site settings</h2>
                </div>
                <nav class="settings-nav" aria-label="Settings sections">
                    <a href="#settings-brand">Brand</a>
                    <a href="#settings-pages">Pages</a>
                    <a href="#settings-finance">Finance</a>
                    <a href="#settings-contact">Contact</a>
                    <a href="#settings-social">Testimonials</a>
                </nav>
            </div>

            <div class="settings-card" id="settings-brand">
                <h3>Hero Section</h3>
                <div class="form-group">
                    <label for="hero_badge">Hero Badge</label>
                    <input type="text" id="hero_badge" name="hero_badge"
                           value="<?php echo h($settings['hero_badge'] ?? 'FOR SALE'); ?>">
                </div>
                <div class="form-group">
                    <label for="hero_headline">Hero Headline</label>
                    <textarea id="hero_headline" name="hero_headline" rows="2"><?php echo h($settings['hero_headline'] ?? "OWN IT.<br><span>DRIVE IT.</span>"); ?></textarea>
                    <small>HTML allowed (e.g. &lt;span&gt; for styling)</small>
                </div>
                <div class="form-group">
                    <label for="hero_subtitle">Hero Subtitle</label>
                    <textarea id="hero_subtitle" name="hero_subtitle" rows="2"><?php echo h($settings['hero_subtitle'] ?? "Clean stock, clear pricing, and branch viewings when you are ready."); ?></textarea>
                </div>
            </div>

            <div class="settings-card" id="settings-pages">
                <h3>About Page</h3>
                <div class="form-group">
                    <label for="about_tagline">About Hero Tagline</label>
                    <input type="text" id="about_tagline" name="about_tagline"
                           value="<?php echo h($settings['about_tagline'] ?? "Kenya's trusted home for quality locally available vehicles and automotive passion."); ?>">
                </div>
                <div class="form-group">
                    <label for="about_p1">About Paragraph 1</label>
                    <textarea id="about_p1" name="about_p1" rows="3"><?php echo h($settings['about_p1'] ?? SITE_NAME . " sells new and pre-owned vehicles in Kenya. We inspect stock, list the details clearly, and help customers arrange branch viewings."); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="about_p2">About Paragraph 2</label>
                    <textarea id="about_p2" name="about_p2" rows="3"><?php echo h($settings['about_p2'] ?? "Vehicles in our dealership are sourced locally and from reputable international markets — ensuring variety to meet every taste and budget in the Kenyan market."); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="about_p3_intro">About Paragraph 3 (Intro)</label>
                    <textarea id="about_p3_intro" name="about_p3_intro" rows="3"><?php echo h($settings['about_p3_intro'] ?? "Beyond our inventory, we help customers compare vehicles, arrange viewing appointments, and understand financing or trade-in options before visiting a branch."); ?></textarea>
                    <small>Shown as supporting copy on the about page.</small>
                </div>
                <div class="form-group">
                    <label for="about_p4">About Paragraph 4 (Closing)</label>
                    <textarea id="about_p4" name="about_p4" rows="2"><?php echo h($settings['about_p4'] ?? "Whether you are a first-time buyer or a seasoned collector, our team is here to make the process easy, transparent, and safe. <strong>Own it. Drive it. Love it.</strong>"); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="site_motto">Site Motto</label>
                    <input type="text" id="site_motto" name="site_motto"
                           value="<?php echo h($settings['site_motto'] ?? 'Own it. Drive it. Love it.'); ?>">
                </div>
            </div>

            <div class="settings-card" id="settings-stat-labels">
                <h3>Stats Labels</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stat_label_heritage">Showroom Models Label</label>
                        <input type="text" id="stat_label_heritage" name="stat_label_heritage"
                               value="<?php echo h($settings['stat_label_heritage'] ?? 'Vehicles Ready to View'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="stat_label_countries">Source Countries Label</label>
                        <input type="text" id="stat_label_countries" name="stat_label_countries"
                               value="<?php echo h($settings['stat_label_countries'] ?? 'Source Countries'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stat_label_brands">Brands Label</label>
                        <input type="text" id="stat_label_brands" name="stat_label_brands"
                               value="<?php echo h($settings['stat_label_brands'] ?? 'Brands in Inventory'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="stat_label_showcase">Online Showcase Label</label>
                        <input type="text" id="stat_label_showcase" name="stat_label_showcase"
                               value="<?php echo h($settings['stat_label_showcase'] ?? 'Online Showcase'); ?>">
                    </div>
                </div>
            </div>

            <div class="settings-card" id="settings-page-descriptions">
                <h3>Page Descriptions</h3>
                <div class="form-group">
                    <label for="contact_tagline">Contact Page Tagline</label>
                    <textarea id="contact_tagline" name="contact_tagline" rows="2"><?php echo h($settings['contact_tagline'] ?? "We'd love to help you find your perfect vehicle. Send us a message and we'll respond promptly."); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="inventory_tagline">Inventory Page Tagline</label>
                    <textarea id="inventory_tagline" name="inventory_tagline" rows="2"><?php echo h($settings['inventory_tagline'] ?? "Browse available stock, compare specs, and book a branch viewing."); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="meta_description_default">Default Meta Description</label>
                    <textarea id="meta_description_default" name="meta_description_default" rows="2"><?php echo h($settings['meta_description_default'] ?? "Browse available vehicles, book branch viewings, and send trade-in or finance requests."); ?></textarea>
                    <small>Used as fallback when a page doesn't set its own description.</small>
                </div>
            </div>

            <div class="settings-card" id="settings-finance">
                <h3>Finance Estimates</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="finance_deposit_percent">Deposit Percentage</label>
                        <input type="number" id="finance_deposit_percent" name="finance_deposit_percent"
                               value="<?php echo h($settings['finance_deposit_percent'] ?? '20'); ?>"
                               min="0" max="95" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="finance_rate_percent">Annual Rate Percentage</label>
                        <input type="number" id="finance_rate_percent" name="finance_rate_percent"
                               value="<?php echo h($settings['finance_rate_percent'] ?? '16'); ?>"
                               min="0" max="60" step="0.1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="finance_min_term_months">Shortest Term Months</label>
                        <input type="number" id="finance_min_term_months" name="finance_min_term_months"
                               value="<?php echo h($settings['finance_min_term_months'] ?? '36'); ?>"
                               min="1" max="120" step="1">
                    </div>
                    <div class="form-group">
                        <label for="finance_max_term_months">Longest Term Months</label>
                        <input type="number" id="finance_max_term_months" name="finance_max_term_months"
                               value="<?php echo h($settings['finance_max_term_months'] ?? '60'); ?>"
                               min="1" max="120" step="1">
                    </div>
                </div>
                <div class="form-group">
                    <label for="finance_disclaimer">Finance Disclaimer</label>
                    <textarea id="finance_disclaimer" name="finance_disclaimer" rows="2"><?php echo h($settings['finance_disclaimer'] ?? 'Finance estimates are indicative and subject to approval.'); ?></textarea>
                    <small>Shown beside estimated monthly payments.</small>
                </div>
            </div>

            <div class="settings-card" id="settings-social">
                <h3>Footer</h3>
                <div class="form-group">
                    <label for="testimonials_title">Testimonials Section Title</label>
                    <input type="text" id="testimonials_title" name="testimonials_title"
                           value="<?php echo h($settings['testimonials_title'] ?? 'What Our <span>Clients</span> Say'); ?>">
                    <small>HTML allowed (e.g. &lt;span&gt; for styling)</small>
                </div>
                <div class="form-group">
                    <label for="testimonial_fallback_quote">Fallback Testimonial Quote</label>
                    <textarea id="testimonial_fallback_quote" name="testimonial_fallback_quote" rows="2"><?php echo h($settings['testimonial_fallback_quote'] ?? 'Great selection of vehicles and excellent customer service.'); ?></textarea>
                    <small>Shown when no testimonials exist in the database.</small>
                </div>
                <div class="form-group">
                    <label for="testimonial_fallback_author">Fallback Testimonial Author</label>
                    <input type="text" id="testimonial_fallback_author" name="testimonial_fallback_author"
                           value="<?php echo h($settings['testimonial_fallback_author'] ?? 'Client, Nairobi'); ?>">
                </div>
            </div>

            <h2 class="section-title section-title-spaced">Stats &amp; Contact Info</h2>

            <div class="settings-card" id="settings-statistics">
                <h3>Statistics</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="heritage_models">Showroom Models Count</label>
                        <input type="text" id="heritage_models" name="heritage_models"
                               value="<?php echo h($settings['heritage_models'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="source_countries">Source Countries</label>
                        <input type="text" id="source_countries" name="source_countries"
                               value="<?php echo h($settings['source_countries'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="brands_count">Brands Count</label>
                        <input type="text" id="brands_count" name="brands_count"
                               value="<?php echo h($settings['brands_count'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="online_showcase">Online Showcase</label>
                        <input type="text" id="online_showcase" name="online_showcase"
                               value="<?php echo h($settings['online_showcase'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="settings-card" id="settings-contact">
                <h3>Contact Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_phone">Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone"
                               value="<?php echo h($settings['contact_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_email">Email</label>
                        <input type="email" id="contact_email" name="contact_email"
                               value="<?php echo h($settings['contact_email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="business_hours">Business Hours</label>
                    <input type="text" id="business_hours" name="business_hours"
                           value="<?php echo h($settings['business_hours'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="contact_address">Address</label>
                    <input type="text" id="contact_address" name="contact_address"
                           value="<?php echo h($settings['contact_address'] ?? ''); ?>">
                </div>
            </div>

            <div class="settings-save-bar">
                <span>Save changes across all visible sections.</span>
                <button type="submit" class="btn btn-success settings-save">Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>
