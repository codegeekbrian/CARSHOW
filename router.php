<?php
/**
 * PHP Built-in Server Router
 * Handles clean URL routing for the CARSHOW application.
 * Replaces .htaccess/mod_rewrite for environments without Apache.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// ── Security Check ───────────────────────────────────────
$protected_dirs = ['/data', '/include', '/cache', '/tools'];
foreach ($protected_dirs as $dir) {
    if (str_starts_with($uri, $dir)) {
        http_response_code(403);
        die('Access Denied');
    }
}

if ($uri === '' || $uri === '/index.php') {
    require __DIR__ . '/index.php';

} elseif (str_starts_with($uri, '/legacy/')) {
    $parts = explode('/', trim($uri, '/'));
    if (isset($parts[1]) && $parts[1] !== '') {
        $_GET['model'] = urldecode($parts[1]);
    }
    require __DIR__ . '/pages/legacy_template.php';

} elseif ($uri === '/inventory') {
    require __DIR__ . '/pages/inventory.php';

} elseif ($uri === '/about') {
    require __DIR__ . '/pages/about.php';

} elseif ($uri === '/contact') {
    require __DIR__ . '/pages/contact.php';

} elseif ($uri === '/register') {
    require __DIR__ . '/pages/auth/register.php';

} elseif ($uri === '/login') {
    require __DIR__ . '/pages/auth/login.php';

} elseif ($uri === '/logout') {
    require __DIR__ . '/pages/auth/logout.php';

} elseif (file_exists(__DIR__ . $uri)) {
    return false;

} else {
    require __DIR__ . '/pages/404.php';
}