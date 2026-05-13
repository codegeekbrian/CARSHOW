<?php
/**
 * Simple Analytics Logger
 */
require_once __DIR__ . '/db.php';

function logActivity($type, $target_id = null) {
    global $db;
    
    $page_url = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {
        $stmt = $db->prepare("INSERT INTO analytics (type, target_id, page_url, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$type, $target_id, $page_url, $ip, $ua]);
    } catch (PDOException $e) {
        // Silently fail to not break the user experience
        error_log("Analytics error: " . $e->getMessage());
    }
}
