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
-- 3. KUNJUNGAN TABLE (with GPS and Photo)
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

-- Seed minimal settings
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','Fundraising System'),
('app_version','1.0.0');

-- ========================================
-- 6. ADDITIONAL INDEXES FOR PERFORMANCE
-- ========================================

-- Index for kunjungan date range queries
CREATE INDEX `idx_kunjungan_date_range` ON `kunjungan` (`created_at`, `status`);

-- Index for user performance queries
CREATE INDEX `idx_users_active_fundraisers` ON `users` (`role`, `status`) WHERE `role` = 'user' AND `status` = 'active';

-- ========================================
-- 7. SAMPLE DATA (OPTIONAL)
-- ========================================

-- Uncomment below to add sample data for testing

/*
-- Sample fundraisers
INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES
('John Doe','john@example.com','john','__BCRYPT_USER1__','user','active',8),
('Jane Smith','jane@example.com','jane','__BCRYPT_USER2__','user','active',10),
('Monitor User','monitor@example.com','monitor','__BCRYPT_MONITOR__','monitor','active',0);

-- Sample donatur
INSERT INTO `donatur` (`nama`,`hp`,`alamat`) VALUES
('Ahmad Rizki','081234567890','Jl. Sudirman No. 123, Jakarta'),
('Siti Nurhaliza','081234567891','Jl. Thamrin No. 456, Jakarta'),
('Budi Santoso','081234567892','Jl. Gatot Subroto No. 789, Jakarta');

-- Sample kunjungan (with GPS and photo data)
INSERT INTO `kunjungan` (`fundraiser_id`,`donatur_id`,`status`,`nominal`,`catatan`,`foto`,`latitude`,`longitude`,`location_address`) VALUES
(2,1,'berhasil',500000,'Kunjungan berhasil, donatur sangat ramah','uploads/kunjungan/sample1.jpg',-6.2088,106.8456,'Jl. Sudirman No. 123, Jakarta Pusat'),
(2,2,'follow-up',0,'Perlu follow up minggu depan',NULL,-6.1751,106.8650,'Jl. Thamrin No. 456, Jakarta Pusat'),
(3,3,'tidak-berhasil',0,'Donatur tidak ada di rumah',NULL,-6.2088,106.8456,'Jl. Gatot Subroto No. 789, Jakarta Selatan');
*/

-- ========================================
-- 8. VERIFICATION QUERIES
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