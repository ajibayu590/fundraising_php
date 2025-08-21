<?php
/**
 * Setup App Settings Table - Web Version
 * Script untuk membuat tabel app_settings dan menginisialisasi pengaturan default
 * Akses melalui browser: http://your-domain.com/setup_app_settings_web.php
 */

// Database connection
require_once 'config.php';

$message = '';
$error = '';

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
    $message .= "✅ Table app_settings created successfully<br>";
    
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
    
    $message .= "✅ Default settings inserted successfully<br>";
    
    // Verify settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $message .= "<br>📋 Current Settings:<br>";
    foreach ($settings as $setting) {
        $value = $setting['setting_value'];
        if (strlen($value) > 50) {
            $value = substr($value, 0, 50) . '...';
        }
        $message .= "&nbsp;&nbsp;{$setting['setting_key']}: {$value}<br>";
    }
    
    $message .= "<br>✅ App Settings setup completed successfully!<br>";
    $message .= "You can now use WhatsApp Settings page to update API configuration.<br>";
    
} catch (Exception $e) {
    $error = "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup App Settings - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Setup App Settings</h1>
            <p class="text-gray-600">Membuat tabel app_settings dan menginisialisasi pengaturan default</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <strong>Error:</strong> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="whatsapp_settings.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Go to WhatsApp Settings
            </a>
        </div>
    </div>
</body>
</html>