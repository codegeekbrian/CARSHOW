<?php
/**
 * Admin: Manage Legacy Details
 */
require_once __DIR__ . '/admin_db.php';
require_auth();
validate_csrf();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
        case 'edit':
            $data = [$_POST['vehicle_id'], $_POST['generation_name'], $_POST['years'], $_POST['history_text'], $_POST['image_url']];
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO legacy_details (vehicle_id, generation_name, years, history_text, image_url) VALUES (?, ?, ?, ?, ?)");
            } else {
                $data[] = $_POST['id'];
                $stmt = $db->prepare("UPDATE legacy_details SET vehicle_id = ?, generation_name = ?, years = ?, history_text = ?, image_url = ? WHERE id = ?");
            }
            if ($stmt->execute($data)) {
                clearAllCache();
                $message = ($_POST['action'] === 'add' ? 'Legacy detail added' : 'Legacy detail updated') . " successfully!";
            } else {
                $message = "Error saving legacy detail.";
            }
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM legacy_details WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                clearAllCache();
                $message = "Legacy detail deleted successfully!";
            } else {
                $message = "Error deleting legacy detail.";
            }
            break;
    }
}

$stmt = $db->prepare("SELECT ld.*, v.model, b.name as brand_name FROM legacy_details ld JOIN vehicles v ON ld.vehicle_id = v.id JOIN brands b ON v.brand_id = b.id ORDER BY b.name, v.model, ld.id");
$stmt->execute();
$legacy_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT v.id, v.model, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.is_legacy = 1 ORDER BY b.name, v.model");
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Legacy';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">
            Legacy Details
            <button class="btn btn-success" onclick="openAddModal()">Add New Detail</button>
        </h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Generation</th>
                    <th>Years</th>
                    <th>Image URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($legacy_details as $detail): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detail['brand_name'] . ' ' . $detail['model']); ?></td>
                    <td><?php echo htmlspecialchars($detail['generation_name']); ?></td>
                    <td><?php echo htmlspecialchars($detail['years']); ?></td>
                    <td><?php echo htmlspecialchars(substr($detail['image_url'], 0, 30)) . (strlen($detail['image_url']) > 30 ? '...' : ''); ?></td>
                    <td>
                        <button class="btn" onclick='openEditModal(<?php echo json_encode($detail); ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $detail['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="legacyModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Add New Legacy Detail</h2>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="detailId">

            <div class="form-group">
                <label for="vehicle_id">Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" required>
                    <option value="">Select Vehicle</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['brand_name'] . ' ' . $v['model']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="generation_name">Generation Name</label>
                    <input type="text" id="generation_name" name="generation_name" required>
                </div>
                <div class="form-group">
                    <label for="years">Years</label>
                    <input type="text" id="years" name="years" placeholder="e.g., 1990-2001" required>
                </div>
            </div>

            <div class="form-group">
                <label for="history_text">History Text</label>
                <textarea id="history_text" name="history_text" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="text" id="image_url" name="image_url" placeholder="e.g., assets/images/gtr/gtr1.jpg" required>
            </div>

            <button type="submit" class="btn btn-success">Save Detail</button>
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Legacy Detail';
    document.getElementById('formAction').value = 'add';
    document.getElementById('detailId').value = '';
    document.getElementById('vehicle_id').value = '';
    document.getElementById('generation_name').value = '';
    document.getElementById('years').value = '';
    document.getElementById('history_text').value = '';
    document.getElementById('image_url').value = '';
    document.getElementById('legacyModal').style.display = 'flex';
}

function openEditModal(detail) {
    document.getElementById('modalTitle').textContent = 'Edit Legacy Detail';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('detailId').value = detail.id;
    document.getElementById('vehicle_id').value = detail.vehicle_id;
    document.getElementById('generation_name').value = detail.generation_name;
    document.getElementById('years').value = detail.years;
    document.getElementById('history_text').value = detail.history_text;
    document.getElementById('image_url').value = detail.image_url;
    document.getElementById('legacyModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('legacyModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('legacyModal');
    if (event.target == modal) closeModal();
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>