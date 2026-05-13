<?php
/**
 * Legacy Vehicle Template Page
 * Displays timeline and specs for a single legacy vehicle.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db.php';

$model_name = $_GET['model'] ?? '';
if (!$model_name) {
    header("Location: /inventory");
    exit;
}

$stmt = $db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.model = ? AND v.is_legacy = 1 LIMIT 1");
$stmt->execute([$model_name]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

require_once __DIR__ . '/../include/analytics.php';
logActivity('view', $vehicle['id']);

$stmt = $db->prepare("SELECT * FROM legacy_details WHERE vehicle_id = ? ORDER BY id ASC");
$stmt->execute([$vehicle['id']]);
$generations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = htmlspecialchars($vehicle['brand_name'] . ' ' . $vehicle['model']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> — <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($vehicle['description']); ?>">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<?php include __DIR__ . '/../include/nav.php'; ?>

<div class="site-hero">
    <h1><?php echo htmlspecialchars($vehicle['brand_name']); ?> <span style="text-transform:uppercase;"><?php echo htmlspecialchars($vehicle['model']); ?></span></h1>
    <p><?php echo htmlspecialchars($vehicle['description']); ?></p>
</div>

<div class="page-content">
    <div class="page-tabs">
        <a href="#" class="active" onclick="showTab('tab-history',this);return false;">History</a>
        <a href="#" onclick="showTab('tab-overview',this);return false;">Specs Overview</a>
    </div>

    <div id="tab-history" class="tab-panel">
        <div class="legacy-intro">
            <p><?php echo nl2br(htmlspecialchars($vehicle['description'])); ?></p>
        </div>

        <?php if (empty($generations)): ?>
            <p style="color:var(--muted);margin:2rem 0;">No generation data available for this vehicle yet.</p>
        <?php else: ?>
            <?php foreach ($generations as $gen): ?>
            <div class="gen-block">
                <div class="gen-block-header">
                    <h2><?php echo htmlspecialchars($gen['generation_name']); ?></h2>
                    <span class="gen-year"><?php echo htmlspecialchars($gen['years']); ?></span>
                </div>
                <div class="gen-media">
                    <img src="/<?php echo htmlspecialchars($gen['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($gen['generation_name']); ?>"
                         loading="lazy"
                         onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
                </div>
                <p><?php echo htmlspecialchars($gen['history_text']); ?></p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="tab-overview" class="tab-panel" style="display:none">
        <?php if (empty($generations)): ?>
            <p style="color:var(--muted);margin:2rem 0;">No generation data available for this vehicle yet.</p>
        <?php else: ?>
            <div class="overview-grid">
                <?php foreach ($generations as $gen): ?>
                <div class="overview-card">
                    <img src="/<?php echo htmlspecialchars($gen['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($gen['generation_name']); ?>"
                         loading="lazy"
                         onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
                    <div class="ov-body">
                        <h3><?php echo htmlspecialchars($gen['generation_name']); ?></h3>
                        <p><span>Years:</span> <?php echo htmlspecialchars($gen['years']); ?></p>
                        <p><span>History:</span> <?php echo htmlspecialchars(mb_substr($gen['history_text'], 0, 80)); ?>...</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Heritage Conversion Section ──────────────────── -->
    <div style="margin-top: 5rem; padding: 3rem; background: var(--carbon2); border: 1px solid var(--border); border-radius: var(--r-lg); text-align: center;">
        <h2 style="font-family: var(--font-disp); font-size: 2.5rem; letter-spacing: 4px; margin-bottom: 1rem;">Love the <span style="color:var(--gold-light);">Heritage?</span></h2>
        <p style="color: var(--muted); margin-bottom: 2rem; max-width: 600px; margin-inline: auto;">
            The legacy of the <?php echo htmlspecialchars($vehicle['model']); ?> lives on in our modern <?php echo htmlspecialchars($vehicle['brand_name']); ?> inventory. Explore the latest models sourced for the Kenyan market.
        </p>
        <a href="/inventory?brand=<?php echo urlencode($vehicle['brand_name']); ?>" class="btn-outline" style="font-size: 1.1rem; padding: 0.8rem 2rem;">
            View Modern <?php echo htmlspecialchars($vehicle['brand_name']); ?> Inventory
        </a>
    </div>
    </div>


<?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
