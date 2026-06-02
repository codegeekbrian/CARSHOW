<?php
/**
 * Shared inventory filtering and recommendation queries.
 */

function inventoryFiltersFromRequest(array $source): array
{
    return [
        'search' => trim((string)($source['search'] ?? '')),
        'min_year' => max(0, (int)($source['min_year'] ?? 0)),
        'max_year' => max(0, (int)($source['max_year'] ?? 0)),
        'min_price' => max(0, (int)($source['min_price'] ?? 0)),
        'max_price' => max(0, (int)($source['max_price'] ?? 0)),
        'brand' => trim((string)($source['brand'] ?? '')),
        'type' => trim((string)($source['type'] ?? '')),
        'fuel' => trim((string)($source['fuel'] ?? '')),
        'transmission' => trim((string)($source['transmission'] ?? '')),
        'condition' => trim((string)($source['condition'] ?? '')),
        'branch' => trim((string)($source['branch'] ?? '')),
    ];
}

function inventoryQueryForFilters(array $filters): array
{
    $sql = "
        SELECT v.*, b.name AS brand_name, br.name AS branch_name
        FROM vehicles v
        JOIN brands b ON v.brand_id = b.id
        LEFT JOIN branches br ON v.branch_id = br.id
        WHERE v.is_inventory = 1 AND v.status = 'available'
    ";
    $params = [];

    if ($filters['search'] !== '') {
        $searchParts = [];
        foreach (explode(' ', $filters['search']) as $word) {
            $word = trim($word);
            if ($word === '') {
                continue;
            }
            $searchParts[] = '(v.model LIKE ? OR v.description LIKE ? OR b.name LIKE ?)';
            $params[] = "%{$word}%";
            $params[] = "%{$word}%";
            $params[] = "%{$word}%";
        }
        if ($searchParts) {
            $sql .= ' AND (' . implode(' AND ', $searchParts) . ')';
        }
    }

    foreach ([
        'brand' => 'b.name',
        'type' => 'v.vehicle_type',
        'fuel' => 'v.fuel_type',
        'transmission' => 'v.transmission',
        'condition' => 'v.condition_label',
        'branch' => 'br.name',
    ] as $filterKey => $column) {
        if ($filters[$filterKey] !== '') {
            $sql .= " AND {$column} = ?";
            $params[] = $filters[$filterKey];
        }
    }

    foreach ([
        'min_year' => ['v.year >= ?', $filters['min_year']],
        'max_year' => ['v.year <= ?', $filters['max_year']],
        'min_price' => ['v.price >= ?', $filters['min_price']],
        'max_price' => ['v.price <= ?', $filters['max_price']],
    ] as [$condition, $value]) {
        if ($value > 0) {
            $sql .= " AND {$condition}";
            $params[] = $value;
        }
    }

    return [$sql . ' ORDER BY b.name ASC, v.year DESC', $params];
}

function inventoryHasActiveFilters(array $filters): bool
{
    foreach ($filters as $value) {
        if ((is_int($value) && $value > 0) || (is_string($value) && $value !== '')) {
            return true;
        }
    }
    return false;
}

function inventoryActiveFilterLabels(array $filters): array
{
    $labels = [];
    foreach ([
        'search' => 'Search',
        'brand' => 'Brand',
        'type' => 'Type',
        'fuel' => 'Fuel',
        'transmission' => 'Transmission',
        'condition' => 'Condition',
        'branch' => 'Branch',
    ] as $key => $label) {
        if ($filters[$key] !== '') {
            $labels[] = "{$label}: " . $filters[$key];
        }
    }
    if ($filters['min_year'] > 0) $labels[] = 'From ' . $filters['min_year'];
    if ($filters['max_year'] > 0) $labels[] = 'To ' . $filters['max_year'];
    if ($filters['min_price'] > 0) $labels[] = 'Min KES ' . number_format($filters['min_price']);
    if ($filters['max_price'] > 0) $labels[] = 'Max KES ' . number_format($filters['max_price']);
    return $labels;
}

function inventoryFilterOptions(PDO $db): array
{
    return [
        'brands' => $db->query('SELECT name FROM brands ORDER BY name')->fetchAll(PDO::FETCH_COLUMN),
        'types' => $db->query("SELECT DISTINCT vehicle_type FROM vehicles WHERE is_inventory = 1 AND vehicle_type IS NOT NULL AND vehicle_type <> '' ORDER BY vehicle_type")->fetchAll(PDO::FETCH_COLUMN),
        'fuels' => $db->query("SELECT DISTINCT fuel_type FROM vehicles WHERE is_inventory = 1 AND fuel_type IS NOT NULL AND fuel_type <> '' ORDER BY fuel_type")->fetchAll(PDO::FETCH_COLUMN),
        'transmissions' => $db->query("SELECT DISTINCT transmission FROM vehicles WHERE is_inventory = 1 AND transmission IS NOT NULL AND transmission <> '' ORDER BY transmission")->fetchAll(PDO::FETCH_COLUMN),
        'conditions' => $db->query("SELECT DISTINCT condition_label FROM vehicles WHERE is_inventory = 1 AND condition_label IS NOT NULL AND condition_label <> '' ORDER BY condition_label")->fetchAll(PDO::FETCH_COLUMN),
        'branches' => $db->query('SELECT name FROM branches WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN),
    ];
}

function inventoryRecommendedVehicles(PDO $db, array $filters, int $limit = 4): array
{
    $order = [];
    $params = [];
    foreach ([
        'brand' => 'b.name = ? DESC',
        'type' => 'v.vehicle_type = ? DESC',
        'fuel' => 'v.fuel_type = ? DESC',
        'transmission' => 'v.transmission = ? DESC',
        'condition' => 'v.condition_label = ? DESC',
    ] as $key => $condition) {
        if ($filters[$key] !== '') {
            $order[] = $condition;
            $params[] = $filters[$key];
        }
    }

    if ($filters['search'] !== '') {
        $order[] = '(b.name LIKE ? OR v.model LIKE ? OR v.description LIKE ?) DESC';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $sql = "
        SELECT v.*, b.name AS brand_name, br.name AS branch_name
        FROM vehicles v
        JOIN brands b ON v.brand_id = b.id
        LEFT JOIN branches br ON v.branch_id = br.id
        WHERE v.is_inventory = 1 AND v.status = 'available'
        ORDER BY " . ($order ? implode(', ', $order) . ', ' : '') . "v.is_featured DESC, v.year DESC
        LIMIT " . max(1, min(12, $limit));
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function inventorySerializeVehicles(array $vehicles, array $settings): array
{
    foreach ($vehicles as &$vehicle) {
        $vehicle['image_url_resolved'] = getVehicleImage($vehicle['image_url'] ?? '');
        $vehicle['cash_price_label'] = formatVehicleCashPrice($vehicle['price'] ?? 0);
        $vehicle['finance_estimate'] = financeEstimateRange($vehicle['price'] ?? 0, $settings);
    }
    unset($vehicle);
    return $vehicles;
}
