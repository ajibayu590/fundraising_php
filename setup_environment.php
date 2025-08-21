<?php
/**
 * Environment Setup Script
 * This script helps configure the application for different environments
 * 
 * Usage: php setup_environment.php [environment]
 * Environments: development, staging, production
 */

// Prevent direct access if not CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Get environment from command line argument
$environment = $argv[1] ?? 'development';

// Valid environments
$valid_environments = ['development', 'staging', 'production'];

if (!in_array($environment, $valid_environments)) {
    echo "❌ Invalid environment. Valid options: " . implode(', ', $valid_environments) . "\n";
    exit(1);
}

echo "🚀 Setting up environment: $environment\n\n";

// ========================================
// ENVIRONMENT CONFIGURATIONS
// ========================================

$configs = [
    'development' => [
        'host' => 'localhost',
        'database' => 'fundraising_db',
        'username' => 'root',
        'password' => '',
        'debug_mode' => true,
        'error_reporting' => true,
        'domain' => 'localhost'
    ],
    'staging' => [
        'host' => 'staging-db-host',
        'database' => 'staging_fundraising_db',
        'username' => 'staging_user',
        'password' => 'staging_password',
        'debug_mode' => true,
        'error_reporting' => true,
        'domain' => 'staging.your-domain.com'
    ],
    'production' => [
        'host' => 'production-db-host',
        'database' => 'production_fundraising_db',
        'username' => 'production_user',
        'password' => 'production_password',
        'debug_mode' => false,
        'error_reporting' => false,
        'domain' => 'your-production-domain.com'
    ]
];

$config = $configs[$environment];

// ========================================
// CREATE CONFIG.PHP
// ========================================

echo "📝 Creating config.php for $environment environment...\n";

$config_content = '<?php
/**
 * Configuration File for ' . ucfirst($environment) . ' Environment
 * Generated automatically by setup_environment.php
 * 
 * IMPORTANT: This file contains sensitive database credentials.
 * Never commit this file to version control!
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// ENVIRONMENT DETECTION
// ========================================
$environment = $_SERVER[\'HTTP_HOST\'] ?? \'localhost\';

// ========================================
// DATABASE CONFIGURATION
// ========================================

// ' . ucfirst($environment) . ' Environment
if ($environment === \'' . $config['domain'] . '\' || strpos($environment, \'' . $config['domain'] . '\') !== false) {
    $host = \'' . $config['host'] . '\';
    $database = \'' . $config['database'] . '\';
    $username = \'' . $config['username'] . '\';
    $password = \'' . $config['password'] . '\';
    $debug_mode = ' . ($config['debug_mode'] ? 'true' : 'false') . ';
    $error_reporting = ' . ($config['error_reporting'] ? 'true' : 'false') . ';
}

// Development Environment (localhost) - fallback
elseif ($environment === \'localhost\' || strpos($environment, \'127.0.0.1\') !== false) {
    $host = \'localhost\';
    $database = \'fundraising_db\';
    $username = \'root\';
    $password = \'\';
    $debug_mode = true;
    $error_reporting = true;
}

// Default to development
else {
    $host = \'localhost\';
    $database = \'fundraising_db\';
    $username = \'root\';
    $password = \'\';
    $debug_mode = true;
    $error_reporting = true;
}

// ========================================
// ERROR REPORTING CONFIGURATION
// ========================================
if ($error_reporting) {
    error_reporting(E_ALL);
    ini_set(\'display_errors\', 1);
    ini_set(\'display_startup_errors\', 1);
} else {
    error_reporting(0);
    ini_set(\'display_errors\', 0);
    ini_set(\'display_startup_errors\', 0);
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
define(\'APP_NAME\', \'Fundraising System\');
define(\'APP_VERSION\', \'1.0.0\');
define(\'APP_ENVIRONMENT\', $environment);
define(\'DEBUG_MODE\', $debug_mode);

// ========================================
// SECURITY CONFIGURATION
// ========================================

// CSRF Protection Functions
function generate_csrf_token() {
    if (empty($_SESSION[\'csrf_token\'])) {
        $_SESSION[\'csrf_token\'] = bin2hex(random_bytes(32));
    }
    return $_SESSION[\'csrf_token\'];
}

function check_csrf() {
    if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' || $_SERVER[\'REQUEST_METHOD\'] === \'PUT\' || $_SERVER[\'REQUEST_METHOD\'] === \'DELETE\') {
        $headers = getallheaders();
        $csrf_token = $headers[\'X-CSRF-Token\'] ?? $_POST[\'csrf_token\'] ?? \'\';
        
        if (!isset($_SESSION[\'csrf_token\']) || $csrf_token !== $_SESSION[\'csrf_token\']) {
            http_response_code(403);
            if (DEBUG_MODE) {
                echo json_encode([\'error\' => \'CSRF token mismatch\']);
            } else {
                echo json_encode([\'error\' => \'Security validation failed\']);
            }
            exit();
        }
    }
}

// Helper function untuk mendapatkan CSRF token untuk HTML forms
function get_csrf_token_field() {
    return \'<input type="hidden" name="csrf_token" value="\' . generate_csrf_token() . \'">\';
}

// Helper function untuk mendapatkan CSRF token untuk meta tag
function get_csrf_token_meta() {
    return \'<meta name="csrf-token" content="\' . generate_csrf_token() . \'">\';
}

// ========================================
// LOGGING CONFIGURATION
// ========================================
if (!DEBUG_MODE) {
    // Production logging
    ini_set(\'log_errors\', 1);
    ini_set(\'error_log\', \'/var/log/php/error.log\');
} else {
    // Development logging
    ini_set(\'log_errors\', 1);
    ini_set(\'error_log\', __DIR__ . \'/logs/error.log\');
}

// ========================================
// ENVIRONMENT INFO (for debugging)
// ========================================
if (DEBUG_MODE) {
    error_log("Application started in " . APP_ENVIRONMENT . " mode");
    error_log("Database connected to: $host/$database");
}
?>';

// Write config.php
if (file_put_contents('config.php', $config_content)) {
    echo "✅ config.php created successfully\n";
} else {
    echo "❌ Failed to create config.php\n";
    exit(1);
}

// ========================================
// CREATE DIRECTORIES
// ========================================

echo "\n📁 Creating necessary directories...\n";

$directories = [
    'logs',
    'uploads',
    'uploads/kunjungan',
    'uploads/logos',
    'temp',
    'cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "ℹ️  Directory already exists: $dir\n";
    }
}

// ========================================
// SET PERMISSIONS
// ========================================

echo "\n🔐 Setting file permissions...\n";

// Set permissions for uploads directory
if (is_dir('uploads')) {
    chmod('uploads', 0755);
    echo "✅ Set permissions for uploads directory\n";
}

// Set permissions for logs directory
if (is_dir('logs')) {
    chmod('logs', 0755);
    echo "✅ Set permissions for logs directory\n";
}

// ========================================
// CREATE .HTACCESS
// ========================================

echo "\n🔒 Creating .htaccess file...\n";

$htaccess_content = '# ========================================
# FUNDRAISING SYSTEM - HTACCESS
# ========================================

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent access to sensitive files
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

<Files ".env*">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Prevent directory listing
Options -Indexes

# Custom error pages
ErrorDocument 404 /404.php
ErrorDocument 500 /500.php

# URL Rewriting (if needed)
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Force HTTPS in production
    RewriteCond %{HTTPS} off
    RewriteCond %{HTTP_HOST} ^your-production-domain\.com$ [NC]
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove trailing slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache Control
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/ico "access plus 1 month"
    ExpiresByType image/icon "access plus 1 month"
    ExpiresByType text/plain "access plus 1 month"
</IfModule>';

if (file_put_contents('.htaccess', $htaccess_content)) {
    echo "✅ .htaccess created successfully\n";
} else {
    echo "❌ Failed to create .htaccess\n";
}

// ========================================
// SETUP COMPLETE
// ========================================

echo "\n🎉 Environment setup completed successfully!\n";
echo "\n📊 Configuration Summary:\n";
echo "   Environment: $environment\n";
echo "   Database Host: {$config['host']}\n";
echo "   Database Name: {$config['database']}\n";
echo "   Debug Mode: " . ($config['debug_mode'] ? 'Enabled' : 'Disabled') . "\n";
echo "   Error Reporting: " . ($config['error_reporting'] ? 'Enabled' : 'Disabled') . "\n";

echo "\n📋 Next Steps:\n";
echo "   1. Update database credentials in config.php if needed\n";
echo "   2. Create database and import database_complete.sql\n";
echo "   3. Test the application\n";
echo "   4. Update domain settings in config.php\n";

if ($environment === 'production') {
    echo "\n⚠️  Production Security Notes:\n";
    echo "   - Ensure SSL certificate is installed\n";
    echo "   - Set up proper firewall rules\n";
    echo "   - Configure regular backups\n";
    echo "   - Monitor error logs\n";
}

echo "\n✅ Setup script completed!\n";
?>