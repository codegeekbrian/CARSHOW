<?php
/**
 * Vehicle Inventory Page
 * Displays all vehicles with filtering by brand, year, and search.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/analytics.php';

logActivity('view');

$current_page = 'inventory';

$search       = trim($_GET['search'] ?? '');
$min_year     = max(0, intval($_GET['min_year'] ?? 0));
$max_year     = max(0, intval($_GET['max_year'] ?? 0));
$brand_filter = trim($_GET['brand'] ?? '');

$sql    = "SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.is_inventory = 1";
$params = [];

if ($search !== '') {
    $keywords = explode(' ', $search);
    $searchParts = [];
    foreach ($keywords as $word) {
        $word = trim($word);
        if ($word !== '') {
            $searchParts[] = "(v.model LIKE ? OR v.description LIKE ? OR b.name LIKE ?)";
            $params[] = "%$word%";
            $params[] = "%$word%";
            $params[] = "%$word%";
        }
    }
    if (!empty($searchParts)) {
        $sql .= " AND (" . implode(" AND ", $searchParts) . ")";
    }
}

if ($brand_filter !== '') {
    $sql .= " AND b.name = ?";
    $params[] = $brand_filter;
}

if ($min_year > 0) {
    $sql .= " AND v.year >= ?";
    $params[] = $min_year;
}

if ($max_year > 0) {
    $sql .= " AND v.year <= ?";
    $params[] = $max_year;
}

$sql .= " ORDER BY b.name ASC, v.year DESC";

$brandStmt = $db->query("SELECT name FROM brands ORDER BY name");
$allBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);

$hasFilters = $search !== '' || $brand_filter !== '' || $min_year > 0 || $max_year > 0;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory — <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<?php include __DIR__ . '/../include/nav.php'; ?>

<div class="site-hero">
    <h1>Our <span>Inventory</span></h1>
    <p>Browse our carefully sourced selection of quality vehicles from around the world.</p>
</div>

<div class="page-content">
    <div class="filters-bar">
        <form method="GET">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Model, brand, description...">
            </div>

            <div class="filter-group">
                <label for="brand">Brand</label>
                <select id="brand" name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($allBrands as $b): ?>
                        <option value="<?php echo htmlspecialchars($b); ?>"
                            <?php echo $brand_filter === $b ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="min_year">Min Year</label>
                <input type="number" id="min_year" name="min_year"
                       value="<?php echo $min_year > 0 ? $min_year : ''; ?>"
                       placeholder="1990" min="1900" max="2026">
            </div>

            <div class="filter-group">
                <label for="max_year">Max Year</label>
                <input type="number" id="max_year" name="max_year"
                       value="<?php echo $max_year > 0 ? $max_year : ''; ?>"
                       placeholder="2026" min="1900" max="2026">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn">Filter</button>
                <a href="inventory" class="btn btn-danger" style="background:#495057;">Reset</a>
            </div>
        </form>
    </div>

    <?php if ($hasFilters): ?>
        <p class="search-results-count">
            <?php echo count($vehicles); ?> vehicle<?php echo count($vehicles) !== 1 ? 's' : ''; ?> found
        </p>
    <?php endif; ?>

    <?php if (empty($vehicles)): ?>
        <p class="search-results-count">No vehicles found matching your criteria.</p>
    <?php else:
        $currentBrand = '';
        foreach ($vehicles as $v):
            if ($currentBrand !== $v['brand_name']):
                if ($currentBrand !== '') echo '</div></section>';
                $currentBrand = $v['brand_name'];
                $slug = strtolower(str_replace(' ', '-', $currentBrand));
                echo "<section class='brand-section' id='$slug'>
                      <h2>{$currentBrand} Motors</h2>
                      <div class='cards-grid'>";
            endif;
        ?>
            <div class="car-card" data-brand="<?php echo htmlspecialchars($v['brand_name']); ?>">
                <?php
                $imgUrl = $v['image_url'] ?? '';
                ?>
                <img class="card-img"
                     src="/<?php echo htmlspecialchars($imgUrl); ?>"
                     alt="<?php echo htmlspecialchars($v['model']); ?>"
                     loading="lazy"
                     onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($v['model']); ?></h3>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($v['year']); ?></p>
                    <p><strong>Engine:</strong> <?php echo htmlspecialchars($v['engine'] ?? '-'); ?></p>
                    <p><strong>Drive:</strong> <?php echo htmlspecialchars($v['drive'] ?? '-'); ?></p>
                    <?php if (!empty($v['price'])): ?>
                        <p><strong>Price:</strong> $<?php echo number_format((float)$v['price']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($v['description']); ?></p>
                </div>
            </div>
        <?php endforeach;
        echo '</div></section>';
    endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
