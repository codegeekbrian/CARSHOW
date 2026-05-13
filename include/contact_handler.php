<?php
/**
 * Contact Form Handler
 * Validates input, applies rate limiting, saves to storage, and sends email notification.
 *
 * Expected POST fields: csrf_token, name, email, subject, message
 * Returns: JSON { success: bool, message: string }
 */

// Start session & load dependencies
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

// ── Method Check ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── CSRF Validation ─────────────────────────────────────
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
    exit;
}

// ── Rate Limiting ───────────────────────────────────────
$rate_file = STORAGE_DIR . 'rate_limit.json';
if (file_exists($rate_file)) {
    $rates  = json_decode(file_get_contents($rate_file), true) ?: [];
    $cutoff = time() - RATE_LIMIT_WINDOW;
    $recent = array_filter($rates, fn($t) => ($t['time'] ?? 0) > $cutoff);
    $ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $myCount = count(array_filter($recent, fn($t) => ($t['ip'] ?? '') === $ip));
    if ($myCount >= RATE_LIMIT_MAX) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many messages. Please wait an hour.']);
        exit;
    }
    $recent[] = ['ip' => $ip, 'time' => time()];
    if (!is_dir(STORAGE_DIR)) { mkdir(STORAGE_DIR, 0755, true); }
    file_put_contents($rate_file, json_encode(array_values($recent)));
} else {
    if (!is_dir(STORAGE_DIR)) { mkdir(STORAGE_DIR, 0755, true); }
    file_put_contents($rate_file, json_encode([['ip' => $ip, 'time' => time()]]));
}

// ── Input Validation ─────────────────────────────────────
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'General Inquiry');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (strlen($name) < 2)    { $errors[] = 'Name must be at least 2 characters.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
if (strlen($message) < 10) { $errors[] = 'Message must be at least 10 characters.'; }

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Save Message ────────────────────────────────────────
$entry = [
    'id'      => uniqid('msg_', true),
    'name'    => $name,
    'email'   => $email,
    'subject' => $subject,
    'message' => $message,
    'ip'      => $ip,
    'date'    => date('Y-m-d H:i:s'),
];

$existing = file_exists(MESSAGES_FILE) ? json_decode(file_get_contents(MESSAGES_FILE), true) : [];
array_unshift($existing, $entry);
file_put_contents(MESSAGES_FILE, json_encode($existing, JSON_PRETTY_PRINT));

// ── Email Notification ──────────────────────────────────
$mailSubject = "New Contact Form Message: " . htmlspecialchars($subject);
$mailBody    = "From: {$name}\nEmail: {$email}\nDate: {$entry['date']}\n\n{$message}";
$headers     = "From: " . NOREPLY_EMAIL . "\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8";
@mail(CONTACT_EMAIL, $mailSubject, $mailBody, $headers);

// ── Response ─────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => "Thank you, {$name}. Your message has been received. We'll reply to {$email} shortly."
]);