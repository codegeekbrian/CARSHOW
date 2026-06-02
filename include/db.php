<?php
/**
 * Database Connection (MySQL)
 * Uses PDO to connect to the MySQL database.
 */
require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3307';
$dbname = $_ENV['DB_NAME'] ?? 'OWNIT_DRIVEIT';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    // Graceful error display or redirect to 500
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die('Database connection error: ' . $e->getMessage());
    } else {
        http_response_code(500);
        include __DIR__ . '/../pages/500.php';
        exit;
    }
}
