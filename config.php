<?php
define('ROOT_PATH', __DIR__);
define('DB_HOST', 'localhost');
define('DB_NAME', 'multi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_ENV', 'local'); // local | production

// Error reporting (disable in production)
if (APP_ENV === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Secure session settings
session_start([
    'cookie_lifetime' => 3600,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Database connection
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

// Load Auth Middleware & Helpers globally
require_once __DIR__ . '/app/Helpers/AuthMiddleware.php';
require_once __DIR__ . '/api/helpers/brandinghelper.php';
require_once __DIR__ . '/app/middleware/superadminmiddleware.php';
?>