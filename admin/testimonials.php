<?php
require_once __DIR__ . '/admin_db.php';
require_permission('testimonials.manage');
require_csrf();

$message = '';

function excerptText(string $text, int $length = 80): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $length);
    }
    return substr($text, 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $vehicleId = ($_POST['vehicle_id'] ?? '') !== '' ? (int)$_POST['vehicle_id'] : null;
    $verifiedBuyer = isset($_POST['verified_buyer']) ? 1 : 0;

    switch ($_POST['action']) {
        case 'add':
            $stmt = $db->prepare("INSERT INTO testimonials (client_name, location, verified_buyer, vehicle_id, quote) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$_POST['client_name'], $_POST['location'], $verifiedBuyer, $vehicleId, $_POST['quote']])) {
                $message = "Testimonial added successfully!";
            } else {
                $message = "Error adding testimonial.";
            }
            break;

        case 'edit':
            $stmt = $db->prepare("UPDATE testimonials SET client_name = ?, location = ?, verified_buyer = ?, vehicle_id = ?, quote = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$_POST['client_name'], $_POST['location'], $verifiedBuyer, $vehicleId, $_POST['quote'], isset($_POST['is_active']) ? 1 : 0, $_POST['id']])) {
                $message = "Testimonial updated successfully!";
            } else {
                $message = "Error updating testimonial.";
            }
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM testimonials WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = "Testimonial deleted successfully!";
            } else {
                $message = "Error deleting testimonial.";
            }
            break;

        case 'toggle':
            $stmt = $db->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Testimonial status toggled.";
            break;
    }
}

$stmt = $db->query("
    SELECT t.*, CONCAT(COALESCE(b.name, ''), ' ', COALESCE(v.model, '')) AS vehicle_label
    FROM testimonials t
    LEFT JOIN vehicles v ON v.id = t.vehicle_id
    LEFT JOIN brands b ON b.id = v.brand_id
    ORDER BY t.created_at DESC
");
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
$vehicles = $db->query("
    SELECT v.id, CONCAT(b.name, ' ', v.model, ' ', COALESCE(v.year, '')) AS label
    FROM vehicles v
    JOIN brands b ON b.id = v.brand_id
    WHERE v.is_inventory = 1
    ORDER BY b.name, v.model
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Testimonials';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">
            Client Testimonials
            <button type="button" class="btn btn-success" onclick="openAddModal()">Add New Testimonial</button>
        </h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
        <table class="admin-table-stack">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Location</th>
                    <th>Buyer</th>
                    <th>Quote</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testimonials as $t): ?>
                <tr>
                    <td data-label="Client Name"><?php echo htmlspecialchars($t['client_name']); ?></td>
                    <td data-label="Location"><?php echo htmlspecialchars($t['location'] ?? '-'); ?></td>
                    <td data-label="Buyer">
                        <?php if (!empty($t['verified_buyer'])): ?>
                            <span class="status-badge status-read">Verified</span>
                            <small><?php echo h(trim((string)($t['vehicle_label'] ?? '')) ?: 'Vehicle not linked'); ?></small>
                        <?php else: ?>
                            <span class="text-muted">Unverified</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Quote"><?php echo htmlspecialchars(excerptText($t['quote'], 80)) . (strlen($t['quote']) > 80 ? '...' : ''); ?></td>
                    <td data-label="Status">
                        <span class="status-badge <?php echo $t['is_active'] ? 'status-read' : 'status-unread'; ?>">
                            <?php echo $t['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td data-label="Actions">
                        <div class="admin-row-actions">
                            <button type="button" class="btn btn-sm" data-testimonial='<?php echo h(json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>' data-edit-testimonial>Edit</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-sm"><?php echo $t['is_active'] ? 'Disable' : 'Enable'; ?></button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this testimonial?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div id="testimonialModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true" tabindex="-1">
    <div class="modal-content">
        <button type="button" class="close" onclick="closeModal()" aria-label="Close testimonial editor">&times;</button>
        <h2 id="modalTitle">Add New Testimonial</h2>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="testimonialId">

            <div class="form-group">
                <label for="client_name">Client Name</label>
                <input type="text" id="client_name" name="client_name" required>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Nairobi">
            </div>

            <div class="form-group">
                <label for="quote">Quote</label>
                <textarea id="quote" name="quote" rows="4" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="admin-checkline admin-switch">
                        <input type="checkbox" id="verified_buyer" name="verified_buyer">
                        <span aria-hidden="true"></span>
                        Verified Buyer
                    </label>
                </div>
                <div class="form-group">
                    <label for="vehicle_id">Purchased vehicle</label>
                    <select id="vehicle_id" name="vehicle_id">
                        <option value="">No vehicle linked</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo (int)$vehicle['id']; ?>"><?php echo h($vehicle['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="admin-checkline admin-switch">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <span aria-hidden="true"></span>
                    Active (show on site)
                </label>
            </div>

            <div class="admin-modal-actions">
                <button type="submit" class="btn btn-success">Save Testimonial</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Testimonial';
    document.getElementById('formAction').value = 'add';
    document.getElementById('testimonialId').value = '';
    document.getElementById('client_name').value = '';
    document.getElementById('location').value = '';
    document.getElementById('quote').value = '';
    document.getElementById('verified_buyer').checked = false;
    document.getElementById('vehicle_id').value = '';
    document.getElementById('is_active').checked = true;
    openAdminModal('testimonialModal');
}

function openEditModal(t) {
    document.getElementById('modalTitle').textContent = 'Edit Testimonial';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('testimonialId').value = t.id;
    document.getElementById('client_name').value = t.client_name || '';
    document.getElementById('location').value = t.location || '';
    document.getElementById('quote').value = t.quote || '';
    document.getElementById('verified_buyer').checked = t.verified_buyer == 1;
    document.getElementById('vehicle_id').value = t.vehicle_id || '';
    document.getElementById('is_active').checked = t.is_active == 1;
    openAdminModal('testimonialModal');
}

document.querySelectorAll('[data-edit-testimonial]').forEach((button) => {
    button.addEventListener('click', () => {
        try {
            openEditModal(JSON.parse(button.dataset.testimonial || '{}'));
        } catch (error) {
            console.error('Could not open testimonial editor', error);
        }
    });
});

function closeModal() {
    closeAdminModal('testimonialModal');
}
</script>

<?php require_once __DIR__ . '/include/footer.php'; ?>
