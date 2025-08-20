-- fundraising_full.sql
-- Full database schema + minimal seed data for Fundraising System
-- Import once via CLI or phpMyAdmin
-- CLI Example: mysql -u root -p < fundraising_full.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- Change DB name if needed
CREATE DATABASE IF NOT EXISTS `fundraising_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fundraising_db`;

-- Drop tables (FK-safe order)
DROP TABLE IF EXISTS `kunjungan`;
DROP TABLE IF EXISTS `donatur`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

SET foreign_key_checks = 1;

-- =========================
-- 1) USERS
-- =========================
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

-- SEED ADMIN
-- Replace __BCRYPT_ADMIN__ with a bcrypt hash of your chosen password
-- Generate hash: php -r 'echo password_hash("admin123", PASSWORD_BCRYPT), PHP_EOL;'
INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES
('Administrator','admin@example.com','admin','__BCRYPT_ADMIN__','admin','active',8);

-- Optional seeds (uncomment and replace hashes if needed)
-- INSERT INTO `users` (`name`,`email`,`username`,`password`,`role`,`status`,`target`) VALUES
-- ('Monitor','monitor@example.com','monitor','__BCRYPT_MONITOR__','monitor','active',8),
-- ('Fundraiser 1','user1@example.com','user1','__BCRYPT_USER1__','user','active',8);

-- =========================
-- 2) DONATUR
-- =========================
CREATE TABLE `donatur` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`nama` VARCHAR(150) NOT NULL,
	`hp` VARCHAR(25) NOT NULL,
	`alamat` TEXT DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_donatur_hp` (`hp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 3) KUNJUNGAN
-- =========================
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

-- =========================
-- 4) SETTINGS (key-value)
-- =========================
CREATE TABLE `settings` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`setting_key` VARCHAR(100) NOT NULL,
	`setting_value` TEXT DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Minimal seed settings
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','Fundraising System'),
('app_version','1.0.0');

-- Done