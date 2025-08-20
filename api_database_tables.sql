-- API Database Tables for Fundraising System

-- WhatsApp Messages Table
CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `donor_id` INT UNSIGNED NULL,
    `to_number` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `template_id` VARCHAR(100) NULL,
    `variables` JSON NULL,
    `file_url` VARCHAR(500) NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `response_data` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_whatsapp_user` (`user_id`),
    KEY `idx_whatsapp_donor` (`donor_id`),
    KEY `idx_whatsapp_success` (`success`),
    KEY `idx_whatsapp_created` (`created_at`),
    CONSTRAINT `fk_whatsapp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_whatsapp_donor` FOREIGN KEY (`donor_id`) REFERENCES `donatur`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Logs Table
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `endpoint` VARCHAR(255) NOT NULL,
    `method` VARCHAR(10) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_api_user` (`user_id`),
    KEY `idx_api_endpoint` (`endpoint`),
    KEY `idx_api_method` (`method`),
    KEY `idx_api_created` (`created_at`),
    CONSTRAINT `fk_api_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Rate Limiting Table (Alternative to file-based)
CREATE TABLE IF NOT EXISTS `api_rate_limits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL,
    `endpoint` VARCHAR(255) NOT NULL,
    `count` INT UNSIGNED NOT NULL DEFAULT 1,
    `reset_time` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rate_limit_identifier_endpoint` (`identifier`, `endpoint`),
    KEY `idx_rate_limit_reset` (`reset_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for WhatsApp templates
INSERT INTO `whatsapp_messages` (`user_id`, `to_number`, `message`, `success`, `created_at`) VALUES
(1, '6281234567890', 'Test message from API', 1, NOW()),
(2, '6281234567891', 'Welcome to our fundraising program', 1, NOW());

-- Sample API logs
INSERT INTO `api_logs` (`endpoint`, `method`, `user_id`, `ip_address`, `user_agent`, `details`) VALUES
('/api/auth', 'POST', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'User login'),
('/api/kunjungan', 'GET', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'List kunjungan'),
('/api/whatsapp', 'POST', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Send WhatsApp message');