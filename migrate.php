<?php
/**
 * Fundraising System - PHP Migration Script
 *
 * Usage (CLI):
 *   php migrate.php --yes --admin-pass="yourSecurePassword" [--db=fundraising_db]
 *
 * Usage (Browser):
 *   http(s)://your-host/migrate.php?confirm=run&admin_pass=yourSecurePassword
 *
 * This script will:
 *   - Parse DB credentials from config.php (host, database, username, password)
 *   - Create the database if it doesn't exist
 *   - Drop existing tables (kunjungan, donatur, users, settings)
 *   - Create fresh tables with all columns and indexes (including foto + GPS)
 *   - Seed admin user with the provided password (bcrypt)
 *   - Seed minimal settings
 */

// -------- Helpers --------
$isCli = PHP_SAPI === 'cli';

function println($msg) {
    global $isCli;
    if ($isCli) {
        fwrite(STDOUT, $msg . PHP_EOL);
    } else {
        echo htmlspecialchars($msg) . "<br>";
        @ob_flush(); @flush();
    }
}

function getArgvOption($name, $default = null) {
    global $argv;
    foreach ($argv ?? [] as $arg) {
        if (strpos($arg, "--$name=") === 0) {
            return substr($arg, strlen($name) + 3);
        }
        if ($arg === "--$name") {
            return true;
        }
    }
    return $default;
}

function parseConfigPhpCredentials($configPath) {
    $content = @file_get_contents($configPath);
    if ($content === false) {
        throw new RuntimeException("Cannot read config.php at $configPath");
    }
    $creds = [
        'host' => null,
        'database' => null,
        'username' => null,
        'password' => ''
    ];
    $patterns = [
        'host' => "/\\$host\\s*=\\s*'([^']*)'/",
        'database' => "/\\$database\\s*=\\s*'([^']*)'/",
        'username' => "/\\$username\\s*=\\s*'([^']*)'/",
        'password' => "/\\$password\\s*=\\s*'([^']*)'/",
    ];
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $content, $m)) {
            $creds[$key] = $m[1];
        }
    }
    if (empty($creds['host']) || empty($creds['username'])) {
        throw new RuntimeException("Failed to parse DB credentials from config.php");
    }
    return $creds;
}

// -------- Read inputs --------
$adminPass = null;
$forceYes = false;
$overrideDb = null;

if ($isCli) {
    $adminPass = getArgvOption('admin-pass');
    $forceYes = (bool) getArgvOption('yes', false);
    $overrideDb = getArgvOption('db');
} else {
    $adminPass = $_GET['admin_pass'] ?? $_POST['admin_pass'] ?? null;
    $confirm = $_GET['confirm'] ?? $_POST['confirm'] ?? '';
    $forceYes = ($confirm === 'run');
    $overrideDb = $_GET['db'] ?? $_POST['db'] ?? null;
}

if (!$forceYes) {
    println("REFUSED: Confirmation required. CLI: use --yes. Browser: add ?confirm=run");
    exit(1);
}

if (empty($adminPass)) {
    println("ERROR: Missing required admin password. CLI: --admin-pass=... | Browser: ?admin_pass=...");
    exit(1);
}

try {
    // -------- Load DB credentials from config.php (without executing it) --------
    $configPath = __DIR__ . '/config.php';
    $creds = parseConfigPhpCredentials($configPath);

    $host = $creds['host'];
    $database = $overrideDb ?: ($creds['database'] ?: 'fundraising_db');
    $username = $creds['username'];
    $password = $creds['password'];

    println("Using MySQL host=$host db=$database user=$username");

    // -------- Ensure database exists --------
    $pdoServer = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    println("Ensured database exists: $database");

    // -------- Connect to target database --------
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // -------- Drop tables (FK-safe order) --------
    $pdo->exec("SET foreign_key_checks = 0");
    $pdo->exec("DROP TABLE IF EXISTS `kunjungan`");
    $pdo->exec("DROP TABLE IF EXISTS `donatur`");
    $pdo->exec("DROP TABLE IF EXISTS `users`");
    $pdo->exec("DROP TABLE IF EXISTS `settings`");
    $pdo->exec("SET foreign_key_checks = 1");
    println("Dropped existing tables (if any)");

    // -------- Create tables from SQL file --------
    $sql_file = __DIR__ . '/database_complete.sql';
    if (!file_exists($sql_file)) {
        throw new RuntimeException("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Remove database creation and USE statements (we're already connected)
    $sql_content = preg_replace('/CREATE DATABASE.*?;/s', '', $sql_content);
    $sql_content = preg_replace('/USE.*?;/s', '', $sql_content);
    
    // Remove DROP TABLE statements (we already dropped them)
    $sql_content = preg_replace('/DROP TABLE.*?;/s', '', $sql_content);
    
    // Remove verification queries
    $sql_content = preg_replace('/-- ========================================.*$/s', '', $sql_content);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|SET|SELECT|SHOW)/', trim($statement))) {
            $pdo->exec($statement);
        }
    }
    
    println("Created tables from database_complete.sql");

    // -------- Seed admin with provided password --------
    $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `username` = 'admin'");
    $stmt->execute([$adminHash]);
    println("Updated admin password: username=admin");

    // -------- Ensure upload directory --------
    $uploadDir = __DIR__ . '/uploads/kunjungan';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
        println("Created directory: uploads/kunjungan");
    } else {
        println("Directory exists: uploads/kunjungan");
    }

    println("MIGRATION COMPLETE ✅");
    exit(0);
} catch (Throwable $e) {
    println("MIGRATION FAILED ❌: " . $e->getMessage());
    exit(1);
}