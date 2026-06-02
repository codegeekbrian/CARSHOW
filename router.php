<?php
/**
 * Router for PHP built-in server.
 * Serves existing static files directly, sends all other requests to index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = __DIR__ . rawurldecode($uri);

if ($uri !== '/' && is_file($path)) {
    return false;
}

require __DIR__ . '/index.php';
