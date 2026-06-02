<?php
/**
 * Contact Form Handler (MySQL)
 * Validates input, saves to database, and returns JSON.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// ── Validation ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh and try again.']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'General Inquiry');
$message = trim($_POST['message'] ?? '');
$inquiryType = trim($_POST['inquiry_type'] ?? 'general') === 'appointment' ? 'appointment' : 'general';
$vehicleId = max(0, (int)($_POST['vehicle_id'] ?? 0));
$branchId = max(0, (int)($_POST['branch_id'] ?? 0));
$phone = trim($_POST['phone'] ?? '');
$preferredDate = trim($_POST['preferred_date'] ?? '');
$preferredTime = trim($_POST['preferred_time'] ?? '');
$ip      = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// ── Database Storage ─────────────────────────────────────
try {
    $appointmentId = null;

    if ($inquiryType === 'appointment') {
        if ($vehicleId < 1) {
            echo json_encode(['success' => false, 'message' => 'Please choose a vehicle before booking a viewing.']);
            exit;
        }

        $vehicleStmt = $db->prepare('SELECT branch_id FROM vehicles WHERE id = ? AND is_inventory = 1 LIMIT 1');
        $vehicleStmt->execute([$vehicleId]);
        $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'The selected vehicle is no longer available.']);
            exit;
        }

        if ($branchId < 1 && !empty($vehicle['branch_id'])) {
            $branchId = (int)$vehicle['branch_id'];
        }

        $appointment = $db->prepare("
            INSERT INTO appointments
                (vehicle_id, branch_id, customer_name, customer_email, customer_phone, preferred_date, preferred_time, message, ip_address)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $appointment->execute([
            $vehicleId,
            $branchId > 0 ? $branchId : null,
            $name,
            $email,
            $phone !== '' ? $phone : null,
            $preferredDate !== '' ? $preferredDate : null,
            $preferredTime !== '' ? $preferredTime : null,
            $message,
            $ip,
        ]);
        $appointmentId = (int)$db->lastInsertId();
    }

    $stmt = $db->prepare("
        INSERT INTO messages
            (name, email, subject, message, inquiry_type, vehicle_id, branch_id, appointment_id, ip_address)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $email,
        $subject,
        $message,
        $inquiryType,
        $vehicleId > 0 ? $vehicleId : null,
        $branchId > 0 ? $branchId : null,
        $appointmentId,
        $ip,
    ]);

    $responseMessage = $inquiryType === 'appointment'
        ? "Thank you, {$name}. Your viewing request has been received. We'll confirm the branch appointment shortly."
        : "Thank you, {$name}. Your message has been received. We'll reply to {$email} shortly.";

    echo json_encode([
        'success' => true,
        'message' => $responseMessage
    ]);
} catch (PDOException $e) {
    error_log("Contact form DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while saving your message. Please try again later.']);
}
