<?php
/**
 * Sales desk helpers for admin lead, customer, and vehicle status workflows.
 */

function salesDeskAllowedLeadStatuses(): array
{
    return ['new', 'contacted', 'appointment', 'reserved', 'won', 'lost'];
}

function salesDeskAllowedVehicleStatuses(): array
{
    return ['available', 'reserved', 'sold'];
}

function normalizeSalesDeskStatus(string $status): string
{
    return in_array($status, salesDeskAllowedLeadStatuses(), true) ? $status : 'new';
}

function findOrCreateCustomer(PDO $db, string $name, string $email, ?string $phone = null): int
{
    $name = trim($name);
    $email = trim(strtolower($email));
    $phone = trim((string)$phone);

    if ($name === '' || $email === '') {
        throw new InvalidArgumentException('Customer name and email are required.');
    }

    $stmt = $db->prepare('SELECT id FROM customers WHERE email = ? ORDER BY id LIMIT 1');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();

    if ($id !== false) {
        $update = $db->prepare('UPDATE customers SET name = ?, phone = COALESCE(NULLIF(?, ""), phone), updated_at = current_timestamp() WHERE id = ?');
        $update->execute([$name, $phone, $id]);
        return (int)$id;
    }

    $insert = $db->prepare('INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)');
    $insert->execute([$name, $email, $phone !== '' ? $phone : null]);
    return (int)$db->lastInsertId();
}

function findAdminUserIdByUsername(PDO $db, string $username): ?int
{
    $stmt = $db->prepare('SELECT id FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int)$id : null;
}

function createLeadFromAppointment(PDO $db, int $appointmentId, string $adminUser): int
{
    $stmt = $db->prepare('
        SELECT *
        FROM appointments
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        throw new InvalidArgumentException('Appointment not found.');
    }

    $existing = $db->prepare('SELECT id FROM sales_leads WHERE appointment_id = ? ORDER BY id LIMIT 1');
    $existing->execute([$appointmentId]);
    $existingId = $existing->fetchColumn();
    if ($existingId !== false) {
        return (int)$existingId;
    }

    $customerId = findOrCreateCustomer(
        $db,
        $appointment['customer_name'] ?? '',
        $appointment['customer_email'] ?? '',
        $appointment['customer_phone'] ?? null
    );

    $insert = $db->prepare('
        INSERT INTO sales_leads
            (customer_id, vehicle_id, branch_id, appointment_id, source, status, priority, assigned_to, assigned_admin_user_id, summary, last_activity_at)
        VALUES
            (?, ?, ?, ?, "appointment", "new", "normal", ?, ?, ?, current_timestamp())
    ');
    $insert->execute([
        $customerId,
        (int)$appointment['vehicle_id'],
        $appointment['branch_id'] ? (int)$appointment['branch_id'] : null,
        $appointmentId,
        $adminUser,
        findAdminUserIdByUsername($db, $adminUser),
        $appointment['message'] ?? null,
    ]);

    $leadId = (int)$db->lastInsertId();
    addLeadNote($db, $leadId, $adminUser, 'Lead created from appointment request.');
    return $leadId;
}

function createLeadFromMessage(PDO $db, int $messageId, string $adminUser): int
{
    $stmt = $db->prepare('SELECT * FROM messages WHERE id = ? LIMIT 1');
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new InvalidArgumentException('Message not found.');
    }

    $existing = $db->prepare('SELECT id FROM sales_leads WHERE message_id = ? ORDER BY id LIMIT 1');
    $existing->execute([$messageId]);
    $existingId = $existing->fetchColumn();
    if ($existingId !== false) {
        return (int)$existingId;
    }

    $customerId = findOrCreateCustomer($db, $message['name'] ?? '', $message['email'] ?? '');
    $insert = $db->prepare('
        INSERT INTO sales_leads
            (customer_id, vehicle_id, branch_id, message_id, source, status, priority, assigned_to, assigned_admin_user_id, summary, last_activity_at)
        VALUES
            (?, ?, ?, ?, "inquiry", "new", "normal", ?, ?, ?, current_timestamp())
    ');
    $insert->execute([
        $customerId,
        $message['vehicle_id'] ? (int)$message['vehicle_id'] : null,
        $message['branch_id'] ? (int)$message['branch_id'] : null,
        $messageId,
        $adminUser,
        findAdminUserIdByUsername($db, $adminUser),
        $message['subject'] ?: ($message['message'] ?? null),
    ]);

    $leadId = (int)$db->lastInsertId();
    addLeadNote($db, $leadId, $adminUser, 'Lead created from customer inquiry.');
    return $leadId;
}

function addLeadNote(PDO $db, int $leadId, string $createdBy, string $note): void
{
    $note = trim($note);
    if ($note === '') {
        return;
    }

    $stmt = $db->prepare('INSERT INTO lead_notes (lead_id, created_by, note) VALUES (?, ?, ?)');
    $stmt->execute([$leadId, $createdBy, $note]);

    $touch = $db->prepare('UPDATE sales_leads SET last_activity_at = current_timestamp(), updated_at = current_timestamp() WHERE id = ?');
    $touch->execute([$leadId]);
}

function updateLeadStatus(PDO $db, int $leadId, string $status): void
{
    $status = normalizeSalesDeskStatus($status);
    $stmt = $db->prepare('UPDATE sales_leads SET status = ?, last_activity_at = current_timestamp(), updated_at = current_timestamp() WHERE id = ?');
    $stmt->execute([$status, $leadId]);
}

function updateLeadPriority(PDO $db, int $leadId, string $priority): void
{
    $allowed = ['low', 'normal', 'high'];
    $priority = in_array($priority, $allowed, true) ? $priority : 'normal';
    $stmt = $db->prepare('UPDATE sales_leads SET priority = ?, updated_at = current_timestamp() WHERE id = ?');
    $stmt->execute([$priority, $leadId]);
}

function updateLeadAssignment(PDO $db, int $leadId, ?int $adminUserId, ?string $nextFollowUpAt): void
{
    $assignedName = null;
    if ($adminUserId && $adminUserId > 0) {
        $stmt = $db->prepare('SELECT COALESCE(display_name, username) FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$adminUserId]);
        $assignedName = $stmt->fetchColumn() ?: null;
    } else {
        $adminUserId = null;
    }

    $nextFollowUpAt = trim((string)$nextFollowUpAt);
    if ($nextFollowUpAt !== '') {
        $timestamp = strtotime($nextFollowUpAt);
        $nextFollowUpAt = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    } else {
        $nextFollowUpAt = null;
    }

    $stmt = $db->prepare('
        UPDATE sales_leads
        SET assigned_admin_user_id = ?, assigned_to = ?, next_follow_up_at = ?, updated_at = current_timestamp()
        WHERE id = ?
    ');
    $stmt->execute([$adminUserId, $assignedName, $nextFollowUpAt, $leadId]);
}

function changeVehicleStatus(PDO $db, int $vehicleId, string $newStatus, string $changedBy, ?string $note = null): void
{
    if (!in_array($newStatus, salesDeskAllowedVehicleStatuses(), true)) {
        throw new InvalidArgumentException('Invalid vehicle status.');
    }

    $stmt = $db->prepare('SELECT status FROM vehicles WHERE id = ? LIMIT 1');
    $stmt->execute([$vehicleId]);
    $oldStatus = $stmt->fetchColumn();

    if ($oldStatus === false) {
        throw new InvalidArgumentException('Vehicle not found.');
    }

    $update = $db->prepare('UPDATE vehicles SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $vehicleId]);

    $history = $db->prepare('
        INSERT INTO vehicle_status_history (vehicle_id, old_status, new_status, changed_by, note)
        VALUES (?, ?, ?, ?, ?)
    ');
    $history->execute([$vehicleId, $oldStatus, $newStatus, $changedBy, $note]);
}
