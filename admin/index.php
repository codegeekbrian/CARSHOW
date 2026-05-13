<?php
require_once __DIR__ . '/admin_db.php';
require_auth();

$stmt = $db->prepare("SELECT COUNT(*) as total_cars FROM vehicles");
$stmt->execute();
$total_cars = $stmt->fetch(PDO::FETCH_ASSOC)['total_cars'];

$stmt = $db->prepare("SELECT COUNT(*) as total_brands FROM brands");
$stmt->execute();
$total_brands = $stmt->fetch(PDO::FETCH_ASSOC)['total_brands'];

$stmt = $db->prepare("SELECT COUNT(*) as total_legacy FROM legacy_details");
$stmt->execute();
$total_legacy = $stmt->fetch(PDO::FETCH_ASSOC)['total_legacy'];

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/include/header.php';
?>

<div class="dashboard-container">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Vehicles</h3>
            <div class="count"><?php echo $total_cars; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Brands</h3>
            <div class="count"><?php echo $total_brands; ?></div>
        </div>
        <div class="stat-card">
            <h3>Legacy Details</h3>
            <div class="count"><?php echo $total_legacy; ?></div>
        </div>
    </div>

    <div class="admin-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h2 class="section-title">Quick Actions</h2>
            <p>Welcome to the CARSHOW Admin Dashboard.</p>

            <div style="margin-top: 2rem;">
                <a href="cars.php" class="btn" style="margin-bottom: 0.5rem; display: block; text-align: center;">Manage Cars</a>
                <a href="brands.php" class="btn btn-success" style="margin-bottom: 0.5rem; display: block; text-align: center;">Manage Brands</a>
                <a href="legacy.php" class="btn" style="background: #6f42c1; margin-bottom: 0.5rem; display: block; text-align: center;">Manage Legacy</a>
                <a href="messages.php" class="btn btn-danger" style="display: block; text-align: center;">View Messages</a>
            </div>
        </div>

        <div>
            <h2 class="section-title">Top Viewed Models</h2>
            <?php if (empty($top_vehicles)): ?>
                <p>No view data yet.</p>
            <?php else: ?>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_vehicles as $v): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['brand_name'] . ' ' . $v['model']); ?></td>
                            <td style="text-align: right;"><?php echo $v['views']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>