-- Fundraising System Database Schema
-- Created: 2025-01-01
-- Description: Database untuk sistem fundraising dengan role-based access control

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS fundraising_db;
USE fundraising_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL, -- changed from 255 to 191
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
);
-- Donatur table
CREATE TABLE donatur (
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
);

-- Kunjungan table
CREATE TABLE kunjungan (
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
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default users
-- Password: "password" (hashed with BCRYPT)
INSERT INTO users (id, name, email, password, hp, role, status, target, created_at) VALUES
(1, 'Ahmad Rizki Pratama', 'ahmad.rizki@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin', 'aktif', 10, '2024-01-01 00:00:00'),
(2, 'Pipin Monitor', 'pipin@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'monitor', 'aktif', 8, '2024-01-01 00:00:00'),
(3, 'Siti Nurhaliza Dewi', 'siti.nurhaliza@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
(4, 'Budi Santoso Wijaya', 'budi.santoso@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
(5, 'Dewi Sartika Putri', 'dewi.sartika@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567894', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
(6, 'Muhammad Fajar Sidiq', 'fajar.sidiq@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567895', 'user', 'aktif', 8, '2024-01-01 00:00:00'),
(7, 'Rina Kartika Sari', 'rina.kartika@fundraising.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567896', 'user', 'aktif', 8, '2024-01-01 00:00:00');

-- Insert default donatur
INSERT INTO donatur (id, nama, hp, email, alamat, kategori, status, created_at) VALUES
(1, 'Pak Joko Widodo Santoso', '081234567801', 'joko.widodo@email.com', 'Jl. Sudirman No. 123, Jakarta Pusat', 'individu', 'aktif', '2024-01-01 00:00:00'),
(2, 'PT. Maju Bersama Indonesia', '021-1234-5678', 'info@majubersama.co.id', 'Jl. Thamrin No. 45, Jakarta Pusat', 'korporasi', 'aktif', '2024-01-01 00:00:00'),
(3, 'Ibu Siti Aminah', '081234567802', 'siti.aminah@email.com', 'Jl. Gatot Subroto No. 67, Jakarta Selatan', 'individu', 'aktif', '2024-01-01 00:00:00'),
(4, 'Yayasan Peduli Bangsa', '021-9876-5432', 'contact@pedulibangsa.or.id', 'Jl. Rasuna Said No. 89, Jakarta Selatan', 'yayasan', 'aktif', '2024-01-01 00:00:00'),
(5, 'Bapak Ahmad Hidayat', '081234567803', 'ahmad.hidayat@email.com', 'Jl. Kuningan No. 12, Jakarta Selatan', 'individu', 'aktif', '2024-01-01 00:00:00');

-- Insert default kunjungan
INSERT INTO kunjungan (id, fundraiser_id, donatur_id, alamat, lokasi, nominal, status, waktu, foto, catatan, created_at) VALUES
(1, 1, 1, 'Jl. Sudirman No. 123, Jakarta Pusat', '-6.2088, 106.8456', 2500000.00, 'berhasil', '2024-01-01 10:00:00', 'foto1.jpg', 'Kunjungan berhasil, donatur sangat antusias', '2024-01-01 10:00:00'),
(2, 2, 2, 'Jl. Thamrin No. 45, Jakarta Pusat', '-6.1865, 106.8243', 15000000.00, 'berhasil', '2024-01-01 11:00:00', 'foto2.jpg', 'Meeting dengan direktur, program disetujui', '2024-01-01 11:00:00'),
(3, 3, 3, 'Jl. Gatot Subroto No. 67, Jakarta Selatan', '-6.2088, 106.8456', 800000.00, 'berhasil', '2024-01-01 12:00:00', 'foto3.jpg', 'Donatur tertarik dengan program pendidikan', '2024-01-01 12:00:00'),
(4, 4, 4, 'Jl. Rasuna Said No. 89, Jakarta Selatan', '-6.2088, 106.8456', 5000000.00, 'berhasil', '2024-01-01 13:00:00', 'foto4.jpg', 'Kolaborasi program sosial', '2024-01-01 13:00:00'),
(5, 5, 5, 'Jl. Kuningan No. 12, Jakarta Selatan', '-6.2088, 106.8456', 1200000.00, 'berhasil', '2024-01-01 14:00:00', 'foto5.jpg', 'Donatur tertarik dengan program kesehatan', '2024-01-01 14:00:00');

-- Insert default settings
INSERT INTO settings (id, setting_key, setting_value, created_at) VALUES
(1, 'app_name', 'Fundraising System', '2024-01-01 00:00:00'),
(2, 'version', '1.0.0', '2024-01-01 00:00:00'),
(3, 'company_name', 'PT. Fundraising Indonesia', '2024-01-01 00:00:00'),
(4, 'company_address', 'Jl. Sudirman No. 123, Jakarta Pusat', '2024-01-01 00:00:00'),
(5, 'company_phone', '+62-21-1234-5678', '2024-01-01 00:00:00'),
(6, 'company_email', 'info@fundraising.com', '2024-01-01 00:00:00'),
(7, 'target_monthly', '100000000', '2024-01-01 00:00:00'),
(8, 'target_yearly', '1200000000', '2024-01-01 00:00:00'),
(9, 'currency_format', 'IDR', '2024-01-01 00:00:00');

-- Update donatur stats based on kunjungan
UPDATE donatur d 
SET total_donasi = (
    SELECT COALESCE(SUM(nominal), 0) 
    FROM kunjungan k 
    WHERE k.donatur_id = d.id AND k.status = 'berhasil'
),
terakhir_donasi = (
    SELECT MAX(waktu) 
    FROM kunjungan k 
    WHERE k.donatur_id = d.id AND k.status = 'berhasil'
),
jumlah_kunjungan = (
    SELECT COUNT(*) 
    FROM kunjungan k 
    WHERE k.donatur_id = d.id
),
rata_rata_donasi = (
    SELECT COALESCE(AVG(nominal), 0) 
    FROM kunjungan k 
    WHERE k.donatur_id = d.id AND k.status = 'berhasil'
),
first_donation = (
    SELECT MIN(waktu) 
    FROM kunjungan k 
    WHERE k.donatur_id = d.id AND k.status = 'berhasil'
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_donatur_hp ON donatur(hp);
CREATE INDEX idx_donatur_kategori ON donatur(kategori);
CREATE INDEX idx_kunjungan_fundraiser ON kunjungan(fundraiser_id);
CREATE INDEX idx_kunjungan_donatur ON kunjungan(donatur_id);
CREATE INDEX idx_kunjungan_status ON kunjungan(status);
CREATE INDEX idx_kunjungan_waktu ON kunjungan(waktu);
CREATE INDEX idx_settings_key ON settings(setting_key);

-- Create view for kunjungan with user and donatur info
CREATE VIEW v_kunjungan_detail AS
SELECT 
    k.id,
    k.fundraiser_id,
    u.name as fundraiser_nama,
    k.donatur_id,
    d.nama as donatur_nama,
    d.hp as donatur_hp,
    k.alamat,
    k.lokasi,
    k.nominal,
    k.status,
    k.waktu,
    k.foto,
    k.catatan,
    k.follow_up_date,
    k.created_at
FROM kunjungan k
JOIN users u ON k.fundraiser_id = u.id
JOIN donatur d ON k.donatur_id = d.id;

-- Create view for user statistics
CREATE VIEW v_user_stats AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.role,
    u.status,
    u.target,
    COUNT(k.id) as total_kunjungan,
    SUM(CASE WHEN k.status = 'berhasil' THEN 1 ELSE 0 END) as kunjungan_berhasil,
    SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END) as total_donasi,
    AVG(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE NULL END) as rata_rata_donasi
FROM users u
LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
GROUP BY u.id, u.name, u.email, u.role, u.status, u.target;