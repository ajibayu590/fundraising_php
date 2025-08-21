-- App Settings Table for Fundraising System
-- This table stores application settings like WhatsApp API configuration, version, copyright, etc.

CREATE TABLE IF NOT EXISTS `app_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`),
    KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `app_settings` (`setting_key`, `setting_value`, `description`) VALUES
('whatsapp_base_url', 'https://app.saungwa.com/api', 'WhatsApp API Base URL'),
('whatsapp_app_key', 'e98095ab-363d-47a4-b3b6-af99d68ef2b8', 'WhatsApp API App Key'),
('whatsapp_auth_key', 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT', 'WhatsApp API Auth Key'),
('whatsapp_sandbox', '0', 'WhatsApp API Sandbox Mode (0=Production, 1=Sandbox)'),
('app_version', '1.0.0', 'Application Version'),
('app_copyright', '© 2024 Fundraising System. All rights reserved.', 'Application Copyright'),
('app_company', 'Fundraising System', 'Company Name'),
('app_description', 'Sistem Manajemen Fundraising Terpadu', 'Application Description')
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();