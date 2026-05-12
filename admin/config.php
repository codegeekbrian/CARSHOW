<?php
// Admin Configuration File
// Rename this file to config.php and update the values for production use

// Database location
define('DB_PATH', dirname(__DIR__) . '/data/carshow.db');

// Admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$aQg15Dj.dCMT6LdCZuVn/eN0ZITW2u45hLP0UWr.xRg1XaqeqAaf6'); // Default: password123

// For production, generate a new hash with:
// echo password_hash('your_password', PASSWORD_DEFAULT);
?>