<?php
/**
 * Configuration Example File
 * Copy this file to config.php and customize for your environment
 * 
 * IMPORTANT: Never commit config.php to version control!
 * This file contains sensitive database credentials.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// ENVIRONMENT DETECTION
// ========================================
$environment = $_SERVER['HTTP_HOST'] ?? 'localhost';

// ========================================
// DATABASE CONFIGURATION
// ========================================

// Development Environment (localhost)
if ($environment === 'localhost' || strpos($environment, '127.0.0.1') !== false) {
    $host = 'localhost';
    $database = 'fundraising_db';
    $username = 'root';
    $password = '';
    $debug_mode = true;
    $error_reporting = true;
}

// Production Environment
elseif ($environment === 'your-production-domain.com' || strpos($environment, 'your-production-domain.com') !== false) {
    $host = 'production-db-host';
    $database = 'production_fundraising_db';
    $username = 'production_user';
    $password = 'production_password';
    $debug_mode = false;
    $error_reporting = false;
}

// Staging Environment
elseif ($environment === 'staging.your-domain.com' || strpos($environment, 'staging') !== false) {
    $host = 'staging-db-host';
    $database = 'staging_fundraising_db';
    $username = 'staging_user';
    $password = 'staging_password';
    $debug_mode = true;
    $error_reporting = true;
}

// Default to development
else {
    $host = 'localhost';
    $database = 'fundraising_db';
    $username = 'root';
    $password = '';
    $debug_mode = true;
    $error_reporting = true;
}

// ========================================
// ERROR REPORTING CONFIGURATION
// ========================================
if ($error_reporting) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// ========================================
// DATABASE CONNECTION
// ========================================
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    if ($debug_mode) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

// ========================================
// APPLICATION CONFIGURATION
// ========================================
define('APP_NAME', 'Fundraising System');
define('APP_VERSION', '1.0.0');
define('APP_ENVIRONMENT', $environment);
define('DEBUG_MODE', $debug_mode);

// ========================================
// SECURITY CONFIGURATION
// ========================================

// CSRF Protection Functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $headers = getallheaders();
        $csrf_token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
        
        if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            if (DEBUG_MODE) {
                echo json_encode(['error' => 'CSRF token mismatch']);
            } else {
                echo json_encode(['error' => 'Security validation failed']);
            }
            exit();
        }
    }
}

// Helper function untuk mendapatkan CSRF token untuk HTML forms
function get_csrf_token_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// Helper function untuk mendapatkan CSRF token untuk meta tag
function get_csrf_token_meta() {
    return '<meta name="csrf-token" content="' . generate_csrf_token() . '">';
}

// ========================================
// LOGGING CONFIGURATION
// ========================================
if (!DEBUG_MODE) {
    // Production logging
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/log/php/error.log');
} else {
    // Development logging
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// ========================================
// ENVIRONMENT INFO (for debugging)
// ========================================
if (DEBUG_MODE) {
    error_log("Application started in " . APP_ENVIRONMENT . " mode");
    error_log("Database connected to: $host/$database");
}
?>