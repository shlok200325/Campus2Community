<?php
/**
 * Campus2Community Jharkhand Civic Portal
 * Global System Configuration
 */

if (!defined('C2C_PORTAL')) {
    define('C2C_PORTAL', true);
}

// Database Credentials (Default WAMP / XAMPP settings)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'c2c_portal');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Application Settings
define('APP_NAME', 'Campus2Community Jharkhand');
define('APP_VERSION', '3.0.0-PHP-MYSQL');
define('BASE_URL', '/');

// Start PHP Session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
