<?php
require_once __DIR__ . '/env_loader.php';

if (!function_exists('start_app_session')) {
    function start_app_session(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $currentPath = session_save_path();
        $sessionPath = $currentPath !== '' && is_dir($currentPath) && is_writable($currentPath)
            ? $currentPath
            : sys_get_temp_dir();

        session_save_path($sessionPath);

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        @session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool)$secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    start_app_session();
}

define('DEBUG_MODE', (bool) ($_ENV['DEBUG_MODE'] ?? false));
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/input_helpers.php';

define('APP_NAME', $_ENV['APP_NAME'] ?? 'Own It & Drive It');
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '1.1.0');

define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Own It & Drive It');
define('SITE_LOCATION', $_ENV['SITE_LOCATION'] ?? 'Nairobi, Kenya');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost:3000/');

$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$base = (strpos($script_name, 'index.php') !== false)
    ? str_replace('index.php', '', $script_name)
    : '/';
define('BASE_PATH', rtrim($base, '/') . '/');

function url($path = '') {
    $path = ltrim((string)$path, '/');
    if ($path === '' || $path === '/') {
        return BASE_PATH;
    }
    return BASE_PATH . $path;
}

define('DB_PATH', __DIR__ . '/../data/' . ($_ENV['DB_FILENAME'] ?? 'carshow.db'));
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_DURATION', 3600);

define('CONTACT_EMAIL', $_ENV['CONTACT_EMAIL'] ?? 'info@ownitdriveit.co.ke');
define('NOREPLY_EMAIL', $_ENV['NOREPLY_EMAIL'] ?? 'noreply@ownitdriveit.co.ke');

define('SESSION_TIMEOUT', (int) ($_ENV['SESSION_TIMEOUT'] ?? 7200));
define('RATE_LIMIT_MAX', 5);
define('RATE_LIMIT_WINDOW', 3600);
define('STORAGE_DIR', __DIR__ . '/storage/');
define('MESSAGES_FILE', STORAGE_DIR . 'messages.json');

define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? 'admin');
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '');
