<?php
// This is a template file. In a real app, we'd use a router.
// For now, we'll use it to dynamically generate Legacy pages with caching.

require_once '../include/cache.php';

$model_name = $_GET['model'] ?? '';
if (!$model_name) {
    header("Location: /pages/inventory.php");
    exit;
}

// Try to get from cache first
$cacheKey = 'vehicle_' . $model_name;
$cachedData = getCache($cacheKey);

if ($cachedData !== false) {
    // Use cached data
    $vehicle = $cachedData['vehicle'];
    $history = $cachedData['history'];
    $current_page = $cachedData['current_page'];
} else {
    // Load from database
    require_once '../include/db.php';
    
    $stmt = $db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.model = ? AND v.is_heritage = 1 LIMIT 1");
    $stmt->execute([$model_name]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        die("Vehicle not found.");
    }
    
    $histStmt = $db->prepare("SELECT * FROM heritage_details WHERE vehicle_id = ? ORDER BY id ASC");
    $histStmt->execute([$vehicle['id']]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current_page = strtolower(str_replace(' ', '_', $model_name));
    
    // Cache the data
    $cacheData = [
        'vehicle' => $vehicle,
        'history' => $history,
        'current_page' => $current_page
    ];
    setCache($cacheKey, $cacheData);
}

// Fallback image helper
function getImageUrl($url, $type = 'heritage') {
    if (!empty($url) && file_exists($url)) {
        return $url;
    }
    return 'assets/images/' . $type . '/placeholder.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($vehicle['brand_name']) ?> <?= htmlspecialchars($vehicle['model']) ?> Heritage — Own It &amp; Drive It</title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body data-page-model="<?= htmlspecialchars($model_name) ?>">
<?php include "../include/nav.php"; ?>
<div class="site-hero">
  <h1><?= htmlspecialchars($vehicle['brand_name']) ?> <span style="text-transform: uppercase;"><?= htmlspecialchars($vehicle['model']) ?></span></h1>
  <p><?= htmlspecialchars($vehicle['description']) ?></p>
</div>
<div class="page-content">
  <div class="page-tabs">
    <a href="#" class="active" onclick="showTab('tab-history',this);return false;">History</a>
    <a href="#" onclick="showTab('tab-overview',this);return false;">Specs Overview</a>
  </div>

  <div id="tab-history" class="tab-panel">
    <div class="heritage-intro">
      <?php echo nl2br(htmlspecialchars($vehicle['description'])); ?>
    </div>

    <?php foreach ($history as $gen): ?>
    <div class="gen-block">
      <div class="gen-block-header">
        <h2><?= htmlspecialchars($gen['generation_name']) ?></h2>
        <span class="gen-year"><?= htmlspecialchars($gen['years']) ?></span>
      </div>
      <div class="gen-media">
        <img src="../<?= htmlspecialchars($gen['image_url']) ?>" alt="<?= htmlspecialchars($gen['generation_name']) ?>" loading="lazy">
      </div>
      <p><?= htmlspecialchars($gen['history_text']) ?></p>
    </div>
    <?php endforeach; ?>
  </div><!-- /tab-history -->

  <div id="tab-overview" class="tab-panel" style="display:none">
    <div class="overview-grid">
      <?php foreach ($history as $gen): ?>
      <div class="overview-card">
        <img src="../<?= htmlspecialchars($gen['image_url']) ?>" alt="<?= htmlspecialchars($gen['generation_name']) ?>" loading="lazy">
        <div class="ov-body">
          <h3><?= htmlspecialchars($gen['generation_name']) ?></h3>
          <p><span>Years:</span> <?= htmlspecialchars($gen['years']) ?></p>
          <p><span>History:</span> <?= substr(htmlspecialchars($gen['history_text']), 0, 60) ?>...</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div><!-- /tab-overview -->

</div><!-- /page-content -->
<?php include "../include/footer.php"; ?>
</body>
</html>
