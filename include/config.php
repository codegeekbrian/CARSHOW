<?php
require_once __DIR__ . '/env_loader.php';

define('APP_NAME', $_ENV['APP_NAME'] ?? 'CARSHOW');
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '1.0.0');

define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Own It & Drive It');
define('SITE_LOCATION', $_ENV['SITE_LOCATION'] ?? 'Nairobi, Kenya');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/CARSHOW/');

define('DB_PATH', __DIR__ . '/../data/' . ($_ENV['DB_FILENAME'] ?? 'carshow.db'));
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_DURATION', 3600);

define('CONTACT_EMAIL', $_ENV['CONTACT_EMAIL'] ?? 'info@ownitdriveit.co.ke');
define('NOREPLY_EMAIL', $_ENV['NOREPLY_EMAIL'] ?? 'noreply@ownitdriveit.co.ke');

define('SESSION_TIMEOUT', 7200);
define('RATE_LIMIT_MAX', 5);
define('RATE_LIMIT_WINDOW', 3600);
define('STORAGE_DIR', __DIR__ . '/storage/');
define('MESSAGES_FILE', STORAGE_DIR . 'messages.json');