<?php
// Script to insert default settings for target global functionality
require_once 'config.php';

try {
    // Check if settings already exist
    $stmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key IN ('target_global', 'target_donasi', 'target_donatur_baru')");
    $stmt->execute();
    $existingSettings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $defaultSettings = [
        'target_global' => '8',
        'target_donasi' => '1000000',
        'target_donatur_baru' => '50'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        if (!in_array($key, $existingSettings)) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$key, $value]);
            echo "Inserted setting: $key = $value\n";
        } else {
            echo "Setting already exists: $key\n";
        }
    }
    
    echo "Default settings setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>