<?php
require_once __DIR__ . '/admin_db.php';
require_permission('brands.manage');
require_csrf();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $stmt = $db->prepare("INSERT INTO brands (name, logo_url) VALUES (?, ?)");
            if ($stmt->execute([$_POST['name'], $_POST['logo_url']])) {
                clearCacheByTag('brands');
                $message = "Brand added successfully!";
            } else {
                $message = "Error adding brand.";
            }
            break;

        case 'delete':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE brand_id = ?");
            $stmt->execute([$_POST['id']]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($count > 0) {
                $message = "Cannot delete brand because it is used by $count vehicle(s).";
            } else {
                $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    clearCacheByTag('brands');
                    $message = "Brand deleted successfully!";
                } else {
                    $message = "Error deleting brand.";
                }
            }
            break;

        case 'edit':
            $stmt = $db->prepare("UPDATE brands SET name = ?, logo_url = ? WHERE id = ?");
            if ($stmt->execute([$_POST['name'], $_POST['logo_url'], $_POST['id']])) {
                clearCacheByTag('brands');
                $message = "Brand updated successfully!";
            } else {
                $message = "Error updating brand.";
            }
            break;
    }
}

$stmt = $db->prepare("SELECT b.*, COUNT(v.id) as vehicle_count FROM brands b LEFT JOIN vehicles v ON b.id = v.brand_id GROUP BY b.id ORDER BY b.name");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Brands';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">
            Car Brands
            <button type="button" class="btn btn-success" onclick="openAddModal()">Add New Brand</button>
        </h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'Cannot') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
        <table class="admin-table-stack">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Brand Name</th>
                    <th>Vehicles Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                <tr>
                    <td data-label="Logo">
                        <?php if ($brand['logo_url']): ?>
                            <img src="<?php echo getVehicleImage($brand['logo_url']); ?>" alt="<?php echo h($brand['name']); ?> logo" class="admin-logo-preview">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td data-label="Brand Name"><?php echo htmlspecialchars($brand['name']); ?></td>
                    <td data-label="Vehicles Count"><?php echo $brand['vehicle_count']; ?></td>
                    <td data-label="Actions">
                        <button type="button" class="btn" onclick='openEditModal(<?php echo $brand["id"]; ?>, <?php echo json_encode($brand["name"]); ?>, <?php echo json_encode($brand["logo_url"]); ?>)'>Edit</button>
                        <?php if ($brand['vehicle_count'] == 0): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $brand['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-danger" disabled title="Cannot delete brand with associated vehicles">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div id="brandModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true" tabindex="-1">
    <div class="modal-content">
        <button type="button" class="close" onclick="closeModal()" aria-label="Close brand editor">&times;</button>
        <h2 id="modalTitle">Add New Brand</h2>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="brandId">

            <div class="form-group">
                <label for="name">Brand Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="logo_url">Logo URL</label>
                <input type="text" id="logo_url" name="logo_url" placeholder="assets/images/products/logo.jpg">
            </div>

            <div class="admin-modal-actions">
                <button type="submit" class="btn btn-success">Save Brand</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Brand';
    document.getElementById('formAction').value = 'add';
    document.getElementById('brandId').value = '';
    document.getElementById('name').value = '';
    document.getElementById('logo_url').value = '';
    openAdminModal('brandModal');
}

function openEditModal(id, name, logo_url) {
    document.getElementById('modalTitle').textContent = 'Edit Brand';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('brandId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('logo_url').value = logo_url || '';
    openAdminModal('brandModal');
}

function closeModal() {
    closeAdminModal('brandModal');
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
