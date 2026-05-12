<?php
$current_page = 'inventory';

// Include centralized database connection
require_once '../include/db.php';

// Include image utilities
require_once '../include/image_utils.php';

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$min_year = intval($_GET['min_year'] ?? 0);
$max_year = intval($_GET['max_year'] ?? 0);
$brand_filter = trim($_GET['brand'] ?? '');

// Build query based on filters
$sql = "SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id";
$params = [];

if (!empty($search)) {
    $sql .= " AND (v.model LIKE ? OR v.description LIKE ? OR b.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($brand_filter)) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory — Own It &amp; Drive It</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <style>
    .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 2rem;
      padding: 1rem;
      background: #f8f9fa;
      border-radius: 8px;
    }
    
    .filter-group {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-width: 150px;
    }
    
    .filter-group label {
      font-weight: bold;
      margin-bottom: 0.5rem;
    }
    
    .filter-group input, .filter-group select {
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .filter-actions {
      display: flex;
      align-items: flex-end;
    }
    
    .filter-actions .btn {
      height: calc(100% - 0.5rem);
      padding: 0.5rem 1rem;
    }
    
    .search-results {
      margin-bottom: 1rem;
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php include "../include/nav.php"; ?>
<div class="site-hero">
  <h1>Our <span>Inventory</span></h1>
  <p>Browse our carefully sourced selection of quality vehicles from around the world.</p>
</div>
<div class="page-content">
  <!-- Advanced Filters -->
  <div class="filters-bar">
    <form method="GET" id="inventoryFilters">
      <div style="display: flex; flex-wrap: wrap; gap: 1rem; width: 100%;">
        <div class="filter-group">
          <label for="search">Search</label>
          <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Model, brand, description...">
        </div>
        
        <div class="filter-group">
          <label for="brand">Brand</label>
          <select id="brand" name="brand">
            <option value="">All Brands</option>
            <?php
            $brandStmt = $db->query("SELECT name FROM brands ORDER BY name");
            while ($brand = $brandStmt->fetch(PDO::FETCH_ASSOC)) {
                $selected = ($brand_filter === $brand['name']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($brand['name']) . '" ' . $selected . '>' . htmlspecialchars($brand['name']) . '</option>';
            }
            ?>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="min_year">Min Year</label>
          <input type="number" id="min_year" name="min_year" value="<?= $min_year > 0 ? $min_year : '' ?>" placeholder="1990" min="1900" max="2026">
        </div>
        
        <div class="filter-group">
          <label for="max_year">Max Year</label>
          <input type="number" id="max_year" name="max_year" value="<?= $max_year > 0 ? $max_year : '' ?>" placeholder="2026" min="1900" max="2026">
        </div>
        
        <div class="filter-actions">
          <button type="submit" class="btn">Filter</button>
          <a href="inventory.php" class="btn" style="margin-left: 0.5rem;">Reset</a>
        </div>
      </div>
    </form>
  </div>
  
  <?php
  // Execute filtered query
  $vehiclesStmt = $db->prepare($sql);
  $vehiclesStmt->execute($params);
  $vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Display search results count
  if (!empty($search) || !empty($brand_filter) || $min_year > 0 || $max_year > 0) {
      echo '<div class="search-results">' . count($vehicles) . ' vehicle(s) found</div>';
  }
  ?>
  
  <div id="inventoryGrid">
    <?php
    if (empty($vehicles)) {
        echo '<div class="search-results">No vehicles found matching your criteria.</div>';
    } else {
        $currentBrand = '';
        foreach ($vehicles as $vehicle) {
            if ($currentBrand !== $vehicle['brand_name']) {
                if ($currentBrand !== '') echo '</div></section>';
                $currentBrand = $vehicle['brand_name'];
                echo '<section class="brand-section" id="' . strtolower(str_replace(' ', '-', $currentBrand)) . '"><h2>' . htmlspecialchars($currentBrand) . ' Motors</h2><div class="cards-grid">';
            }
            ?>
            <div class="car-card" data-brand="<?= htmlspecialchars($vehicle['brand_name']) ?>">
              <?php 
                $imgUrl = $vehicle['image_url'] ?? '';
                $displayImg = !empty($imgUrl) ? $imgUrl : 'assets/images/products/landcruiser.png';
              ?>
              <img class="card-img" src="<?= htmlspecialchars($displayImg) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" loading="lazy">
              <div class="card-body">
                <h3><?= htmlspecialchars($vehicle['model']) ?></h3>
                <p><strong>Year:</strong> <?= htmlspecialchars($vehicle['year']) ?></p>
                <p><strong>Engine:</strong> <?= htmlspecialchars($vehicle['engine']) ?></p>
                <p><strong>Drive:</strong> <?= htmlspecialchars($vehicle['drive']) ?></p>
                <p><?= htmlspecialchars($vehicle['description']) ?></p>
              </div>
            </div>
            <?php
        }
        if (!empty($vehicles)) echo '</div></section>';
    }
    ?>
  </div>
</div>
<?php include "../include/footer.php"; ?>
</body>
</html>
