<?php
/**
 * Setup App Settings Table
 * Script untuk membuat tabel app_settings dan menginisialisasi pengaturan default
 */

// Database connection
require_once 'config.php';

try {
    // Create app_settings table
    $sql = "
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
    ";
    
    $pdo->exec($sql);
    echo "✅ Table app_settings created successfully\n";
    
    // Insert default settings
    $default_settings = [
        'whatsapp_base_url' => 'https://app.saungwa.com/api',
        'whatsapp_app_key' => 'e98095ab-363d-47a4-b3b6-af99d68ef2b8',
        'whatsapp_auth_key' => 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT',
        'whatsapp_sandbox' => '0',
        'app_version' => '1.0.0',
        'app_copyright' => '© 2024 Fundraising System. All rights reserved.',
        'app_company' => 'Fundraising System',
        'app_description' => 'Sistem Manajemen Fundraising Terpadu'
    ];
    
    $descriptions = [
        'whatsapp_base_url' => 'WhatsApp API Base URL',
        'whatsapp_app_key' => 'WhatsApp API App Key',
        'whatsapp_auth_key' => 'WhatsApp API Auth Key',
        'whatsapp_sandbox' => 'WhatsApp API Sandbox Mode (0=Production, 1=Sandbox)',
        'app_version' => 'Application Version',
        'app_copyright' => 'Application Copyright',
        'app_company' => 'Company Name',
        'app_description' => 'Application Description'
    ];
    
    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO app_settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            description = VALUES(description),
            updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $descriptions[$key]]);
    }
    
    echo "✅ Default settings inserted successfully\n";
    
    // Verify settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Current Settings:\n";
    foreach ($settings as $setting) {
        $value = $setting['setting_value'];
        if (strlen($value) > 50) {
            $value = substr($value, 0, 50) . '...';
        }
        echo "  {$setting['setting_key']}: {$value}\n";
    }
    
    echo "\n✅ App Settings setup completed successfully!\n";
    echo "You can now use WhatsApp Settings page to update API configuration.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>