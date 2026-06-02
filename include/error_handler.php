<?php
/**
 * Global Error and Exception Handler
 */

// Define log file
define('ERROR_LOG_FILE', __DIR__ . '/../data/error.log');

/**
 * Handle uncaught exceptions
 */
function handleException($exception) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $message = "[" . date('Y-m-d H:i:s') . "] Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . " on line " . $exception->getLine() . " [IP: $ip] [URI: $uri]\n" . 
               $exception->getTraceAsString() . "\n\n";
    
    // Ensure log directory exists
    if (!file_exists(dirname(ERROR_LOG_FILE))) {
        @mkdir(dirname(ERROR_LOG_FILE), 0755, true);
    }
    error_log($message, 3, ERROR_LOG_FILE);
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<h1>An error occurred</h1>";
        echo "<pre>" . htmlspecialchars($message) . "</pre>";
    } else {
        http_response_code(500);
        include __DIR__ . '/../pages/500.php';
    }
    exit;
}

/**
 * Handle PHP errors
 */
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $message = "[" . date('Y-m-d H:i:s') . "] Error ($errno): $errstr in $errfile on line $errline\n";
    error_log($message, 3, ERROR_LOG_FILE);

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div class='error-debug-box'>";
        echo "<strong>PHP Error:</strong> $errstr in <em>$errfile</em> on line <em>$errline</em>";
        echo "</div>";
    }

    return true;
}

/**
 * Handle fatal errors (shutdown function)
 */
function handleShutdown() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $error['message'] . 
                   " in " . $error['file'] . " on line " . $error['line'] . "\n";
        error_log($message, 3, ERROR_LOG_FILE);
        
        if (!headers_sent()) {
            http_response_code(500);
            include __DIR__ . '/../pages/500.php';
        }
    }
}

// Register handlers
set_exception_handler('handleException');
set_error_handler('handleError');
register_shutdown_function('handleShutdown');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);
