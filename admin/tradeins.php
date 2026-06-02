<?php
require_once __DIR__ . '/admin_db.php';
require_permission('trade_ins.manage');
require_csrf();

$message = '';
$statuses = ['new', 'reviewing', 'valued', 'accepted', 'declined', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_trade') {
    $id = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'new';
    $assignedId = (int)($_POST['assigned_admin_user_id'] ?? 0);
    $note = trim($_POST['admin_note'] ?? '');

    $stmt = $db->prepare('
        UPDATE trade_in_requests
        SET status = ?, assigned_admin_user_id = ?, admin_note = ?
        WHERE id = ?
    ');
    $stmt->execute([$status, $assignedId > 0 ? $assignedId : null, $note !== '' ? $note : null, $id]);
    recordAdminAuditLog($db, 'trade_in.updated', 'trade_in_request', $id, "Updated trade-in request #{$id}");
    $message = 'Trade-in request updated.';
}

$staff = $db->query('
    SELECT u.id, COALESCE(u.display_name, u.username) AS name, r.name AS role_name
    FROM admin_users u
    JOIN admin_roles r ON r.id = u.role_id
    WHERE u.is_active = 1
    ORDER BY r.level DESC, name
')->fetchAll(PDO::FETCH_ASSOC);

$requests = $db->query('
    SELECT t.*, v.model AS target_model, b.name AS target_brand, u.display_name AS assigned_name, u.username AS assigned_username
    FROM trade_in_requests t
    LEFT JOIN vehicles v ON v.id = t.vehicle_id
    LEFT JOIN brands b ON b.id = v.brand_id
    LEFT JOIN admin_users u ON u.id = t.assigned_admin_user_id
    ORDER BY FIELD(t.status, "new", "reviewing", "valued", "accepted", "declined", "closed"), t.created_at DESC
')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Trade-ins';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <header class="admin-page-head">
            <div>
                <p class="admin-kicker">Buying desk</p>
                <h2 class="section-title">Trade-ins</h2>
            </div>
        </header>

        <?php if ($message): ?><div class="message success"><?php echo h($message); ?></div><?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="empty-state admin-empty">
                <h3>No trade-in requests</h3>
                <p>Customer trade-in requests will appear here for valuation and follow-up.</p>
            </div>
        <?php else: ?>
            <div class="lead-board">
                <?php foreach ($requests as $request): ?>
                    <article class="lead-card status-<?php echo h($request['status']); ?>">
                        <div class="lead-card-head">
                            <span class="status-badge status-<?php echo h($request['status']); ?>"><?php echo h(ucfirst($request['status'])); ?></span>
                            <small><?php echo h($request['created_at']); ?></small>
                        </div>
                        <h3><?php echo h($request['vehicle_make'] . ' ' . $request['vehicle_model']); ?></h3>
                        <p><?php echo h($request['vehicle_year'] ?: 'Year not provided'); ?><?php echo $request['mileage'] ? ' / ' . h($request['mileage']) : ''; ?></p>
                        <p><strong><?php echo h($request['customer_name']); ?></strong><br><small><?php echo h($request['customer_email']); ?><?php echo $request['customer_phone'] ? ' / ' . h($request['customer_phone']) : ''; ?></small></p>
                        <?php if (!empty($request['target_model'])): ?>
                            <p class="text-muted">Interested in <?php echo h($request['target_brand'] . ' ' . $request['target_model']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($request['expected_value'])): ?>
                            <p>KES <?php echo number_format((float)$request['expected_value']); ?> expected value</p>
                        <?php endif; ?>
                        <?php if (!empty($request['message'])): ?><p class="text-muted"><?php echo h($request['message']); ?></p><?php endif; ?>

                        <form method="POST" class="lead-note-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="update_trade">
                            <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
                            <label for="status-<?php echo (int)$request['id']; ?>">Status</label>
                            <select id="status-<?php echo (int)$request['id']; ?>" name="status" class="compact-select">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo h($status); ?>" <?php echo $request['status'] === $status ? 'selected' : ''; ?>><?php echo h(ucfirst($status)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="assigned-<?php echo (int)$request['id']; ?>">Assigned staff</label>
                            <select id="assigned-<?php echo (int)$request['id']; ?>" name="assigned_admin_user_id" class="compact-select">
                                <option value="">Unassigned</option>
                                <?php foreach ($staff as $person): ?>
                                    <option value="<?php echo (int)$person['id']; ?>" <?php echo (int)$request['assigned_admin_user_id'] === (int)$person['id'] ? 'selected' : ''; ?>><?php echo h($person['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="note-<?php echo (int)$request['id']; ?>">Internal note</label>
                            <textarea id="note-<?php echo (int)$request['id']; ?>" name="admin_note" rows="2"><?php echo h($request['admin_note'] ?? ''); ?></textarea>
                            <button type="submit" class="btn btn-sm">Save trade-in</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
