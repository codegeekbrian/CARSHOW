<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/session_helpers.php';
require_once __DIR__ . '/../include/customer_journey.php';
require_once __DIR__ . '/../include/finance.php';
enforce_user_session_timeout();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('login'));
    exit;
}

$vehicles = getUserCompareVehicles($db, (int)$_SESSION['user_id']);
$current_page = 'compare';
$pageTitle = 'Compare Vehicles';
if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>

<main>
    <header class="site-hero">
        <h1>Compare <span>Vehicles</span></h1>
        <p>Review your shortlist side by side before booking a viewing or asking for finance.</p>
    </header>

    <div class="page-content">
        <?php if (empty($vehicles)): ?>
            <section class="empty-state">
                <h3>No vehicles selected</h3>
                <p>Add up to three cars from inventory to compare them here.</p>
                <a class="btn" href="<?php echo url('inventory'); ?>">Browse inventory</a>
            </section>
        <?php else: ?>
            <section class="compare-page-grid" aria-label="Vehicle comparison">
                <?php foreach ($vehicles as $vehicle): ?>
                    <?php $finance = financeEstimateRange($vehicle['price'] ?? 0, $settings ?? []); ?>
                    <article class="compare-page-card">
                        <img src="<?php echo getVehicleImage($vehicle['image_url'] ?? ''); ?>" alt="<?php echo h($vehicle['brand_name'] . ' ' . $vehicle['model']); ?>" loading="lazy">
                        <p class="vehicle-card-brand"><?php echo h($vehicle['brand_name']); ?></p>
                        <h2><?php echo h($vehicle['model']); ?></h2>
                        <dl class="spec-list">
                            <dt>Year</dt><dd><?php echo h($vehicle['year']); ?></dd>
                            <dt>Body</dt><dd><?php echo h($vehicle['vehicle_type'] ?: '-'); ?></dd>
                            <dt>Fuel</dt><dd><?php echo h($vehicle['fuel_type'] ?: '-'); ?></dd>
                            <dt>Transmission</dt><dd><?php echo h($vehicle['transmission'] ?: '-'); ?></dd>
                            <dt>Branch</dt><dd><?php echo h($vehicle['branch_name'] ?: 'Nairobi Showroom'); ?></dd>
                            <dt>Finance</dt><dd><?php echo h($finance['available'] ? $finance['label'] : 'Check availability'); ?></dd>
                        </dl>
                        <div class="vehicle-card-actions">
                            <a class="btn" href="<?php echo url('vehicle/' . (int)$vehicle['id']); ?>">View</a>
                            <a class="btn-outline" href="<?php echo url('contact?type=appointment&vehicle_id=' . (int)$vehicle['id']); ?>">Book</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
