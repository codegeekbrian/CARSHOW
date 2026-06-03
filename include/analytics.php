<?php
// basic analytics logging
require_once __DIR__ . '/db.php';

if (!function_exists('logActivity')) {
    function logActivity($type, $target_id = null) {
        global $db;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $page_url = $_SERVER['REQUEST_URI'] ?? $_SERVER['REDIRECT_URL'] ?? '';
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sid = session_id();

        try {
            $stmt = $db->prepare("INSERT INTO analytics (type, target_id, page_url, ip_address, user_agent, session_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $target_id, $page_url, $ip, $ua, $sid]);
        } catch (PDOException $e) {
            error_log("Analytics error: " . $e->getMessage());
        }
    }
}
