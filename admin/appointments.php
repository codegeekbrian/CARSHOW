<?php
require_once __DIR__ . '/admin_db.php';
require_permission('appointments.manage');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if ($_POST['action'] === 'status') {
        $allowed = ['new', 'confirmed', 'completed', 'cancelled'];
        $status = in_array($_POST['status'] ?? '', $allowed, true) ? $_POST['status'] : 'new';
        $stmt = $db->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        if ($stmt->execute([$status, $id])) {
            $message = 'Appointment updated.';
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare('DELETE FROM appointments WHERE id = ?');
        if ($stmt->execute([$id])) {
            $message = 'Appointment deleted.';
        }
    }
}

$stmt = $db->query("
    SELECT a.*, v.model, v.year, b.name AS brand_name, br.name AS branch_name
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN brands b ON v.brand_id = b.id
    LEFT JOIN branches br ON a.branch_id = br.id
    ORDER BY
        FIELD(a.status, 'new', 'confirmed', 'completed', 'cancelled'),
        a.created_at DESC
");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Viewing Appointments';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">Viewing Appointments</h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <p>No appointment requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table-stack">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Vehicle</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Preferred Time</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo h($appointment['status']); ?>">
                                    <?php echo h(ucfirst($appointment['status'])); ?>
                                </span>
                            </td>
                            <td data-label="Vehicle">
                                <strong><?php echo h($appointment['brand_name'] . ' ' . $appointment['model']); ?></strong><br>
                                <small><?php echo h($appointment['year']); ?></small>
                            </td>
                            <td data-label="Customer">
                                <strong><?php echo h($appointment['customer_name']); ?></strong><br>
                                <small><?php echo h($appointment['customer_email']); ?></small>
                                <?php if (!empty($appointment['customer_phone'])): ?><br><small><?php echo h($appointment['customer_phone']); ?></small><?php endif; ?>
                            </td>
                            <td data-label="Branch"><?php echo h($appointment['branch_name'] ?? 'Any branch'); ?></td>
                            <td data-label="Preferred Time">
                                <?php echo h($appointment['preferred_date'] ?: 'Not set'); ?><br>
                                <small><?php echo h($appointment['preferred_time'] ?: 'Flexible'); ?></small>
                            </td>
                            <td data-label="Message">
                                <div class="message-content-preview"><?php echo nl2br(h($appointment['message'] ?? '')); ?></div>
                            </td>
                            <td data-label="Actions" class="nowrap">
                                <form method="POST" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?php echo (int)$appointment['id']; ?>">
                                    <select name="status" class="compact-select">
                                        <?php foreach (['new', 'confirmed', 'completed', 'cancelled'] as $status): ?>
                                            <option value="<?php echo h($status); ?>" <?php echo $appointment['status'] === $status ? 'selected' : ''; ?>>
                                                <?php echo h(ucfirst($status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm">Save</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this appointment?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$appointment['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
