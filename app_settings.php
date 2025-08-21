<?php
/**
 * Application Settings
 * File untuk mengelola pengaturan aplikasi seperti versi, copyright, dan WhatsApp API settings
 */

// Database connection
require_once 'config.php';

// Application Information
$app_settings = [
    'version' => '1.0.0',
    'copyright' => '© 2024 Fundraising System. All rights reserved.',
    'company' => 'Fundraising System',
    'description' => 'Sistem Manajemen Fundraising Terpadu'
];

/**
 * Function to get app setting from database
 */
function get_app_setting($key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : null;
    } catch (Exception $e) {
        error_log("Error getting app setting '$key': " . $e->getMessage());
        return null;
    }
}

/**
 * Function to update app setting in database
 */
function update_app_setting($key, $value) {
    global $pdo;
    
    try {
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // Update existing setting
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            // Insert new setting
            $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$key, $value]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating app setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Function to delete app setting
 */
function delete_app_setting($key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return true;
    } catch (Exception $e) {
        error_log("Error deleting app setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Function to get all app settings
 */
function get_all_app_settings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value, created_at, updated_at FROM app_settings ORDER BY setting_key");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting all app settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Initialize default settings if they don't exist
 */
function initialize_default_settings() {
    global $pdo;
    
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
    
    foreach ($default_settings as $key => $value) {
        if (get_app_setting($key) === null) {
            update_app_setting($key, $value);
        }
    }
}

// Initialize default settings when this file is included
initialize_default_settings();
?>