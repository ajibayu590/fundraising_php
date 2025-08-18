-- Insert default settings for target global functionality
USE fundraising_db;

-- Insert target_global setting if not exists
INSERT IGNORE INTO settings (setting_key, setting_value, created_at, updated_at) 
VALUES ('target_global', '8', NOW(), NOW());

-- Insert target_donasi setting if not exists
INSERT IGNORE INTO settings (setting_key, setting_value, created_at, updated_at) 
VALUES ('target_donasi', '1000000', NOW(), NOW());

-- Insert target_donatur_baru setting if not exists
INSERT IGNORE INTO settings (setting_key, setting_value, created_at, updated_at) 
VALUES ('target_donatur_baru', '50', NOW(), NOW());

-- Verify the settings were inserted
SELECT * FROM settings WHERE setting_key IN ('target_global', 'target_donasi', 'target_donatur_baru');