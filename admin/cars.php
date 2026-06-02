<?php
require_once __DIR__ . '/admin_db.php';
require_once dirname(__DIR__) . '/include/admin_inventory.php';
require_once dirname(__DIR__) . '/include/media_uploads.php';
require_permission('inventory.manage');
require_csrf();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
        case 'edit':
            $imageUrl = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (!admin_can('inventory.upload')) {
                    $message = 'You do not have permission to upload images.';
                    break;
                }
                $upload = storeUploadedImage($db, $_FILES['image_file'], 'vehicles', $_SESSION['admin_username'] ?? null);
                if (empty($upload['valid'])) {
                    $message = $upload['error'] ?? 'Image upload failed.';
                    break;
                }
                $imageUrl = $upload['path'];
            }

            $data = [
                $_POST['brand_id'], $_POST['model'], $_POST['year'], $_POST['price'],
                $_POST['description'], $_POST['engine'], $_POST['power'], $_POST['drive'],
                $_POST['vehicle_type'] ?? '', $_POST['fuel_type'] ?? '', $_POST['transmission'] ?? '',
                ($_POST['branch_id'] ?? '') !== '' ? $_POST['branch_id'] : null,
                $_POST['condition_label'] ?? 'Pre-owned', $_POST['source_market'] ?? 'Locally available',
                $imageUrl, isset($_POST['is_featured']) ? 1 : 0,
                isset($_POST['is_inventory']) ? 1 : 0,
                $_POST['status'] ?? 'available'
            ];
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, price, description, engine, power, drive, vehicle_type, fuel_type, transmission, branch_id, condition_label, source_market, image_url, is_featured, is_inventory, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            } else {
                $data[] = $_POST['id'];
                $stmt = $db->prepare("UPDATE vehicles SET brand_id = ?, model = ?, year = ?, price = ?, description = ?, engine = ?, power = ?, drive = ?, vehicle_type = ?, fuel_type = ?, transmission = ?, branch_id = ?, condition_label = ?, source_market = ?, image_url = ?, is_featured = ?, is_inventory = ?, status = ? WHERE id = ?");
            }
            if ($stmt->execute($data)) {
                $vehicleId = $_POST['action'] === 'add' ? (int)$db->lastInsertId() : (int)$_POST['id'];
                $vehicleLabel = trim((string)($_POST['model'] ?? 'vehicle'));
                recordAdminAuditLog(
                    $db,
                    $_POST['action'] === 'add' ? 'vehicle.created' : 'vehicle.updated',
                    'vehicle',
                    $vehicleId,
                    ($_POST['action'] === 'add' ? 'Created vehicle ' : 'Updated vehicle ') . $vehicleLabel
                );
                clearCacheByTag('inventory');
                clearCacheByTag('featured');
                $message = ($_POST['action'] === 'add' ? 'Car added' : 'Car updated') . " successfully!";
            } else {
                $message = "Error saving car.";
            }
            break;

        case 'bulk':
            $message = applyAdminVehicleBulkAction(
                $db,
                $_POST['vehicle_ids'] ?? [],
                $_POST['bulk_action'] ?? '',
                $_SESSION['admin_username'] ?? 'admin'
            );
            break;

        case 'delete':
            $vehicleId = (int)($_POST['id'] ?? 0);
            $labelStmt = $db->prepare("
                SELECT CONCAT(COALESCE(b.name, ''), ' ', COALESCE(v.model, 'vehicle')) AS vehicle_label
                FROM vehicles v
                LEFT JOIN brands b ON b.id = v.brand_id
                WHERE v.id = ?
                LIMIT 1
            ");
            $labelStmt->execute([$vehicleId]);
            $vehicleLabel = trim((string)($labelStmt->fetchColumn() ?: 'vehicle'));
            $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
            if ($stmt->execute([$vehicleId])) {
                recordAdminAuditLog($db, 'vehicle.deleted', 'vehicle', $vehicleId, "Deleted vehicle {$vehicleLabel}");
                clearCacheByTag('inventory');
                clearCacheByTag('featured');
                $message = "Car deleted successfully!";
            } else {
                $message = "Error deleting car.";
            }
            break;
    }
}

$stmt = $db->prepare("SELECT v.*, b.name as brand_name, br.name as branch_name FROM vehicles v JOIN brands b ON v.brand_id = b.id LEFT JOIN branches br ON v.branch_id = br.id ORDER BY v.brand_id, v.model");
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM brands ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryGroups = [];
$categoryStmt = $db->query("SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY group_name, sort_order, name");
foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $category) {
    $categoryGroups[$category['group_name']][] = $category;
}

$pageTitle = 'Manage Cars';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">
            Vehicle inventory
            <button type="button" class="btn btn-success" onclick="openAddModal()">Add New Car</button>
        </h2>

        <?php if ($message): ?>
            <div class="message <?php echo stripos($message, 'failed') !== false || stripos($message, 'select') !== false ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" id="vehicleBulkForm" class="admin-bulk-bar" data-bulk-form onsubmit="return confirm('Apply this bulk action to the selected vehicles?')">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="bulk">
            <label class="admin-checkline">
                <input type="checkbox" data-bulk-select-all>
                Select all visible vehicles
            </label>
            <label for="vehicle-bulk-action" class="sr-only">Bulk action</label>
            <select id="vehicle-bulk-action" name="bulk_action" class="compact-select" required>
                <option value="">Bulk action</option>
                <option value="status_available">Set status: Available</option>
                <option value="status_reserved">Set status: Reserved</option>
                <option value="status_sold">Set status: Sold</option>
                <option value="feature_on">Mark as Featured</option>
                <option value="feature_off">Remove Featured</option>
                <option value="delete">Delete selected</option>
            </select>
            <button type="submit" class="btn btn-sm">Apply</button>
        </form>

        <div class="table-responsive inventory-table-wrap">
            <table class="admin-table-stack">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Engine</th>
                        <th>Power</th>
                        <th>Drive</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                    <tr>
                        <td data-label="Select">
                            <label class="admin-row-select">
                                <input type="checkbox" name="vehicle_ids[]" value="<?php echo (int)$car['id']; ?>" form="vehicleBulkForm" data-bulk-item>
                                <span class="sr-only">Select <?php echo h($car['brand_name'] . ' ' . $car['model']); ?></span>
                            </label>
                        </td>
                        <td data-label="Brand"><?php echo htmlspecialchars($car['brand_name']); ?></td>
                        <td data-label="Model"><?php echo htmlspecialchars($car['model']); ?></td>
                        <td data-label="Year"><?php echo htmlspecialchars($car['year']); ?></td>
                        <td data-label="Price"><?php echo h(formatAdminVehiclePrice($car['price'])); ?></td>
                        <td data-label="Engine"><?php echo htmlspecialchars($car['engine'] ?? '-'); ?></td>
                        <td data-label="Power"><?php echo htmlspecialchars($car['power'] ?? '-'); ?></td>
                        <td data-label="Drive"><?php echo htmlspecialchars($car['drive'] ?? '-'); ?></td>
                        <td data-label="Type"><?php echo htmlspecialchars($car['vehicle_type'] ?? '-'); ?></td>
                        <td data-label="Branch"><?php echo htmlspecialchars($car['branch_name'] ?? '-'); ?></td>
                        <td data-label="Status"><span class="status-badge status-<?php echo h($car['status'] ?? 'available'); ?>"><?php echo htmlspecialchars(ucfirst($car['status'] ?? 'available')); ?></span></td>
                        <td data-label="Featured"><?php echo $car['is_featured'] ? 'Yes' : 'No'; ?></td>
                        <td data-label="Actions">
                            <div class="admin-row-actions">
                            <button type="button" class="btn" onclick='openEditModal(<?php echo json_encode($car); ?>)'>Edit</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-mobile-inventory-list" aria-label="Compact vehicle list">
            <?php foreach ($cars as $car): ?>
                <article class="admin-vehicle-card">
                    <label class="admin-mobile-card-select">
                        <input type="checkbox" name="vehicle_ids[]" value="<?php echo (int)$car['id']; ?>" form="vehicleBulkForm" data-bulk-item>
                        Select vehicle
                    </label>
                    <div class="admin-vehicle-card-main">
                        <div>
                            <p class="admin-kicker"><?php echo h($car['brand_name']); ?></p>
                            <h3><?php echo h($car['model']); ?></h3>
                            <p class="text-muted">
                                <?php echo h($car['year']); ?> / <?php echo h($car['vehicle_type'] ?: 'Unclassified'); ?> / <?php echo h($car['branch_name'] ?: 'No branch'); ?>
                            </p>
                        </div>
                        <div class="admin-vehicle-price">
                            <?php echo h(formatAdminVehiclePrice($car['price'])); ?>
                            <span class="status-badge status-<?php echo h($car['status'] ?? 'available'); ?>"><?php echo h(ucfirst($car['status'] ?? 'available')); ?></span>
                        </div>
                    </div>
                    <details class="admin-vehicle-details">
                        <summary>Specs</summary>
                        <dl>
                            <div><dt>Engine</dt><dd><?php echo h($car['engine'] ?: '-'); ?></dd></div>
                            <div><dt>Power</dt><dd><?php echo h($car['power'] ?: '-'); ?></dd></div>
                            <div><dt>Drive</dt><dd><?php echo h($car['drive'] ?: '-'); ?></dd></div>
                            <div><dt>Featured</dt><dd><?php echo $car['is_featured'] ? 'Yes' : 'No'; ?></dd></div>
                        </dl>
                    </details>
                    <div class="admin-row-actions">
                        <button type="button" class="btn btn-sm" onclick='openEditModal(<?php echo json_encode($car); ?>)'>Edit</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="carModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true" tabindex="-1">
    <div class="modal-content">
        <button type="button" class="close" onclick="closeModal()" aria-label="Close vehicle editor">&times;</button>
        <h2 id="modalTitle">Add New Car</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="carId">

            <div class="form-group">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id" required>
                    <option value="">Select Brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" required>
                </div>
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" required>
                </div>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="engine">Engine</label>
                    <input type="text" id="engine" name="engine">
                </div>
                <div class="form-group">
                    <label for="power">Power</label>
                    <input type="text" id="power" name="power">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="drive">Drive type</label>
                    <select id="drive" name="drive">
                        <option value="">Select drive</option>
                        <option value="FWD">FWD</option>
                        <option value="RWD">RWD</option>
                        <option value="AWD">AWD</option>
                        <option value="4WD">4WD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vehicle_type">Body type</label>
                    <select id="vehicle_type" name="vehicle_type">
                        <option value="">Select Type</option>
                        <?php foreach (($categoryGroups['Body type'] ?? []) as $category): ?>
                            <option value="<?php echo h($category['name']); ?>"><?php echo h($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type">
                        <option value="">Select Fuel</option>
                        <?php foreach (($categoryGroups['Fuel'] ?? []) as $category): ?>
                            <option value="<?php echo h($category['name']); ?>"><?php echo h($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission">
                        <option value="">Select transmission</option>
                        <?php foreach (($categoryGroups['Transmission'] ?? []) as $category): ?>
                            <option value="<?php echo h($category['name']); ?>"><?php echo h($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="condition_label">Condition</label>
                    <select id="condition_label" name="condition_label">
                        <?php foreach (($categoryGroups['Condition'] ?? []) as $category): ?>
                            <option value="<?php echo h($category['name']); ?>"><?php echo h($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="source_market">Source market</label>
                    <select id="source_market" name="source_market">
                        <?php foreach (($categoryGroups['Source market'] ?? []) as $category): ?>
                            <option value="<?php echo h($category['name']); ?>"><?php echo h($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="branch_id">Branch</label>
                    <select id="branch_id" name="branch_id">
                        <option value="">No Branch</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="available">Available</option>
                        <option value="reserved">Reserved</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="image_url">Image path</label>
                <input type="text" id="image_url" name="image_url">
                <?php if (admin_can('inventory.upload')): ?>
                    <label for="image_file">Upload image</label>
                    <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/webp">
                    <small>Uploading a file replaces the image path for this vehicle.</small>
                <?php endif; ?>
            </div>

            <div class="checkbox-group">
                <label><input type="checkbox" id="is_featured" name="is_featured" value="1"> Featured Car</label>
                <label><input type="checkbox" id="is_inventory" name="is_inventory" value="1" checked> In Inventory</label>
            </div>

            <div class="admin-modal-actions">
                <button type="submit" class="btn btn-success">Save Car</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Car';
    document.getElementById('formAction').value = 'add';
    document.getElementById('carId').value = '';
    document.getElementById('brand_id').value = '';
    document.getElementById('model').value = '';
    document.getElementById('year').value = '';
    document.getElementById('price').value = '';
    document.getElementById('description').value = '';
    document.getElementById('engine').value = '';
    document.getElementById('power').value = '';
    document.getElementById('drive').value = '';
    document.getElementById('vehicle_type').value = '';
    document.getElementById('fuel_type').value = '';
    document.getElementById('transmission').value = '';
    document.getElementById('condition_label').value = 'Pre-owned';
    document.getElementById('source_market').value = 'Locally available';
    document.getElementById('branch_id').value = '';
    document.getElementById('status').value = 'available';
    document.getElementById('image_url').value = '';
    document.getElementById('is_featured').checked = false;
    document.getElementById('is_inventory').checked = true;
    openAdminModal('carModal');
}

function openEditModal(car) {
    document.getElementById('modalTitle').textContent = 'Edit Car';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('carId').value = car.id;
    document.getElementById('brand_id').value = car.brand_id;
    document.getElementById('model').value = car.model;
    document.getElementById('year').value = car.year;
    document.getElementById('price').value = car.price;
    document.getElementById('description').value = car.description || '';
    document.getElementById('engine').value = car.engine || '';
    document.getElementById('power').value = car.power || '';
    document.getElementById('drive').value = car.drive || '';
    document.getElementById('vehicle_type').value = car.vehicle_type || '';
    document.getElementById('fuel_type').value = car.fuel_type || '';
    document.getElementById('transmission').value = car.transmission || '';
    document.getElementById('condition_label').value = car.condition_label || 'Pre-owned';
    document.getElementById('source_market').value = car.source_market || 'Locally available';
    document.getElementById('branch_id').value = car.branch_id || '';
    document.getElementById('status').value = car.status || 'available';
    document.getElementById('image_url').value = car.image_url || '';
    document.getElementById('is_featured').checked = car.is_featured == 1;
    document.getElementById('is_inventory').checked = car.is_inventory == 1;
    openAdminModal('carModal');
}

function closeModal() {
    closeAdminModal('carModal');
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
