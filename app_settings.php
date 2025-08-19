<?php
/**
 * Application Settings
 * File untuk mengelola pengaturan aplikasi seperti versi dan copyright
 */

// Application Information
$app_settings = [
    'version' => '1.0.0',
    'copyright' => '© 2024 Fundraising System. All rights reserved.',
    'company' => 'Fundraising System',
    'description' => 'Sistem Manajemen Fundraising Terpadu'
];

// Function to get app settings
function get_app_setting($key) {
    global $app_settings;
    return $app_settings[$key] ?? '';
}

// Function to update app settings (for admin use)
function update_app_setting($key, $value) {
    global $app_settings;
    $app_settings[$key] = $value;
    
    // In a real application, you would save this to database or config file
    // For now, we'll just update the array
    return true;
}
?>