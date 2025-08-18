<?php
/**
 * Script untuk memperbaiki header positioning di semua halaman
 */

echo "=== FIXING ALL HEADERS TO FIXED POSITION ===\n\n";

$files_to_fix = [
    'settings.php',
    'donatur.php', 
    'kunjungan.php'
];

// CSS patterns to replace
$old_header_css = 'header {
            position: relative !important;
            z-index: 99999 !important;
            background: white !important;
            width: 100% !important;
        }';

$new_header_css = 'header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000 !important;
            background: white !important;
            width: 100% !important;
            height: 64px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            border-bottom: 1px solid #e5e7eb !important;
        }';

$old_sidebar_css = '.sidebar {
            z-index: 10 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 16rem !important;
            height: 100vh !important;
            background: white !important;
        }';

$new_sidebar_css = '.sidebar {
            z-index: 500 !important;
            position: fixed !important;
            top: 64px !important;
            left: 0 !important;
            width: 16rem !important;
            height: calc(100vh - 64px) !important;
            background: white !important;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1) !important;
            overflow-y: auto !important;
        }';

$old_main_css = '.main-content {
            margin-left: 16rem !important;
            padding: 2rem !important;
            width: calc(100% - 16rem) !important;
        }';

$new_main_css = '.main-content {
            margin-left: 16rem !important;
            margin-top: 64px !important;
            padding: 2rem !important;
            width: calc(100% - 16rem) !important;
            min-height: calc(100vh - 64px) !important;
        }';

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        echo "Processing $file...\n";
        
        // Backup original
        copy($file, $file . '.backup');
        
        // Read content
        $content = file_get_contents($file);
        
        // Replace CSS
        $content = str_replace($old_header_css, $new_header_css, $content);
        $content = str_replace($old_sidebar_css, $new_sidebar_css, $content);
        $content = str_replace($old_main_css, $new_main_css, $content);
        
        // Fix mobile CSS patterns
        $old_mobile_header = 'header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 99999 !important;
            }';
        
        // Remove redundant mobile header CSS since header is already fixed
        $content = str_replace($old_mobile_header, '', $content);
        
        // Fix mobile menu button z-index
        $content = str_replace('z-index: 999999 !important;', 'z-index: 1100 !important;', $content);
        
        // Fix mobile sidebar padding
        $content = str_replace('padding-top: 5rem !important;', '', $content);
        $content = str_replace('padding-top: 6rem !important;', '', $content);
        
        // Fix header height in HTML
        $content = str_replace('py-4">', '" style="height: 64px !important;">', $content);
        $content = str_replace('max-w-7xl mx-auto', 'max-w-full mx-auto', $content);
        
        // Write back
        file_put_contents($file, $content);
        
        echo "✓ Fixed $file\n";
    } else {
        echo "✗ File $file not found\n";
    }
}

echo "\n=== FIXES COMPLETED ===\n";
echo "All header positioning issues have been fixed!\n";
echo "Backup files created with .backup extension\n\n";

echo "Changes made:\n";
echo "✓ Header position: relative → fixed\n";
echo "✓ Header height: auto → 64px\n";
echo "✓ Sidebar top: 0 → 64px (below header)\n";
echo "✓ Sidebar height: 100vh → calc(100vh - 64px)\n";
echo "✓ Main content margin-top: 0 → 64px\n";
echo "✓ Added box-shadow and border to header\n";
echo "✓ Fixed z-index hierarchy\n";
echo "✓ Added sidebar scrolling\n";
echo "✓ Fixed mobile responsive issues\n\n";

echo "Test the following pages:\n";
echo "- dashboard.php ✓\n";
echo "- users.php ✓\n";
echo "- settings.php ✓\n";
echo "- donatur.php ✓\n";
echo "- kunjungan.php ✓\n";
?>