<?php
/**
 * FUNDRAISING SYSTEM - HOSTING SETUP
 * 
 * This script sets up the database for hosting deployment
 * Usage: Upload to hosting and run via browser
 */

// Prevent direct access without confirmation
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'setup') {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fundraising System - Database Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn-danger { background: #dc3545; }
            .form-group { margin: 15px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>🚀 Fundraising System - Database Setup</h1>
        
        <div class="warning">
            <strong>⚠️ PERINGATAN:</strong> Script ini akan membuat database baru dan menghapus data yang ada!
        </div>
        
        <form method="GET" action="">
            <input type="hidden" name="confirm" value="setup">
            
            <div class="form-group">
                <label>Database Host:</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="db_name" value="fundraising_db" required>
            </div>
            
            <div class="form-group">
                <label>Database Username:</label>
                <input type="text" name="db_user" required>
            </div>
            
            <div class="form-group">
                <label>Database Password:</label>
                <input type="password" name="db_pass" required>
            </div>
            
            <div class="form-group">
                <label>Admin Password (untuk login):</label>
                <input type="password" name="admin_pass" value="admin123" required>
                <small>Password default: admin123</small>
            </div>
            
            <button type="submit" class="btn btn-danger">🚀 Setup Database</button>
        </form>
        
        <div style="margin-top: 30px;">
            <h3>📋 Langkah Setup:</h3>
            <ol>
                <li>Upload semua file ke hosting</li>
                <li>Jalankan script ini: <code>yourdomain.com/setup_hosting.php</code></li>
                <li>Isi form database credentials</li>
                <li>Klik "Setup Database"</li>
                <li>Setelah selesai, hapus file <code>setup_hosting.php</code></li>
                <li>Akses aplikasi: <code>yourdomain.com</code></li>
            </ol>
            
            <h3>🔐 Login Default:</h3>
            <ul>
                <li><strong>Admin:</strong> admin@example.com / admin123</li>
                <li><strong>User:</strong> ahmad.rizki@fundraising.com / admin123</li>
                <li><strong>Monitor:</strong> monitor@fundraising.com / admin123</li>
            </ul>
        </div>
    </body>
    </html>';
    exit;
}

// Get database credentials from form
$db_host = $_GET['db_host'] ?? 'localhost';
$db_name = $_GET['db_name'] ?? 'fundraising_db';
$db_user = $_GET['db_user'] ?? '';
$db_pass = $_GET['db_pass'] ?? '';
$admin_pass = $_GET['admin_pass'] ?? 'admin123';

// Validate inputs
if (empty($db_user) || empty($db_pass)) {
    die('<div class="error">❌ Database username dan password harus diisi!</div>');
}

echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundraising System - Setup Progress</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .step { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>🚀 Fundraising System - Database Setup</h1>';

try {
    echo '<div class="step">📡 Menghubungkan ke database...</div>';
    
    // Connect to MySQL server
    $pdo_server = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo '<div class="success">✅ Berhasil terhubung ke MySQL server</div>';
    
    // Create database if not exists
    echo '<div class="step">🗄️ Membuat database...</div>';
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo '<div class="success">✅ Database <strong>' . htmlspecialchars($db_name) . '</strong> berhasil dibuat</div>';
    
    // Connect to target database
    echo '<div class="step">🔗 Menghubungkan ke database target...</div>';
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo '<div class="success">✅ Berhasil terhubung ke database target</div>';
    
    // Drop existing tables
    echo '<div class="step">🗑️ Membersihkan tabel lama...</div>';
    $pdo->exec("SET foreign_key_checks = 0");
    $pdo->exec("DROP TABLE IF EXISTS `kunjungan`");
    $pdo->exec("DROP TABLE IF EXISTS `donatur`");
    $pdo->exec("DROP TABLE IF EXISTS `users`");
    $pdo->exec("DROP TABLE IF EXISTS `settings`");
    $pdo->exec("SET foreign_key_checks = 1");
    echo '<div class="success">✅ Tabel lama berhasil dibersihkan</div>';
    
    // Create tables
    echo '<div class="step">🏗️ Membuat struktur tabel...</div>';
    
    // Users table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Donatur table
    $pdo->exec("
        CREATE TABLE `donatur` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nama` VARCHAR(150) NOT NULL,
            `hp` VARCHAR(25) NOT NULL,
            `alamat` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_donatur_hp` (`hp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Kunjungan table
    $pdo->exec("
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
            KEY `idx_kunjungan_location` (`latitude`,`longitude`),
            CONSTRAINT `fk_kunjungan_fundraiser`
                FOREIGN KEY (`fundraiser_id`) REFERENCES `users`(`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT `fk_kunjungan_donatur`
                FOREIGN KEY (`donatur_id`) REFERENCES `donatur`(`id`)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Settings table
    $pdo->exec("
        CREATE TABLE `settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(100) NOT NULL,
            `setting_value` TEXT DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_settings_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo '<div class="success">✅ Struktur tabel berhasil dibuat</div>';
    
    // Create additional indexes
    echo '<div class="step">⚡ Membuat index untuk performa...</div>';
    $pdo->exec("CREATE INDEX `idx_kunjungan_date_range` ON `kunjungan` (`created_at`, `status`)");
    $pdo->exec("CREATE INDEX `idx_kunjungan_gps_search` ON `kunjungan` (`latitude`, `longitude`, `created_at`)");
    echo '<div class="success">✅ Index performa berhasil dibuat</div>';
    
    // Insert sample data
    echo '<div class="step">📊 Memasukkan data sample...</div>';
    
    // Hash password
    $password_hash = password_hash($admin_pass, PASSWORD_BCRYPT);
    
    // Insert users
    $users_data = [
        ['Administrator', 'admin@example.com', 'admin', $password_hash, 'admin', 'active', 8],
        ['Ahmad Rizki Pratama', 'ahmad.rizki@fundraising.com', 'ahmad', $password_hash, 'user', 'active', 8],
        ['Siti Nurhaliza Dewi', 'siti.nurhaliza@fundraising.com', 'siti', $password_hash, 'user', 'active', 8],
        ['Budi Santoso Wijaya', 'budi.santoso@fundraising.com', 'budi', $password_hash, 'user', 'active', 8],
        ['Dewi Sartika Putri', 'dewi.sartika@fundraising.com', 'dewi', $password_hash, 'user', 'active', 8],
        ['Muhammad Fajar Sidiq', 'fajar.sidiq@fundraising.com', 'fajar', $password_hash, 'user', 'active', 8],
        ['Rina Kartika Sari', 'rina.kartika@fundraising.com', 'rina', $password_hash, 'user', 'active', 8],
        ['Monitor User', 'monitor@fundraising.com', 'monitor', $password_hash, 'monitor', 'active', 0]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES (?,?,?,?,?,?,?)");
    foreach ($users_data as $user) {
        $stmt->execute($user);
    }
    echo '<div class="success">✅ Data users berhasil dimasukkan (8 users)</div>';
    
    // Insert donatur
    $donatur_data = [
        ['Pak Joko Widodo Santoso', '081234567801', 'Jl. Sudirman No. 123, Jakarta Pusat'],
        ['PT. Maju Bersama Indonesia', '021-1234-5678', 'Jl. Thamrin No. 45, Jakarta Pusat'],
        ['Ibu Siti Aminah', '081234567802', 'Jl. Gatot Subroto No. 67, Jakarta Selatan'],
        ['Yayasan Peduli Bangsa', '021-9876-5432', 'Jl. Rasuna Said No. 89, Jakarta Selatan'],
        ['Bapak Ahmad Hidayat', '081234567803', 'Jl. Kuningan No. 12, Jakarta Selatan'],
        ['PT. Bumi Sejahtera', '021-5555-1234', 'Jl. Sudirman No. 456, Jakarta Pusat'],
        ['Ibu Kartika Sari', '081234567804', 'Jl. Menteng Raya No. 78, Jakarta Pusat'],
        ['Bapak Bambang Sutrisno', '081234567805', 'Jl. Senayan No. 34, Jakarta Selatan']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `donatur` (`nama`,`hp`,`alamat`) VALUES (?,?,?)");
    foreach ($donatur_data as $donatur) {
        $stmt->execute($donatur);
    }
    echo '<div class="success">✅ Data donatur berhasil dimasukkan (8 donatur)</div>';
    
    // Insert kunjungan with GPS
    $kunjungan_data = [
        // Jakarta Pusat locations
        [2,1,'berhasil',2500000,'Kunjungan berhasil, donatur sangat antusias','uploads/kunjungan/sample1.jpg',-6.2088,106.8456,'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta'],
        [2,2,'berhasil',15000000,'Meeting dengan direktur, program disetujui','uploads/kunjungan/sample2.jpg',-6.1865,106.8243,'Jl. Thamrin No. 45, Jakarta Pusat, DKI Jakarta'],
        [2,6,'berhasil',8000000,'Presentasi program berhasil','uploads/kunjungan/sample3.jpg',-6.2088,106.8456,'Jl. Sudirman No. 456, Jakarta Pusat, DKI Jakarta'],
        [3,7,'berhasil',1200000,'Donatur tertarik dengan program pendidikan','uploads/kunjungan/sample4.jpg',-6.1865,106.8243,'Jl. Menteng Raya No. 78, Jakarta Pusat, DKI Jakarta'],
        
        // Jakarta Selatan locations
        [3,3,'berhasil',800000,'Donatur tertarik dengan program pendidikan','uploads/kunjungan/sample5.jpg',-6.2088,106.8456,'Jl. Gatot Subroto No. 67, Jakarta Selatan, DKI Jakarta'],
        [4,4,'berhasil',5000000,'Kolaborasi program sosial','uploads/kunjungan/sample6.jpg',-6.2088,106.8456,'Jl. Rasuna Said No. 89, Jakarta Selatan, DKI Jakarta'],
        [4,5,'berhasil',1200000,'Donatur tertarik dengan program kesehatan','uploads/kunjungan/sample7.jpg',-6.2088,106.8456,'Jl. Kuningan No. 12, Jakarta Selatan, DKI Jakarta'],
        [5,8,'follow-up',0,'Perlu follow up minggu depan',NULL,-6.2088,106.8456,'Jl. Senayan No. 34, Jakarta Selatan, DKI Jakarta'],
        
        // Additional sample data
        [5,1,'tidak-berhasil',0,'Donatur tidak ada di rumah',NULL,-6.2088,106.8456,'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta'],
        [6,2,'berhasil',3000000,'Kunjungan follow up berhasil','uploads/kunjungan/sample8.jpg',-6.1865,106.8243,'Jl. Thamrin No. 45, Jakarta Pusat, DKI Jakarta'],
        [6,3,'berhasil',1500000,'Donatur baru, sangat ramah','uploads/kunjungan/sample9.jpg',-6.2088,106.8456,'Jl. Gatot Subroto No. 67, Jakarta Selatan, DKI Jakarta'],
        [7,4,'follow-up',0,'Janji bertemu minggu depan',NULL,-6.2088,106.8456,'Jl. Rasuna Said No. 89, Jakarta Selatan, DKI Jakarta']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `kunjungan` (`fundraiser_id`,`donatur_id`,`status`,`nominal`,`catatan`,`foto`,`latitude`,`longitude`,`location_address`) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($kunjungan_data as $kunjungan) {
        $stmt->execute($kunjungan);
    }
    echo '<div class="success">✅ Data kunjungan berhasil dimasukkan (12 kunjungan dengan GPS)</div>';
    
    // Insert settings
    $settings_data = [
        ['site_name', 'Fundraising System'],
        ['app_version', '1.0.0'],
        ['company_name', 'PT. Fundraising Indonesia'],
        ['company_address', 'Jl. Sudirman No. 123, Jakarta Pusat'],
        ['company_phone', '+62-21-1234-5678'],
        ['company_email', 'info@fundraising.com'],
        ['target_monthly', '100000000'],
        ['target_yearly', '1200000000'],
        ['currency_format', 'IDR']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES (?,?)");
    foreach ($settings_data as $setting) {
        $stmt->execute($setting);
    }
    echo '<div class="success">✅ Data settings berhasil dimasukkan</div>';
    
    // Create upload directory
    echo '<div class="step">📁 Membuat direktori upload...</div>';
    $upload_dir = __DIR__ . '/uploads/kunjungan';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo '<div class="success">✅ Direktori upload berhasil dibuat</div>';
    } else {
        echo '<div class="success">✅ Direktori upload sudah ada</div>';
    }
    
    // Create config.php if not exists
    echo '<div class="step">⚙️ Membuat file config.php...</div>';
    $config_content = "<?php
// Database Configuration
\$host = '$db_host';
\$database = '$db_name';
\$username = '$db_user';
\$password = '$db_pass';

// PDO Connection
try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$database;charset=utf8mb4\", \$username, \$password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

function validateCSRFToken(\$token) {
    return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$token);
}
?>";
    
    if (!file_exists('config.php')) {
        file_put_contents('config.php', $config_content);
        echo '<div class="success">✅ File config.php berhasil dibuat</div>';
    } else {
        echo '<div class="info">ℹ️ File config.php sudah ada</div>';
    }
    
    echo '<div class="success" style="font-size: 18px; font-weight: bold;">
        🎉 SETUP DATABASE BERHASIL!
    </div>';
    
    echo '<div class="info">
        <h3>📋 Informasi Login:</h3>
        <ul>
            <li><strong>Admin:</strong> admin@example.com / ' . htmlspecialchars($admin_pass) . '</li>
            <li><strong>User:</strong> ahmad.rizki@fundraising.com / ' . htmlspecialchars($admin_pass) . '</li>
            <li><strong>Monitor:</strong> monitor@fundraising.com / ' . htmlspecialchars($admin_pass) . '</li>
        </ul>
        
        <h3>📊 Data Sample:</h3>
        <ul>
            <li><strong>Users:</strong> 8 fundraisers (admin, monitor, user)</li>
            <li><strong>Donatur:</strong> 8 donatur (individu & organisasi)</li>
            <li><strong>Kunjungan:</strong> 12 kunjungan dengan GPS coordinates</li>
            <li><strong>Total Donasi:</strong> Rp 47,800,000</li>
        </ul>
    </div>';
    
    echo '<div class="step">
        <h3>🔒 Keamanan:</h3>
        <p><strong>PENTING:</strong> Hapus file <code>setup_hosting.php</code> setelah setup selesai!</p>
        <a href="index.php" class="btn">🚀 Akses Aplikasi</a>
    </div>';
    
} catch (Exception $e) {
    echo '<div class="error">
        ❌ ERROR: ' . htmlspecialchars($e->getMessage()) . '
    </div>';
    
    echo '<div class="info">
        <h3>🔧 Troubleshooting:</h3>
        <ul>
            <li>Pastikan database credentials benar</li>
            <li>Pastikan user database memiliki hak CREATE, INSERT, UPDATE, DELETE</li>
            <li>Pastikan hosting mendukung MySQL/MariaDB</li>
            <li>Periksa error log hosting untuk detail lebih lanjut</li>
        </ul>
    </div>';
}

echo '</body></html>';
?>