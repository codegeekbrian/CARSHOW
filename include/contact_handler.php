<?php
/**
 * Contact form handler — saves messages to storage/messages.json
 * Also sends an email if configured.
 */

// Include CSRF protection
require_once 'csrf.php';

// Allow only POST from same origin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
    exit;
}

// Rate limiting: max 5 messages per IP per hour
$rate_file = __DIR__ . '/storage/rate_limit.json';
if (file_exists($rate_file)) {
    $rates = json_decode(file_get_contents($rate_file), true) ?: [];
    $cutoff = time() - 3600;
    $recent = array_filter($rates, fn($t) => $t > $cutoff);
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $my_count = count(array_filter($recent, fn($t) => ($t['ip'] ?? '') === $ip));
    if ($my_count >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many messages. Please wait an hour before sending another.']);
        exit;
    }
    $recent[] = ['ip' => $ip, 'time' => time()];
    file_put_contents($rate_file, json_encode($recent));
}

// Validate input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'General Inquiry');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (strlen($name) < 2)  $errors[] = 'Name must be at least 2 characters.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Save message
$entry = [
    'id'      => uniqid('msg_', true),
    'name'    => $name,
    'email'   => $email,
    'subject' => $subject,
    'message' => $message,
    'ip'      => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
    'date'    => date('Y-m-d H:i:s'),
];

$storage_dir = __DIR__ . '/storage';
if (!is_dir($storage_dir)) mkdir($storage_dir, 0755, true);

$messages_file = $storage_dir . '/messages.json';
$existing = file_exists($messages_file) ? json_decode(file_get_contents($messages_file), true) : [];
array_unshift($existing, $entry);
file_put_contents($messages_file, json_encode($existing, JSON_PRETTY_PRINT));

// Optional: send email notification
// Change these to match your actual email
$to_email = 'info@ownitdriveit.co.ke';
$mail_subject = "New Contact Form Message: " . htmlspecialchars($subject);
$mail_body = "From: {$entry['name']}\nEmail: {$entry['email']}\nDate: {$entry['date']}\n\n{$entry['message']}";
$headers = "From: noreply@ownitdriveit.co.ke\r\nReply-To: {$entry['email']}\r\nContent-Type: text/plain; charset=UTF-8";

@mail($to_email, $mail_subject, $mail_body, $headers);

echo json_encode(['success' => true, 'message' => "Thank you, {$entry['name']}. Your message has been received and we'll reply to " . htmlspecialchars($email) . " shortly."]);
