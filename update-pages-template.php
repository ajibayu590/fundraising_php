<?php
/**
 * Script untuk memperbarui semua halaman menggunakan template header/footer baru
 * Jalankan sekali untuk mengkonversi semua halaman
 */

echo "=== UPDATING PAGES TO USE NEW TEMPLATE ===\n\n";

// Backup original dashboard
if (file_exists('dashboard.php')) {
    copy('dashboard.php', 'dashboard-backup.php');
    echo "✓ Backup dashboard.php ke dashboard-backup.php\n";
}

// Replace dashboard.php with new version
if (file_exists('dashboard-new.php')) {
    copy('dashboard-new.php', 'dashboard.php');
    echo "✓ Updated dashboard.php dengan template baru\n";
}

// List of pages that need to be updated
$pages_to_update = [
    'users.php',
    'donatur.php', 
    'kunjungan.php',
    'settings.php',
    'target.php',
    'analytics.php'
];

foreach ($pages_to_update as $page) {
    if (file_exists($page)) {
        // Backup original
        copy($page, str_replace('.php', '-backup.php', $page));
        echo "✓ Backup $page ke " . str_replace('.php', '-backup.php', $page) . "\n";
        
        // Read original content
        $content = file_get_contents($page);
        
        // Extract main content (remove header and footer parts)
        // This is a basic extraction - may need manual adjustment
        $lines = explode("\n", $content);
        $start_content = 0;
        $end_content = count($lines);
        
        // Find where main content starts (after HTML head)
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], '<body') !== false || strpos($lines[$i], 'main-content') !== false) {
                $start_content = $i + 1;
                break;
            }
        }
        
        // Find where main content ends (before closing body)
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], '</body>') !== false || strpos($lines[$i], '</html>') !== false) {
                $end_content = $i;
                break;
            }
        }
        
        // Create new content with template
        $page_name = ucfirst(str_replace('.php', '', $page));
        $new_content = "<?php\n";
        $new_content .= "\$page_title = \"$page_name - Fundraising System\";\n";
        $new_content .= "include 'layout-header.php';\n\n";
        $new_content .= "// Original page content will be inserted here\n";
        $new_content .= "// TODO: Manual adjustment may be needed\n\n";
        
        // Add extracted content
        for ($i = $start_content; $i < $end_content; $i++) {
            if (isset($lines[$i])) {
                $new_content .= $lines[$i] . "\n";
            }
        }
        
        $new_content .= "\n<?php include 'layout-footer.php'; ?>\n";
        
        // Write new file
        file_put_contents(str_replace('.php', '-new.php', $page), $new_content);
        echo "✓ Created " . str_replace('.php', '-new.php', $page) . " dengan template baru\n";
    }
}

echo "\n=== UPDATE SELESAI ===\n";
echo "File-file baru telah dibuat dengan suffix '-new.php'\n";
echo "File asli di-backup dengan suffix '-backup.php'\n";
echo "Silakan review file-file baru sebelum mengganti file asli.\n\n";

echo "Untuk menggunakan template baru:\n";
echo "1. Review file dashboard.php (sudah diupdate otomatis)\n";
echo "2. Review file *-new.php dan sesuaikan jika perlu\n";
echo "3. Ganti file asli dengan versi baru jika sudah OK\n";
echo "4. Test semua halaman untuk memastikan berfungsi dengan baik\n\n";

echo "Template files yang dibuat:\n";
echo "- layout-header.php (header + sidebar template)\n";
echo "- layout-footer.php (footer + scripts template)\n";
echo "- header-template.php (standalone header jika diperlukan)\n\n";

echo "Keuntungan template baru:\n";
echo "✓ Header fixed position (tidak ikut scroll)\n";
echo "✓ Sidebar tidak tertutupi header\n";
echo "✓ Mobile responsive yang lebih baik\n";
echo "✓ Konsistensi tampilan di semua halaman\n";
echo "✓ Mudah maintenance (edit satu file untuk semua halaman)\n";
?>