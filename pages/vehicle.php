<?php
/**
 * Vehicle Detail Page
 * Shows a single inventory vehicle by numeric ID.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/analytics.php';
require_once __DIR__ . '/../include/finance.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    header('Location: ' . url('inventory'));
    exit;
}

$stmt = $db->prepare(
    'SELECT v.*, b.name AS brand_name, br.name AS branch_name FROM vehicles v
     JOIN brands b ON v.brand_id = b.id
     LEFT JOIN branches br ON v.branch_id = br.id
     WHERE v.id = ? AND v.is_inventory = 1 AND v.status = "available"'
);
$stmt->execute([$id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

logActivity('view', $vehicle['id']);

$current_page = 'inventory';
$img = $vehicle['image_url'] ?? '';
$encoded_img = $img !== '' ? implode('/', array_map('rawurlencode', explode('/', $img))) : '';
$brand_query = rawurlencode($vehicle['brand_name']);
$pageTitle = h($vehicle['brand_name'] . ' ' . $vehicle['model']);
$pageDescription = $vehicle['description'];
$default_branch = $vehicle['branch_name'] ?: 'Nairobi Showroom';
$inquire_subject = rawurlencode(
    'Inquiry: ' . $vehicle['brand_name'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'
);
$appointment_subject = rawurlencode(
    'Appointment: ' . $vehicle['brand_name'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'
);
$appointment_url = url(
    'contact?subject=' . $appointment_subject .
    '&type=appointment&vehicle_id=' . (int) $vehicle['id'] .
    '&branch_id=' . (int) ($vehicle['branch_id'] ?? 0)
);
$contact_phone = $settings['contact_phone'] ?? '';
$whatsapp_phone = preg_replace('/\D+/', '', $contact_phone);
$whatsapp_text = rawurlencode(
    'Hello, I would like to inquire about the ' . $vehicle['brand_name'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ').'
);
$whatsapp_url = $whatsapp_phone !== '' ? 'https://wa.me/' . $whatsapp_phone . '?text=' . $whatsapp_text : url('contact?subject=' . $inquire_subject);
$financeEstimate = financeEstimateRange($vehicle['price'] ?? 0, $settings ?? []);
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $userStmt = $db->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([(int)$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$availability_subject = rawurlencode(
    'Availability check: ' . $vehicle['brand_name'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'
);
$price_subject = rawurlencode(
    'Best price request: ' . $vehicle['brand_name'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'
);

$similar_stmt = $db->prepare(
    'SELECT v.id, v.model, v.year, v.price, v.image_url, b.name AS brand_name
     FROM vehicles v
     JOIN brands b ON v.brand_id = b.id
     WHERE v.id != ? AND v.brand_id = ? AND v.is_inventory = 1 AND v.status = "available"
     ORDER BY v.is_featured DESC, v.year DESC
     LIMIT 3'
);
$similar_stmt->execute([(int) $vehicle['id'], (int) $vehicle['brand_id']]);
$similar_vehicles = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);

// Gallery images should be explicit data, not every image in the same folder.
// Scanning product/brand folders causes unrelated vehicles to appear on detail pages.
$gallery_images = [];

if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>

<main>
    <header class="site-hero">
        <span class="badge-sale">FOR SALE</span>
        <h1><?php echo h($vehicle['brand_name']); ?> <span><?php echo h($vehicle['model']); ?></span></h1>
        <p><?php echo h($vehicle['year']); ?> · <?php echo h($vehicle['description']); ?></p>
        <div class="hero-rule"></div>
    </header>

    <div class="page-content vehicle-detail">
        <section class="trust-row vehicle-trust-row" aria-label="Dealership services">
            <span>Inspected vehicle</span>
            <span><?php echo h($default_branch); ?></span>
            <span>Trade-in inquiry</span>
            <span>Financing inquiry</span>
        </section>

        <div class="vehicle-detail-grid">
            <section class="vehicle-detail-media">
                <div class="vehicle-badges detail-badges" aria-label="Vehicle status">
                    <span class="vehicle-badge"><?php echo h(ucfirst($vehicle['status'] ?? 'available')); ?></span>
                    <span class="vehicle-badge">Inspection-ready</span>
                    <span class="vehicle-badge finance">Finance available</span>
                    <?php if (!empty($vehicle['is_featured'])): ?>
                        <span class="vehicle-badge featured">Featured</span>
                    <?php endif; ?>
                </div>
                <img src="<?php echo getVehicleImage($img); ?>"
                     alt="<?php echo h($vehicle['model']); ?>"
                     loading="lazy"
                     onerror="this.src='<?php echo url('assets/images/products/toyota-land-cruiser-300.jpg'); ?>'; this.onerror=null;">
                <?php if (!empty($gallery_images)): ?>
                <div class="vehicle-gallery">
                    <?php foreach ($gallery_images as $gi): ?>
                    <img src="<?php echo url($gi); ?>"
                         alt="<?php echo h($vehicle['model']); ?> gallery"
                         loading="lazy"
                         onerror="this.style.display='none';">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <section class="vehicle-request-grid vehicle-request-grid-compact" aria-label="Vehicle buying requests">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <article class="profile-panel">
                            <p class="profile-kicker">Trade-in</p>
                            <h2>Value your current car</h2>
                            <form method="POST" action="<?php echo url('api/customer-journey'); ?>" data-ajax-form data-required-fields="vehicle_make,vehicle_model,customer_name,customer_email" data-submit-label="Send trade-in">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="trade_in">
                                <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicle['id']; ?>">
                                <input type="hidden" name="customer_name" value="<?php echo h($currentUser['name'] ?? ($_SESSION['user_name'] ?? 'Customer')); ?>">
                                <input type="hidden" name="customer_email" value="<?php echo h($currentUser['email'] ?? ''); ?>">
                                <div class="form-row">
                                    <div class="form-group"><label for="vehicle_trade_make">Make</label><input id="vehicle_trade_make" name="vehicle_make" required></div>
                                    <div class="form-group"><label for="vehicle_trade_model">Model</label><input id="vehicle_trade_model" name="vehicle_model" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><label for="vehicle_trade_year">Year</label><input id="vehicle_trade_year" name="vehicle_year"></div>
                                    <div class="form-group"><label for="vehicle_trade_value">Expected value</label><input id="vehicle_trade_value" name="expected_value" type="number" min="0" step="50000"></div>
                                </div>
                                <div class="form-response" aria-live="polite"></div>
                                <button type="submit" class="btn-submit btn-outline">Send trade-in</button>
                            </form>
                        </article>

                        <article class="profile-panel">
                            <p class="profile-kicker">Finance</p>
                            <h2>Request finance pre-check</h2>
                            <form method="POST" action="<?php echo url('api/customer-journey'); ?>" data-ajax-form data-required-fields="customer_name,customer_email" data-submit-label="Send finance request">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="finance">
                                <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicle['id']; ?>">
                                <input type="hidden" name="customer_name" value="<?php echo h($currentUser['name'] ?? ($_SESSION['user_name'] ?? 'Customer')); ?>">
                                <input type="hidden" name="customer_email" value="<?php echo h($currentUser['email'] ?? ''); ?>">
                                <div class="form-row">
                                    <div class="form-group"><label for="vehicle_finance_deposit">Deposit</label><input id="vehicle_finance_deposit" name="deposit_amount" type="number" min="0" step="50000"></div>
                                    <div class="form-group"><label for="vehicle_finance_term">Term</label><input id="vehicle_finance_term" name="term_months" type="number" min="1" max="120"></div>
                                </div>
                                <div class="form-group"><label for="vehicle_finance_budget">Monthly budget</label><input id="vehicle_finance_budget" name="monthly_budget" type="number" min="0" step="10000"></div>
                                <div class="form-response" aria-live="polite"></div>
                                <button type="submit" class="btn-submit btn-outline">Send finance request</button>
                            </form>
                        </article>
                    <?php else: ?>
                        <article class="profile-panel profile-panel-wide">
                            <p class="profile-kicker">Account tools</p>
                            <h2>Save, compare, trade-in, and finance tools require login</h2>
                            <p class="profile-copy">You can still inquire or book a viewing without an account. Log in to keep your shortlist and track requests.</p>
                            <a class="btn" href="<?php echo url('login'); ?>">Log in</a>
                            <a class="btn-outline" href="<?php echo url('register'); ?>">Create account</a>
                        </article>
                    <?php endif; ?>
                </section>
            </section>

            <section class="vehicle-detail-specs">
                <p class="vehicle-detail-kicker"><?php echo h($vehicle['brand_name']); ?> inventory</p>
                <h2 class="section-title"><span>Specifications</span><div class="title-line"></div></h2>
                <dl class="spec-list">
                    <dt>Brand</dt>
                    <dd><?php echo h($vehicle['brand_name']); ?></dd>
                    <dt>Model</dt>
                    <dd><?php echo h($vehicle['model']); ?></dd>
                    <dt>Year</dt>
                    <dd><?php echo h($vehicle['year']); ?></dd>
                    <?php if (!empty($vehicle['price'])): ?>
                    <dt>Price</dt>
                    <dd>KES <?php echo number_format((float) $vehicle['price']); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($vehicle['engine'])): ?>
                    <dt>Engine</dt>
                    <dd><?php echo h($vehicle['engine']); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($vehicle['power'])): ?>
                    <dt>Power</dt>
                    <dd><?php echo h($vehicle['power']); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($vehicle['drive'])): ?>
                    <dt>Drive</dt>
                    <dd><?php echo h($vehicle['drive']); ?></dd>
                    <?php endif; ?>
                    <dt>Branch</dt>
                    <dd><?php echo h($default_branch); ?></dd>
                </dl>

                <aside class="vehicle-action-panel" aria-label="Vehicle actions">
                    <?php if ($financeEstimate['available']): ?>
                        <div class="vehicle-finance-lead">
                            <span>Estimated finance</span>
                            <strong><?php echo h($financeEstimate['label']); ?></strong>
                            <small><?php echo h($financeEstimate['disclaimer']); ?></small>
                        </div>
                        <p class="vehicle-cash-price"><?php echo h(formatVehicleCashPrice($vehicle['price'])); ?> cash price</p>
                    <?php else: ?>
                        <div class="vehicle-finance-lead">
                            <span>Pricing</span>
                            <strong>Check availability</strong>
                            <small>Ask for current pricing and branch availability.</small>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo url('contact?subject=' . $availability_subject); ?>" class="btn">Check Availability</a>
                    <a href="<?php echo url('contact?subject=' . $price_subject); ?>" class="btn-outline">Unlock Best Price</a>
                    <a href="<?php echo $appointment_url; ?>" class="btn">Book Appointment</a>
                    <a href="<?php echo h($whatsapp_url); ?>" class="btn-outline">WhatsApp</a>
                    <button type="button" class="btn-outline compact-action" data-save-id="<?php echo (int) $vehicle['id']; ?>">Save</button>
                    <a href="<?php echo url('inventory?brand=' . $brand_query); ?>" class="text-action">More from <?php echo h($vehicle['brand_name']); ?></a>
                    <a href="<?php echo url('inventory'); ?>" class="text-action">Back to inventory</a>
                </aside>
            </section>
        </div>

        <?php if (!empty($similar_vehicles)): ?>
            <section class="similar-cars" aria-label="Similar vehicles">
                <h2 class="section-title"><span>Similar Cars</span><div class="title-line"></div></h2>
                <div class="similar-grid">
                    <?php foreach ($similar_vehicles as $similar): ?>
                        <article class="similar-card">
                            <img src="<?php echo getVehicleImage($similar['image_url'] ?? ''); ?>"
                                 alt="<?php echo h($similar['brand_name'] . ' ' . $similar['model']); ?>"
                                 loading="lazy"
                                 onerror="this.src='<?php echo url('assets/images/products/toyota-land-cruiser-300.jpg'); ?>'; this.onerror=null;">
                            <div>
                                <span><?php echo h($similar['year']); ?></span>
                                <h3><?php echo h($similar['brand_name'] . ' ' . $similar['model']); ?></h3>
                                <?php if (!empty($similar['price'])): ?>
                                    <p>KES <?php echo number_format((float) $similar['price']); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo url('vehicle/' . (int) $similar['id']); ?>">View details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
    <nav class="mobile-vehicle-actions" aria-label="Vehicle quick actions">
        <a href="<?php echo url('contact?subject=' . $availability_subject); ?>">Check</a>
        <a href="<?php echo $appointment_url; ?>">Book</a>
        <button type="button" data-save-id="<?php echo (int) $vehicle['id']; ?>">Save</button>
    </nav>
    </main>

    <?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
