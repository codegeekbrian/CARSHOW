<?php
/**
 * Contact Us Page
 * Displays contact form and business information.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/csrf.php';
require_once __DIR__ . '/../include/settings.php';

$current_page = 'contact';
$pageTitle = "Inquire";
$pageDescription = "Inquire with " . SITE_NAME . " about vehicles, pricing, appointments, or availability.";
$subject_prefill = trim($_GET['subject'] ?? '');
$inquiry_type = trim($_GET['type'] ?? 'general') === 'appointment' ? 'appointment' : 'general';
$selected_vehicle_id = max(0, (int)($_GET['vehicle_id'] ?? 0));
$selected_branch_id = max(0, (int)($_GET['branch_id'] ?? 0));
$branches = [];
$selected_vehicle = null;

try {
    require_once __DIR__ . '/../include/db.php';
    $settings = loadSettings($db);
    $branches = $db->query('SELECT id, name, location FROM branches WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

    if ($selected_vehicle_id > 0) {
        $stmt = $db->prepare(
            'SELECT v.id, v.model, v.year, v.branch_id, b.name AS brand_name
             FROM vehicles v
             JOIN brands b ON v.brand_id = b.id
             WHERE v.id = ? AND v.is_inventory = 1
             LIMIT 1'
        );
        $stmt->execute([$selected_vehicle_id]);
        $selected_vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($selected_vehicle && $selected_branch_id === 0) {
            $selected_branch_id = (int)($selected_vehicle['branch_id'] ?? 0);
        }
    }
} catch (Exception $e) {
    $settings = [];
    // Page-level defaults keep contact usable if settings are unavailable.
}

if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
<main>
    <div class="site-hero">
        <h1>MAKE AN <span>INQUIRY</span></h1>
        <p><?php echo h($settings['contact_tagline'] ?? "Ask about a vehicle, appointment, price, trade-in, or financing option. We'll respond promptly."); ?></p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content">
        <div class="contact-layout single">
        <div class="contact-wrap">
            <h2>Send an Inquiry</h2>
            <form action="<?php echo url('api/contact'); ?>" method="POST" data-ajax-form data-required-fields="name,email,message" data-submit-label="Send Message">
                <?php echo csrfField(); ?>
                <input type="hidden" name="inquiry_type" value="<?php echo h($inquiry_type); ?>">
                <input type="hidden" name="vehicle_id" value="<?php echo (int)$selected_vehicle_id; ?>">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Your name" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Vehicle inquiry, pricing, etc." autocomplete="off"
                           value="<?php echo htmlspecialchars($subject_prefill); ?>">
                </div>
                <?php if ($inquiry_type === 'appointment'): ?>
                    <?php if ($selected_vehicle): ?>
                        <div class="form-note">
                            Viewing request for <?php echo h($selected_vehicle['brand_name'] . ' ' . $selected_vehicle['model'] . ' (' . $selected_vehicle['year'] . ')'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select id="branch_id" name="branch_id">
                                <option value="">Any Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo (int)$branch['id']; ?>" <?php echo $selected_branch_id === (int)$branch['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" placeholder="+254 700 000 000">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_date">Preferred Date</label>
                            <input type="date" id="preferred_date" name="preferred_date">
                        </div>
                        <div class="form-group">
                            <label for="preferred_time">Preferred Time</label>
                            <input type="text" id="preferred_time" name="preferred_time" placeholder="Morning, afternoon, or exact time">
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Tell us what you're looking for..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
                <div class="form-response" aria-live="polite"></div>
            </form>
        </div>
        </div>

        <div class="contact-info-strip">
            <div class="contact-info-item">
                <div class="ci-icon ci-location" aria-hidden="true"></div>
                <div class="ci-label">Location</div>
                <div class="ci-val"><?php echo h($settings['contact_address'] ?? SITE_LOCATION); ?></div>
            </div>
            <div class="contact-info-item">
                <div class="ci-icon ci-email" aria-hidden="true"></div>
                <div class="ci-label">Email</div>
                <div class="ci-val"><?php echo h($settings['contact_email'] ?? CONTACT_EMAIL); ?></div>
            </div>
            <div class="contact-info-item">
                <div class="ci-icon ci-phone" aria-hidden="true"></div>
                <div class="ci-label">Phone</div>
                <div class="ci-val"><?php echo h($settings['contact_phone'] ?? ''); ?></div>
            </div>
            <div class="contact-info-item">
                <div class="ci-icon ci-hours" aria-hidden="true"></div>
                <div class="ci-label">Hours</div>
                <div class="ci-val"><?php echo h($settings['business_hours'] ?? 'Mon-Sat: 8am - 6pm'); ?></div>
            </div>
        </div>
    </div>
</main>

<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
