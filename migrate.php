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

    // -------- Create tables --------
    // users
    $pdo->exec(<<<SQL
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `username` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','monitor','user') NOT NULL DEFAULT 'user',
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `target` INT UNSIGNED NOT NULL DEFAULT 8,
    `phone` VARCHAR(25) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_username` (`username`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // donatur
    $pdo->exec(<<<SQL
CREATE TABLE `donatur` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(150) NOT NULL,
    `hp` VARCHAR(25) NOT NULL,
    `alamat` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_donatur_hp` (`hp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // kunjungan
    $pdo->exec(<<<SQL
CREATE TABLE `kunjungan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fundraiser_id` INT UNSIGNED NOT NULL,
    `donatur_id` INT UNSIGNED NOT NULL,
    `status` ENUM('berhasil','tidak-berhasil','follow-up') NOT NULL,
    `nominal` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `catatan` TEXT DEFAULT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `location_address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kunjungan_fundraiser` (`fundraiser_id`),
    KEY `idx_kunjungan_donatur` (`donatur_id`),
    KEY `idx_kunjungan_status` (`status`),
    KEY `idx_kunjungan_created_at` (`created_at`),
    KEY `idx_kunjungan_foto` (`foto`),
    KEY `idx_kunjungan_location` (`latitude`, `longitude`),
    CONSTRAINT `fk_kunjungan_fundraiser` FOREIGN KEY (`fundraiser_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_kunjungan_donatur` FOREIGN KEY (`donatur_id`) REFERENCES `donatur`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // settings
    $pdo->exec(<<<SQL
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    println("Created tables: users, donatur, kunjungan, settings");

    // -------- Seed admin --------
    $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES (?,?,?,?, 'admin','active', 8)");
    $stmt->execute(['Administrator','admin@example.com','admin', $adminHash]);
    println("Seeded admin user: username=admin");

    // -------- Seed settings --------
    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES (?,?), (?,?)");
    $stmt->execute(['site_name','Fundraising System','app_version','1.0.0']);
    println("Seeded minimal settings");

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