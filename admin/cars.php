<?php
require_once __DIR__ . '/admin_db.php';
require_auth();
validate_csrf();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
        case 'edit':
            $data = [
                $_POST['brand_id'], $_POST['model'], $_POST['year'], $_POST['price'],
                $_POST['description'], $_POST['engine'], $_POST['power'], $_POST['drive'],
                $_POST['image_url'], isset($_POST['is_featured']) ? 1 : 0,
                isset($_POST['is_legacy']) ? 1 : 0, isset($_POST['is_inventory']) ? 1 : 0
            ];
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, price, description, engine, power, drive, image_url, is_featured, is_legacy, is_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            } else {
                $data[] = $_POST['id'];
                $stmt = $db->prepare("UPDATE vehicles SET brand_id = ?, model = ?, year = ?, price = ?, description = ?, engine = ?, power = ?, drive = ?, image_url = ?, is_featured = ?, is_legacy = ?, is_inventory = ? WHERE id = ?");
            }
            if ($stmt->execute($data)) {
                clearAllCache();
                $message = ($_POST['action'] === 'add' ? 'Car added' : 'Car updated') . " successfully!";
            } else {
                $message = "Error saving car.";
            }
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                clearAllCache();
                $message = "Car deleted successfully!";
            } else {
                $message = "Error deleting car.";
            }
            break;
    }
}

$stmt = $db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id ORDER BY v.brand_id, v.model");
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM brands ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Cars';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">
            Car Inventory
            <button class="btn btn-success" onclick="openAddModal()">Add New Car</button>
        </h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Price</th>
                    <th>Engine</th>
                    <th>Power</th>
                    <th>Drive</th>
                    <th>Featured</th>
                    <th>Legacy</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cars as $car): ?>
                <tr>
                    <td><?php echo htmlspecialchars($car['brand_name']); ?></td>
                    <td><?php echo htmlspecialchars($car['model']); ?></td>
                    <td><?php echo htmlspecialchars($car['year']); ?></td>
                    <td>$<?php echo number_format($car['price']); ?></td>
                    <td><?php echo htmlspecialchars($car['engine'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($car['power'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($car['drive'] ?? '-'); ?></td>
                    <td><?php echo $car['is_featured'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $car['is_legacy'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <button class="btn" onclick='openEditModal(<?php echo json_encode($car); ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="carModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Add New Car</h2>
        <form method="POST">
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
                    <label for="drive">Drive Type</label>
                    <input type="text" id="drive" name="drive">
                </div>
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url">
                </div>
            </div>

            <div class="checkbox-group">
                <label><input type="checkbox" id="is_featured" name="is_featured" value="1"> Featured Car</label>
                <label><input type="checkbox" id="is_legacy" name="is_legacy" value="1"> Legacy Model</label>
                <label><input type="checkbox" id="is_inventory" name="is_inventory" value="1" checked> In Inventory</label>
            </div>

            <button type="submit" class="btn btn-success">Save Car</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
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
    document.getElementById('image_url').value = '';
    document.getElementById('is_featured').checked = false;
    document.getElementById('is_legacy').checked = false;
    document.getElementById('is_inventory').checked = true;
    document.getElementById('carModal').style.display = 'flex';
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
    document.getElementById('image_url').value = car.image_url || '';
    document.getElementById('is_featured').checked = car.is_featured == 1;
    document.getElementById('is_legacy').checked = car.is_legacy == 1;
    document.getElementById('is_inventory').checked = car.is_inventory == 1;
    document.getElementById('carModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('carModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('carModal');
    if (event.target == modal) closeModal();
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>