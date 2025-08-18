<?php
// Database Setup Script
// Run this script to create the database and tables

echo "<h2>Database Setup Script</h2>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL successfully</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS fundraising_db");
    echo "<p>✅ Database 'fundraising_db' created/verified</p>";
    
    // Select the database
    $pdo->exec("USE fundraising_db");
    echo "<p>✅ Using database 'fundraising_db'</p>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(191) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            hp VARCHAR(20),
            role ENUM('admin', 'user', 'monitor') NOT NULL DEFAULT 'user',
            status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            target INT DEFAULT 8,
            kunjungan_hari_ini INT DEFAULT 0,
            total_kunjungan_bulan INT DEFAULT 0,
            total_donasi_bulan DECIMAL(15,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_active TIMESTAMP NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Table 'users' created/verified</p>";
    
    // Create donatur table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS donatur (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            hp VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(255),
            alamat TEXT NOT NULL,
            kategori ENUM('individu', 'korporasi', 'yayasan', 'organisasi') NOT NULL,
            total_donasi DECIMAL(15,2) DEFAULT 0.00,
            terakhir_donasi TIMESTAMP NULL,
            status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            jumlah_kunjungan INT DEFAULT 0,
            rata_rata_donasi DECIMAL(15,2) DEFAULT 0.00,
            first_donation TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Table 'donatur' created/verified</p>";
    
    // Create kunjungan table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kunjungan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fundraiser_id INT NOT NULL,
            donatur_id INT NOT NULL,
            alamat TEXT NOT NULL,
            lokasi VARCHAR(100),
            nominal DECIMAL(15,2) DEFAULT 0.00,
            status ENUM('berhasil', 'tidak-berhasil', 'follow-up') NOT NULL,
            waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            foto VARCHAR(255),
            catatan TEXT,
            follow_up_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (fundraiser_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (donatur_id) REFERENCES donatur(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Table 'kunjungan' created/verified</p>";
    
    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Table 'settings' created/verified</p>";
    
    // Check if default data exists
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    
    echo "<h3>Current Data Status:</h3>";
    echo "<p>Users: $userCount</p>";
    echo "<p>Donatur: $donaturCount</p>";
    echo "<p>Kunjungan: $kunjunganCount</p>";
    
    // Insert default data if tables are empty
    if ($userCount == 0) {
        echo "<p>📝 Inserting default users...</p>";
        
        // Insert default users
        $pdo->exec("
            INSERT INTO users (id, name, email, password, hp, role, status, target, created_at) VALUES
            (1, 'Ahmad Rizki Pratama', 'ahmad.rizki@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin', 'aktif', 10, '2024-01-01 00:00:00'),
            (2, 'Pipin Monitor', 'pipin@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'monitor', 'aktif', 8, '2024-01-01 00:00:00'),
            (3, 'Siti Nurhaliza Dewi', 'siti.nurhaliza@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
            (4, 'Budi Santoso Wijaya', 'budi.santoso@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
            (5, 'Dewi Sartika Putri', 'dewi.sartika@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567894', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
            (6, 'Muhammad Fajar Sidiq', 'fajar.sidiq@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567895', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
            (7, 'Rina Kartika Sari', 'rina.kartika@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567896', 'user', 'aktif', 8, '2024-01-01 00:00:00')
        ");
        echo "<p>✅ Default users inserted</p>";
    }
    
    if ($donaturCount == 0) {
        echo "<p>📝 Inserting default donatur...</p>";
        
        // Insert default donatur
        $pdo->exec("
            INSERT INTO donatur (id, nama, hp, email, alamat, kategori, status, created_at) VALUES
            (1, 'Pak Joko Widodo Santoso', '081234567801', 'joko.widodo@email.com', 'Jl. Sudirman No. 123, Jakarta Pusat', 'individu', 'aktif', '2024-01-01 00:00:00'),
            (2, 'PT. Maju Bersama Indonesia', '021-1234-5678', 'info@majubersama.co.id', 'Jl. Thamrin No. 45, Jakarta Pusat', 'korporasi', 'aktif', '2024-01-01 00:00:00'),
            (3, 'Ibu Siti Aminah', '081234567802', 'siti.aminah@email.com', 'Jl. Gatot Subroto No. 67, Jakarta Selatan', 'individu', 'aktif', '2024-01-01 00:00:00'),
            (4, 'Yayasan Peduli Bangsa', '021-9876-5432', 'contact@pedulibangsa.or.id', 'Jl. Rasuna Said No. 89, Jakarta Selatan', 'yayasan', 'aktif', '2024-01-01 00:00:00'),
            (5, 'Bapak Ahmad Hidayat', '081234567803', 'ahmad.hidayat@email.com', 'Jl. Kuningan No. 12, Jakarta Selatan', 'individu', 'aktif', '2024-01-01 00:00:00')
        ");
        echo "<p>✅ Default donatur inserted</p>";
    }
    
    if ($kunjunganCount == 0) {
        echo "<p>📝 Inserting default kunjungan...</p>";
        
        // Insert default kunjungan
        $pdo->exec("
            INSERT INTO kunjungan (id, fundraiser_id, donatur_id, alamat, lokasi, nominal, status, waktu, foto, catatan, created_at) VALUES
            (1, 1, 1, 'Jl. Sudirman No. 123, Jakarta Pusat', '-6.2088, 106.8456', 2500000.00, 'berhasil', '2024-01-01 10:00:00', 'foto1.jpg', 'Kunjungan berhasil, donatur sangat antusias', '2024-01-01 10:00:00'),
            (2, 2, 2, 'Jl. Thamrin No. 45, Jakarta Pusat', '-6.1865, 106.8243', 15000000.00, 'berhasil', '2024-01-01 11:00:00', 'foto2.jpg', 'Meeting dengan direktur, program disetujui', '2024-01-01 11:00:00'),
            (3, 3, 3, 'Jl. Gatot Subroto No. 67, Jakarta Selatan', '-6.2088, 106.8456', 800000.00, 'berhasil', '2024-01-01 12:00:00', 'foto3.jpg', 'Donatur tertarik dengan program pendidikan', '2024-01-01 12:00:00'),
            (4, 4, 4, 'Jl. Rasuna Said No. 89, Jakarta Selatan', '-6.2088, 106.8456', 5000000.00, 'berhasil', '2024-01-01 13:00:00', 'foto4.jpg', 'Kolaborasi program sosial', '2024-01-01 13:00:00'),
            (5, 5, 5, 'Jl. Kuningan No. 12, Jakarta Selatan', '-6.2088, 106.8456', 1200000.00, 'berhasil', '2024-01-01 14:00:00', 'foto5.jpg', 'Donatur tertarik dengan program kesehatan', '2024-01-01 14:00:00')
        ");
        echo "<p>✅ Default kunjungan inserted</p>";
    }
    
    // Final data count
    $finalUserCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $finalDonaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    $finalKunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    
    echo "<h3>Final Data Status:</h3>";
    echo "<p>Users: $finalUserCount</p>";
    echo "<p>Donatur: $finalDonaturCount</p>";
    echo "<p>Kunjungan: $finalKunjunganCount</p>";
    
    echo "<h3>🎉 Database setup completed successfully!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p>Admin: ahmad.rizki@fundraising.com / password</p>";
    echo "<p>Monitor: pipin@fundraising.com / password</p>";
    echo "<p>User: siti.nurhaliza@fundraising.com / password</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
