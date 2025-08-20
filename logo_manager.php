<?php
/**
 * Logo Manager for Fundraising System
 * Handles logo upload, storage, and retrieval
 */

require_once 'config.php';

// Create uploads directory if it doesn't exist
$logoDir = __DIR__ . '/uploads/logos';
if (!is_dir($logoDir)) {
    mkdir($logoDir, 0755, true);
}

/**
 * Get the current logo path
 */
function get_logo_path() {
    $logoDir = __DIR__ . '/uploads/logos';
    $logoFile = $logoDir . '/logo.png';
    $logoFileJpg = $logoDir . '/logo.jpg';
    $logoFileJpeg = $logoDir . '/logo.jpeg';
    
    if (file_exists($logoFile)) {
        return 'uploads/logos/logo.png';
    } elseif (file_exists($logoFileJpg)) {
        return 'uploads/logos/logo.jpg';
    } elseif (file_exists($logoFileJpeg)) {
        return 'uploads/logos/logo.jpeg';
    }
    
    return null; // No logo found
}

/**
 * Upload a new logo
 */
function upload_logo($file) {
    $logoDir = __DIR__ . '/uploads/logos';
    
    // Validate file
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PNG and JPEG are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 2MB.'];
    }
    
    // Remove old logo files
    $oldFiles = glob($logoDir . '/logo.*');
    foreach ($oldFiles as $oldFile) {
        unlink($oldFile);
    }
    
    // Generate new filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = 'logo.' . strtolower($extension);
    $uploadPath = $logoDir . '/' . $newFilename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'message' => 'Logo uploaded successfully.', 'path' => 'uploads/logos/' . $newFilename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload logo.'];
    }
}

/**
 * Delete current logo
 */
function delete_logo() {
    $logoDir = __DIR__ . '/uploads/logos';
    $logoFiles = glob($logoDir . '/logo.*');
    
    foreach ($logoFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    return ['success' => true, 'message' => 'Logo deleted successfully.'];
}

/**
 * Get logo HTML for display
 */
function get_logo_html($size = 'w-8 h-8', $class = '') {
    $logoPath = get_logo_path();
    
    if ($logoPath) {
        return '<img src="' . htmlspecialchars($logoPath) . '" alt="Logo" class="' . $size . ' ' . $class . '" />';
    } else {
        // Default placeholder
        return '<div class="' . $size . ' bg-blue-600 rounded-lg flex items-center justify-center ' . $class . '">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>';
    }
}

/**
 * Get logo URL
 */
function get_logo_url() {
    $logoPath = get_logo_path();
    return $logoPath ? $logoPath : null;
}
?>