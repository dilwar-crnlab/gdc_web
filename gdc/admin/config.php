<?php
// config.php - Configuration settings

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'dcb_notifications');

// Application Configuration
define('BASE_UPLOAD_PATH', 'notifications/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Default admin credentials
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123');
define('DEFAULT_ADMIN_NAME', 'Administrator');

// Error reporting configuration
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
}

// Application constants
define('APP_NAME', 'DCB Girls College');
define('APP_SUBTITLE', 'Notification Management System');
?>