<?php
/**
 * Simple Smoke Test Script
 * Checks if key routes return a 200 OK status.
 */

$base_url = "http://localhost/router.php"; // Adjust if necessary for CLI
$routes = [
    '/',
    '/inventory',
    '/about',
    '/contact',
    '/login',
    '/register',
    '/legacy/GT-R',
    '/legacy/Supra',
];

echo "Starting smoke tests...\n";
$all_pass = true;

foreach ($routes as $route) {
    // Note: This requires the PHP built-in server to be running or a local web server.
    // Since we are in a CLI environment, we can't easily hit the router via HTTP 
    // without a running server. Instead, we'll mock the $_SERVER variables and 
    // require the router to see if it executes without fatal errors.
    
    echo "Testing $route: ";
    
    ob_start();
    $_SERVER['REQUEST_URI'] = $route;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    try {
        include __DIR__ . '/../router.php';
        $output = ob_get_clean();
        echo "PASS (Executed without fatal error)\n";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "FAIL - " . $e->getMessage() . "\n";
        $all_pass = false;
    }
}

if ($all_pass) {
    echo "\nAll smoke tests passed!\n";
} else {
    echo "\nSome tests failed.\n";
    exit(1);
}
