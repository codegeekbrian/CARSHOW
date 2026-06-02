<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/finance.php';
require_once __DIR__ . '/inventory_queries.php';

$settings = loadSettings($db);

$filters = inventoryFiltersFromRequest($_GET);
[$sql, $params] = inventoryQueryForFilters($filters);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = inventorySerializeVehicles($stmt->fetchAll(PDO::FETCH_ASSOC), $settings);

$recommendedVehicles = [];
if (empty($vehicles)) {
    $recommendedVehicles = inventorySerializeVehicles(inventoryRecommendedVehicles($db, $filters), $settings);
}

$filterOptions = inventoryFilterOptions($db);

echo json_encode([
    'count' => count($vehicles),
    'vehicles' => $vehicles,
    'recommended_vehicles' => $recommendedVehicles,
    'brands' => $filterOptions['brands'],
    'filter_options' => $filterOptions,
    'filters' => $filters
]);
