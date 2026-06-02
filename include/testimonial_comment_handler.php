<?php
/**
 * Testimonial Comment Handler
 * Saves public comments as inactive testimonials for admin approval.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh and try again.']);
    exit;
}

$name = trim((string) ($_POST['client_name'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$quote = trim((string) ($_POST['quote'] ?? ''));

if ($name === '' || $quote === '') {
    echo json_encode(['success' => false, 'message' => 'Please add your name and comment.']);
    exit;
}

if (mb_strlen($name) > 120 || mb_strlen($location) > 120 || mb_strlen($quote) > 700) {
    echo json_encode(['success' => false, 'message' => 'Please keep your comment concise.']);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO testimonials (client_name, location, quote, is_active)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$name, $location, $quote]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you. Your comment is pending review.'
    ]);
} catch (PDOException $e) {
    error_log('Testimonial comment DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save your comment. Please try again later.']);
}
