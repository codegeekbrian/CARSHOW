<?php
require_once __DIR__ . '/admin_db.php';
require_once dirname(__DIR__) . '/include/sales_desk.php';
require_permission('sales.manage');

$message = '';
$adminUser = $_SESSION['admin_username'] ?? 'admin';
$quickLeadNotes = [
    'left_voicemail' => 'Left voicemail',
    'wrong_number' => 'Wrong number',
    'sent_whatsapp' => 'Sent WhatsApp',
    'asked_finance' => 'Customer asked for finance',
    'needs_follow_up' => 'Needs follow-up',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $action = isset($_POST['quick_note']) ? 'quick_note' : $_POST['action'];

    try {
        if ($action === 'create_from_appointment') {
            $leadId = createLeadFromAppointment($db, (int)($_POST['appointment_id'] ?? 0), $adminUser);
            $message = "Lead #{$leadId} created from appointment.";
        } elseif ($action === 'create_from_message') {
            $leadId = createLeadFromMessage($db, (int)($_POST['message_id'] ?? 0), $adminUser);
            $message = "Lead #{$leadId} created from inquiry.";
        } elseif ($action === 'update_status') {
            updateLeadStatus($db, (int)($_POST['lead_id'] ?? 0), $_POST['status'] ?? 'new');
            $message = 'Lead status updated.';
        } elseif ($action === 'update_priority') {
            updateLeadPriority($db, (int)($_POST['lead_id'] ?? 0), $_POST['priority'] ?? 'normal');
            $message = 'Lead priority updated.';
        } elseif ($action === 'add_note') {
            addLeadNote($db, (int)($_POST['lead_id'] ?? 0), $adminUser, $_POST['note'] ?? '');
            $message = 'Lead note added.';
        } elseif ($action === 'quick_note') {
            $quickKey = $_POST['quick_note'] ?? '';
            if (!isset($quickLeadNotes[$quickKey])) {
                throw new InvalidArgumentException('Choose a valid quick note.');
            }
            $leadId = (int)($_POST['lead_id'] ?? 0);
            addLeadNote($db, $leadId, $adminUser, $quickLeadNotes[$quickKey]);
            recordAdminAuditLog($db, 'lead.quick_note', 'sales_lead', $leadId, $quickLeadNotes[$quickKey]);
            $message = 'Quick note added.';
        } elseif ($action === 'update_assignment') {
            updateLeadAssignment($db, (int)($_POST['lead_id'] ?? 0), (int)($_POST['assigned_admin_user_id'] ?? 0), $_POST['next_follow_up_at'] ?? null);
            recordAdminAuditLog($db, 'lead.assigned', 'sales_lead', (int)($_POST['lead_id'] ?? 0), 'Updated lead assignment and follow-up.');
            $message = 'Lead assignment updated.';
        } elseif ($action === 'reserve_vehicle' || $action === 'mark_sold') {
            $leadId = (int)($_POST['lead_id'] ?? 0);
            $leadStmt = $db->prepare('SELECT vehicle_id FROM sales_leads WHERE id = ? LIMIT 1');
            $leadStmt->execute([$leadId]);
            $vehicleId = (int)$leadStmt->fetchColumn();
            if ($vehicleId <= 0) {
                throw new RuntimeException('This lead is not linked to a vehicle.');
            }
            $vehicleStatus = $action === 'reserve_vehicle' ? 'reserved' : 'sold';
            $leadStatus = $action === 'reserve_vehicle' ? 'reserved' : 'won';
            changeVehicleStatus($db, $vehicleId, $vehicleStatus, $adminUser, "Updated from lead #{$leadId}.");
            updateLeadStatus($db, $leadId, $leadStatus);
            $message = $action === 'reserve_vehicle' ? 'Vehicle reserved for lead.' : 'Vehicle marked sold from lead.';
        }
    } catch (Throwable $e) {
        $message = 'Action failed: ' . $e->getMessage();
    }
}

$leadStmt = $db->query("
    SELECT
        l.*,
        c.name AS customer_name,
        c.email AS customer_email,
        c.phone AS customer_phone,
        v.model,
        v.year,
        v.status AS vehicle_status,
        b.name AS brand_name,
        br.name AS branch_name,
        (
            SELECT COUNT(*)
            FROM lead_notes ln
            WHERE ln.lead_id = l.id
        ) AS note_count
    FROM sales_leads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    LEFT JOIN brands b ON v.brand_id = b.id
    LEFT JOIN branches br ON l.branch_id = br.id
    ORDER BY
        FIELD(l.status, 'new', 'contacted', 'appointment', 'reserved', 'won', 'lost'),
        FIELD(l.priority, 'high', 'normal', 'low'),
        COALESCE(l.last_activity_at, l.created_at) DESC
");
$leads = $leadStmt->fetchAll(PDO::FETCH_ASSOC);

$staff = $db->query('
    SELECT u.id, COALESCE(u.display_name, u.username) AS name, r.name AS role_name
    FROM admin_users u
    JOIN admin_roles r ON r.id = u.role_id
    WHERE u.is_active = 1
    ORDER BY r.level DESC, name
')->fetchAll(PDO::FETCH_ASSOC);

$appointments = $db->query("
    SELECT a.id, a.customer_name, a.customer_email, a.preferred_date, v.model, b.name AS brand_name
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN brands b ON v.brand_id = b.id
    LEFT JOIN sales_leads l ON l.appointment_id = a.id
    WHERE l.id IS NULL
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$messages = $db->query("
    SELECT m.id, m.name, m.email, m.subject, m.created_at
    FROM messages m
    LEFT JOIN sales_leads l ON l.message_id = m.id
    WHERE l.id IS NULL
    ORDER BY m.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Sales Leads';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <header class="admin-page-head">
            <div>
                <p class="admin-kicker">Sales desk</p>
                <h2 class="section-title">Leads</h2>
            </div>
            <a href="appointments.php" class="btn btn-secondary">Appointments</a>
        </header>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'failed') !== false ? 'error' : 'success'; ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid control-grid">
            <section class="admin-panel">
                <h3>Convert appointments</h3>
                <?php if (empty($appointments)): ?>
                    <p class="text-muted">No unassigned appointments.</p>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <form method="POST" class="admin-mini-row">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="create_from_appointment">
                            <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                            <span>
                                <strong><?php echo h($appointment['customer_name']); ?></strong>
                                <small><?php echo h($appointment['brand_name'] . ' ' . $appointment['model']); ?></small>
                            </span>
                            <button type="submit" class="btn btn-sm">Create Lead</button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="admin-panel">
                <h3>Convert inquiries</h3>
                <?php if (empty($messages)): ?>
                    <p class="text-muted">No unassigned inquiries.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <form method="POST" class="admin-mini-row">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="create_from_message">
                            <input type="hidden" name="message_id" value="<?php echo (int)$msg['id']; ?>">
                            <span>
                                <strong><?php echo h($msg['name']); ?></strong>
                                <small><?php echo h($msg['subject'] ?: $msg['email']); ?></small>
                            </span>
                            <button type="submit" class="btn btn-sm">Create Lead</button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>

        <?php if (empty($leads)): ?>
            <div class="empty-state admin-empty">
                <h3>No active leads yet</h3>
                <p>Convert an appointment or inquiry above to start tracking the customer journey.</p>
            </div>
        <?php else: ?>
            <div class="lead-board">
                <?php foreach ($leads as $lead): ?>
                    <article class="lead-card status-<?php echo h($lead['status']); ?>">
                        <div class="lead-card-head">
                            <div>
                                <span class="status-badge status-<?php echo h($lead['status']); ?>"><?php echo h(ucfirst($lead['status'])); ?></span>
                                <span class="status-badge priority-<?php echo h($lead['priority']); ?>"><?php echo h(ucfirst($lead['priority'])); ?></span>
                            </div>
                            <small><?php echo h($lead['source']); ?></small>
                        </div>
                        <h3><?php echo h($lead['customer_name']); ?></h3>
                        <p><?php echo h($lead['customer_email']); ?><?php echo $lead['customer_phone'] ? ' / ' . h($lead['customer_phone']) : ''; ?></p>
                        <p><strong><?php echo h(trim(($lead['brand_name'] ?? '') . ' ' . ($lead['model'] ?? '')) ?: 'No vehicle linked'); ?></strong></p>
                        <p class="text-muted"><?php echo h($lead['summary'] ?: 'No summary recorded.'); ?></p>
                        <p class="text-muted">
                            Assigned: <?php echo h($lead['assigned_to'] ?: 'Unassigned'); ?>
                            <?php if (!empty($lead['next_follow_up_at'])): ?>
                                / Follow-up <?php echo h($lead['next_follow_up_at']); ?>
                            <?php endif; ?>
                        </p>

                        <div class="lead-actions">
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                                <select name="status" class="compact-select" aria-label="Lead status">
                                    <?php foreach (salesDeskAllowedLeadStatuses() as $status): ?>
                                        <option value="<?php echo h($status); ?>" <?php echo $lead['status'] === $status ? 'selected' : ''; ?>><?php echo h(ucfirst($status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm">Save</button>
                            </form>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_priority">
                                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                                <select name="priority" class="compact-select" aria-label="Lead priority">
                                    <?php foreach (['low', 'normal', 'high'] as $priority): ?>
                                        <option value="<?php echo h($priority); ?>" <?php echo $lead['priority'] === $priority ? 'selected' : ''; ?>><?php echo h(ucfirst($priority)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm">Priority</button>
                            </form>
                        </div>

                        <form method="POST" class="lead-note-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="update_assignment">
                            <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                            <label for="assigned-<?php echo (int)$lead['id']; ?>">Assign to</label>
                            <select id="assigned-<?php echo (int)$lead['id']; ?>" name="assigned_admin_user_id" class="compact-select">
                                <option value="">Unassigned</option>
                                <?php foreach ($staff as $person): ?>
                                    <option value="<?php echo (int)$person['id']; ?>" <?php echo (int)$lead['assigned_admin_user_id'] === (int)$person['id'] ? 'selected' : ''; ?>><?php echo h($person['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="follow-<?php echo (int)$lead['id']; ?>">Next follow-up</label>
                            <input id="follow-<?php echo (int)$lead['id']; ?>" name="next_follow_up_at" type="datetime-local" value="<?php echo !empty($lead['next_follow_up_at']) ? h(date('Y-m-d\TH:i', strtotime($lead['next_follow_up_at']))) : ''; ?>">
                            <button type="submit" class="btn btn-sm">Assign</button>
                        </form>

                        <div class="lead-actions">
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="reserve_vehicle">
                                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                                <button type="submit" class="btn btn-sm" <?php echo empty($lead['vehicle_id']) ? 'disabled' : ''; ?>>Reserve</button>
                            </form>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_sold">
                                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm" <?php echo empty($lead['vehicle_id']) ? 'disabled' : ''; ?>>Mark Sold</button>
                            </form>
                        </div>

                        <form method="POST" class="lead-note-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="add_note">
                            <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                            <label for="note-<?php echo (int)$lead['id']; ?>">Add note</label>
                            <textarea id="note-<?php echo (int)$lead['id']; ?>" name="note" rows="2" placeholder="Call outcome, buyer preference, next step"></textarea>
                            <div class="lead-quick-notes" aria-label="Quick note options">
                                <?php foreach ($quickLeadNotes as $quickKey => $quickLabel): ?>
                                    <button type="submit" class="btn btn-sm" name="quick_note" value="<?php echo h($quickKey); ?>">
                                        <?php echo h($quickLabel); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-sm">Add Note</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
