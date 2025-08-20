-- ========================================
-- FUNDRAISING SYSTEM - COMPLETE DATABASE
-- ========================================
-- This file contains all SQL scripts combined
-- Import this file to set up the complete database

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `fundraising_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fundraising_db`;

-- Drop tables in FK-safe order
DROP TABLE IF EXISTS `kunjungan`;
DROP TABLE IF EXISTS `donatur`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

SET foreign_key_checks = 1;

-- ========================================
-- 1. USERS TABLE
-- ========================================
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

-- ========================================
-- 2. DONATUR TABLE
-- ========================================
CREATE TABLE `donatur` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(150) NOT NULL,
    `hp` VARCHAR(25) NOT NULL,
    `alamat` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_donatur_hp` (`hp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 3. KUNJUNGAN TABLE (with GPS coordinates)
-- ========================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 4. SETTINGS TABLE
-- ========================================
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 5. SEED DATA
-- ========================================

-- Seed admin user (replace __BCRYPT_ADMIN__ with actual hash)
-- Generate hash: php -r 'echo password_hash("admin123", PASSWORD_BCRYPT), PHP_EOL;'
INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES
('Administrator','admin@example.com','admin','__BCRYPT_ADMIN__','admin','active',8);

-- Seed sample fundraisers (replace __BCRYPT_USER__ with actual hash)
INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES
('Ahmad Rizki Pratama','ahmad.rizki@fundraising.com','ahmad','__BCRYPT_USER__','user','active',8),
('Siti Nurhaliza Dewi','siti.nurhaliza@fundraising.com','siti','__BCRYPT_USER__','user','active',8),
('Budi Santoso Wijaya','budi.santoso@fundraising.com','budi','__BCRYPT_USER__','user','active',8),
('Dewi Sartika Putri','dewi.sartika@fundraising.com','dewi','__BCRYPT_USER__','user','active',8),
('Muhammad Fajar Sidiq','fajar.sidiq@fundraising.com','fajar','__BCRYPT_USER__','user','active',8),
('Rina Kartika Sari','rina.kartika@fundraising.com','rina','__BCRYPT_USER__','user','active',8),
('Monitor User','monitor@fundraising.com','monitor','__BCRYPT_USER__','monitor','active',0);

-- Seed sample donatur
INSERT INTO `donatur` (`nama`,`hp`,`alamat`) VALUES
('Pak Joko Widodo Santoso','081234567801','Jl. Sudirman No. 123, Jakarta Pusat'),
('PT. Maju Bersama Indonesia','021-1234-5678','Jl. Thamrin No. 45, Jakarta Pusat'),
('Ibu Siti Aminah','081234567802','Jl. Gatot Subroto No. 67, Jakarta Selatan'),
('Yayasan Peduli Bangsa','021-9876-5432','Jl. Rasuna Said No. 89, Jakarta Selatan'),
('Bapak Ahmad Hidayat','081234567803','Jl. Kuningan No. 12, Jakarta Selatan'),
('PT. Bumi Sejahtera','021-5555-1234','Jl. Sudirman No. 456, Jakarta Pusat'),
('Ibu Kartika Sari','081234567804','Jl. Menteng Raya No. 78, Jakarta Pusat'),
('Bapak Bambang Sutrisno','081234567805','Jl. Senayan No. 34, Jakarta Selatan');

-- Seed sample kunjungan with GPS coordinates
INSERT INTO `kunjungan` (`fundraiser_id`,`donatur_id`,`status`,`nominal`,`catatan`,`foto`,`latitude`,`longitude`,`location_address`) VALUES
-- Jakarta Pusat locations
(2,1,'berhasil',2500000,'Kunjungan berhasil, donatur sangat antusias','uploads/kunjungan/sample1.jpg',-6.2088,106.8456,'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta'),
(2,2,'berhasil',15000000,'Meeting dengan direktur, program disetujui','uploads/kunjungan/sample2.jpg',-6.1865,106.8243,'Jl. Thamrin No. 45, Jakarta Pusat, DKI Jakarta'),
(2,6,'berhasil',8000000,'Presentasi program berhasil','uploads/kunjungan/sample3.jpg',-6.2088,106.8456,'Jl. Sudirman No. 456, Jakarta Pusat, DKI Jakarta'),
(3,7,'berhasil',1200000,'Donatur tertarik dengan program pendidikan','uploads/kunjungan/sample4.jpg',-6.1865,106.8243,'Jl. Menteng Raya No. 78, Jakarta Pusat, DKI Jakarta'),

-- Jakarta Selatan locations
(3,3,'berhasil',800000,'Donatur tertarik dengan program pendidikan','uploads/kunjungan/sample5.jpg',-6.2088,106.8456,'Jl. Gatot Subroto No. 67, Jakarta Selatan, DKI Jakarta'),
(4,4,'berhasil',5000000,'Kolaborasi program sosial','uploads/kunjungan/sample6.jpg',-6.2088,106.8456,'Jl. Rasuna Said No. 89, Jakarta Selatan, DKI Jakarta'),
(4,5,'berhasil',1200000,'Donatur tertarik dengan program kesehatan','uploads/kunjungan/sample7.jpg',-6.2088,106.8456,'Jl. Kuningan No. 12, Jakarta Selatan, DKI Jakarta'),
(5,8,'follow-up',0,'Perlu follow up minggu depan',NULL,-6.2088,106.8456,'Jl. Senayan No. 34, Jakarta Selatan, DKI Jakarta'),

-- Additional sample data
(5,1,'tidak-berhasil',0,'Donatur tidak ada di rumah',NULL,-6.2088,106.8456,'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta'),
(6,2,'berhasil',3000000,'Kunjungan follow up berhasil','uploads/kunjungan/sample8.jpg',-6.1865,106.8243,'Jl. Thamrin No. 45, Jakarta Pusat, DKI Jakarta'),
(6,3,'berhasil',1500000,'Donatur baru, sangat ramah','uploads/kunjungan/sample9.jpg',-6.2088,106.8456,'Jl. Gatot Subroto No. 67, Jakarta Selatan, DKI Jakarta'),
(7,4,'follow-up',0,'Janji bertemu minggu depan',NULL,-6.2088,106.8456,'Jl. Rasuna Said No. 89, Jakarta Selatan, DKI Jakarta');

-- Seed minimal settings
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','Fundraising System'),
('app_version','1.0.0'),
('company_name','PT. Fundraising Indonesia'),
('company_address','Jl. Sudirman No. 123, Jakarta Pusat'),
('company_phone','+62-21-1234-5678'),
('company_email','info@fundraising.com'),
('target_monthly','100000000'),
('target_yearly','1200000000'),
('currency_format','IDR');

-- ========================================
-- 6. ADDITIONAL INDEXES FOR PERFORMANCE
-- ========================================

-- Index for kunjungan date range queries
CREATE INDEX `idx_kunjungan_date_range` ON `kunjungan` (`created_at`, `status`);

-- Index for user performance queries
CREATE INDEX `idx_users_active_fundraisers` ON `users` (`role`, `status`) WHERE `role` = 'user' AND `status` = 'active';

-- Index for GPS-based queries
CREATE INDEX `idx_kunjungan_gps_search` ON `kunjungan` (`latitude`, `longitude`, `created_at`);

-- ========================================
-- 7. VERIFICATION QUERIES
-- ========================================

-- Verify table creation
SELECT 'Tables created successfully' as status;

-- Check table structure
SHOW TABLES;

-- Check indexes
SHOW INDEX FROM kunjungan;

-- ========================================
-- COMPLETE DATABASE SETUP FINISHED
-- ========================================