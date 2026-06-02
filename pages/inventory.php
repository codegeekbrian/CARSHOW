<?php
/**
 * Vehicle Inventory Page
 * Displays all vehicles with filtering by brand, year, and search.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/analytics.php';
require_once __DIR__ . '/../include/finance.php';
require_once __DIR__ . '/../include/inventory_queries.php';

logActivity('view');

$current_page = 'inventory';

$filters = inventoryFiltersFromRequest($_GET);
$search = $filters['search'];
$min_year = $filters['min_year'];
$max_year = $filters['max_year'];
$min_price = $filters['min_price'];
$max_price = $filters['max_price'];
$brand_filter = $filters['brand'];
$type_filter = $filters['type'];
$fuel_filter = $filters['fuel'];
$transmission_filter = $filters['transmission'];
$condition_filter = $filters['condition'];
$branch_filter = $filters['branch'];
$is_logged_in = isset($_SESSION['user_id']);
$default_branch = 'Nairobi Showroom';

$filterOptions = inventoryFilterOptions($db);
$allBrands = $filterOptions['brands'];
$hasFilters = inventoryHasActiveFilters($filters);
$activeFilters = inventoryActiveFilterLabels($filters);

[$sql, $params] = inventoryQueryForFilters($filters);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
$recommendedVehicles = empty($vehicles) ? inventoryRecommendedVehicles($db, $filters) : [];
$pageTitle = "Inventory";
if (!defined('IS_INDEX_INCLUDE')) {
    require_once __DIR__ . '/../include/header.php';
}
?>
<script>const BASE_PATH = '<?php echo BASE_PATH; ?>';</script>

<main>
    <header class="site-hero">
        <h1>Our <span>Inventory</span></h1>
        <p><?php echo h($settings['inventory_tagline'] ?? "Browse our carefully sourced selection of quality vehicles from around the world."); ?></p>
    </header>

    <div class="page-content">
        <section class="trust-row" aria-label="Dealership services">
            <span>Inspection-ready stock</span>
            <span>Branch viewing</span>
            <span>Trade-in inquiry</span>
            <span>Financing inquiry</span>
        </section>

        <div class="inventory-filter-compact" aria-label="Inventory filter controls">
            <button class="mobile-filter-toggle" type="button" data-filter-open aria-controls="inventoryFilters" aria-expanded="false">
                Filter inventory
            </button>
            <div class="active-filter-chips" aria-label="Active filters">
                <?php if (empty($activeFilters)): ?>
                    <span>All available vehicles</span>
                <?php else: ?>
                    <?php foreach ($activeFilters as $filterLabel): ?>
                        <span><?php echo h($filterLabel); ?></span>
                    <?php endforeach; ?>
                    <a href="<?php echo url('inventory'); ?>">Clear</a>
                <?php endif; ?>
            </div>
        </div>

        <section class="filters-bar" id="inventoryFilters">
            <div class="filter-sheet-head">
                <span>Filter stock</span>
                <button type="button" data-filter-close aria-label="Close filters">Close</button>
            </div>
            <form method="GET" action="<?php echo url('inventory'); ?>">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search"
                           value="<?php echo h($search); ?>"
                           placeholder="Model, brand, description...">
                </div>

                <div class="filter-group">
                    <label for="brand">Brand</label>
                    <select id="brand" name="brand">
                        <option value="">All Brands</option>
                        <?php foreach ($allBrands as $b): ?>
                            <option value="<?php echo h($b); ?>"
                                <?php echo $brand_filter === $b ? 'selected' : ''; ?>>
                                <?php echo h($b); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($filterOptions['types'] as $type): ?>
                            <option value="<?php echo h($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>><?php echo h($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="fuel">Fuel</label>
                    <select id="fuel" name="fuel">
                        <option value="">All Fuel</option>
                        <?php foreach ($filterOptions['fuels'] as $fuel): ?>
                            <option value="<?php echo h($fuel); ?>" <?php echo $fuel_filter === $fuel ? 'selected' : ''; ?>><?php echo h($fuel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission">
                        <option value="">All Transmissions</option>
                        <?php foreach ($filterOptions['transmissions'] as $transmission): ?>
                            <option value="<?php echo h($transmission); ?>" <?php echo $transmission_filter === $transmission ? 'selected' : ''; ?>><?php echo h($transmission); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="condition">Condition</label>
                    <select id="condition" name="condition">
                        <option value="">All Conditions</option>
                        <?php foreach ($filterOptions['conditions'] as $condition): ?>
                            <option value="<?php echo h($condition); ?>" <?php echo $condition_filter === $condition ? 'selected' : ''; ?>><?php echo h($condition); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="branch">Branch</label>
                    <select id="branch" name="branch">
                        <option value="">All Branches</option>
                        <?php foreach ($filterOptions['branches'] as $branch): ?>
                            <option value="<?php echo h($branch); ?>" <?php echo $branch_filter === $branch ? 'selected' : ''; ?>><?php echo h($branch); ?></option>
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

                <div class="filter-group">
                    <label for="min_price">Min Price</label>
                    <input type="number" id="min_price" name="min_price"
                           value="<?php echo $min_price > 0 ? $min_price : ''; ?>"
                           placeholder="2500000" min="0" step="50000">
                </div>

                <div class="filter-group">
                    <label for="max_price">Max Price</label>
                    <input type="number" id="max_price" name="max_price"
                           value="<?php echo $max_price > 0 ? $max_price : ''; ?>"
                           placeholder="12000000" min="0" step="50000">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn">Filter</button>
                    <a href="<?php echo url('inventory'); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </section>
        <div class="filter-backdrop" data-filter-close hidden></div>

        <p class="search-results-count <?php echo $hasFilters ? '' : 'd-none'; ?>" id="results-count">
            <?php echo count($vehicles); ?> vehicle<?php echo count($vehicles) !== 1 ? 's' : ''; ?> found
        </p>

        <section id="inventory-container">
        <?php if (empty($vehicles)): ?>
            <div class="empty-state">
                <h3>No Vehicles Found</h3>
                <p>Try adjusting your filters or search terms.</p>
                <?php if (!empty($recommendedVehicles)): ?>
                    <div class="no-results-recommendations" aria-label="Similar vehicles">
                        <h4>Similar vehicles worth checking</h4>
                        <div class="cards-grid">
                            <?php foreach ($recommendedVehicles as $recommendation): ?>
                                <article class="car-card recommendation-card">
                                    <img class="card-img"
                                         src="<?php echo getVehicleImage($recommendation['image_url'] ?? ''); ?>"
                                         alt="<?php echo h($recommendation['model']); ?>"
                                         loading="lazy"
                                         onerror="this.src='<?php echo url('assets/images/products/toyota-land-cruiser-300.jpg'); ?>'; this.onerror=null;">
                                    <div class="card-body">
                                        <p class="vehicle-card-brand"><?php echo h($recommendation['brand_name']); ?></p>
                                        <h3><?php echo h($recommendation['model']); ?></h3>
                                        <div class="vehicle-card-meta">
                                            <span><?php echo h($recommendation['year']); ?></span>
                                            <span><?php echo h($recommendation['branch_name'] ?? $default_branch); ?></span>
                                        </div>
                                        <div class="vehicle-card-actions">
                                            <a href="<?php echo url('vehicle/' . (int)$recommendation['id']); ?>" class="btn card-link-wrap">View</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else:
            $currentBrand = '';
            $visibleLimit = 12;
            $vehicleIndex = 0;
            foreach ($vehicles as $v):
                $vehicleIndex++;
                if ($currentBrand !== $v['brand_name']):
                    if ($currentBrand !== '') echo '</div></section>';
                    $currentBrand = $v['brand_name'];
                    $slug = slugify($currentBrand);
                    $sectionClass = (!$hasFilters && $vehicleIndex > $visibleLimit) ? ' brand-section inventory-extra-section is-hidden' : ' brand-section';
                    echo '<section class="' . trim($sectionClass) . '" id="' . h($slug) . '">
                          <h2>' . h($currentBrand) . ' Motors</h2>
                          <div class="cards-grid">';
                endif;
                $financeEstimate = financeEstimateRange($v['price'] ?? 0, $settings ?? []);
                $availabilitySubject = rawurlencode('Availability check: ' . $v['brand_name'] . ' ' . $v['model']);
                $priceSubject = rawurlencode('Best price request: ' . $v['brand_name'] . ' ' . $v['model']);
            ?>
                <article class="car-card <?php echo (!$hasFilters && $vehicleIndex > $visibleLimit) ? 'inventory-extra is-hidden' : ''; ?>" data-brand="<?php echo h($v['brand_name']); ?>">
                    <div class="vehicle-badges" aria-label="Vehicle status">
                        <span class="vehicle-badge-icon status" data-icon="OK" aria-label="<?php echo h(ucfirst($v['status'] ?? 'available')); ?>" title="<?php echo h(ucfirst($v['status'] ?? 'available')); ?>">
                            <span class="sr-only"><?php echo h(ucfirst($v['status'] ?? 'available')); ?></span>
                        </span>
                        <span class="vehicle-badge-icon inspection" data-icon="IN" aria-label="Inspection-ready" title="Inspection-ready">
                            <span class="sr-only">Inspection-ready</span>
                        </span>
                        <span class="vehicle-badge-icon finance" data-icon="KES" aria-label="Finance available" title="Finance available">
                            <span class="sr-only">Finance available</span>
                        </span>
                        <?php if (!empty($v['is_featured'])): ?>
                            <span class="vehicle-badge-icon featured" data-icon="VIP" aria-label="Featured" title="Featured">
                                <span class="sr-only">Featured</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <img class="card-img"
                         src="<?php echo getVehicleImage($v['image_url'] ?? ''); ?>"
                         alt="<?php echo h($v['model']); ?>"
                         loading="lazy"
                         onerror="this.src='<?php echo url('assets/images/products/toyota-land-cruiser-300.jpg'); ?>'; this.onerror=null;">
                    <div class="card-body">
                        <p class="vehicle-card-brand"><?php echo h($v['brand_name']); ?></p>
                        <h3><?php echo h($v['model']); ?></h3>
                        <div class="vehicle-card-meta">
                            <span><?php echo h($v['year']); ?></span>
                            <span><?php echo h($v['branch_name'] ?? $default_branch); ?></span>
                            <?php if (!empty($v['vehicle_type'])): ?><span><?php echo h($v['vehicle_type']); ?></span><?php endif; ?>
                        </div>
                        <div class="vehicle-card-price-row">
                            <?php if ($financeEstimate['available']): ?>
                                <span><?php echo h($financeEstimate['label']); ?></span>
                            <?php else: ?>
                                <span>Check availability</span>
                            <?php endif; ?>
                            <a href="<?php echo url('contact?subject=' . $priceSubject); ?>">Unlock Best Price</a>
                        </div>
                        <div class="vehicle-card-actions">
                            <a href="<?php echo url('vehicle/' . (int) $v['id']); ?>" class="btn card-link-wrap">View</a>
                            <a href="<?php echo url('contact?subject=' . $availabilitySubject); ?>" class="btn-outline">Check Availability</a>
                        </div>
                        <div class="vehicle-card-tools" aria-label="Vehicle shortlist actions">
                            <button type="button" class="btn-outline compact-action" data-save-id="<?php echo (int) $v['id']; ?>">Save</button>
                            <button type="button" class="btn-outline compact-action" data-compare-id="<?php echo (int) $v['id']; ?>">Compare</button>
                        </div>
                    </div>
                </article>
            <?php endforeach;
            echo '</div></section>';
            if (!$hasFilters && count($vehicles) > $visibleLimit): ?>
                <div class="inventory-more-row">
                    <button type="button" class="btn btn-secondary" data-show-inventory>Show <?php echo count($vehicles) - $visibleLimit; ?> more vehicles</button>
                </div>
            <?php endif;
        endif; ?>
        </section>
    </div>
</main>
<?php if (!defined('IS_INDEX_INCLUDE')) { include __DIR__ . '/../include/footer.php'; } ?>
