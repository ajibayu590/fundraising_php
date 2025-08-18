<?php
// Comprehensive test untuk semua halaman yang sudah diperbaiki
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test All Pages</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";

echo "<h1 class='text-3xl font-bold mb-6'>🧪 TEST ALL PAGES - COMPREHENSIVE</h1>";

// Test 1: Database Connection
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-bold mb-4'>1. 🔌 Database Connection</h2>";
try {
    if ($pdo) {
        echo "<p class='text-green-600'>✅ Database connected successfully</p>";
        
        // Test basic queries
        $tables = ['users', 'donatur', 'kunjungan', 'settings'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "<p class='text-blue-600'>📊 Table '$table': $count records</p>";
        }
    } else {
        echo "<p class='text-red-600'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Database Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: User Data by Role
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-bold mb-4'>2. 👥 User Data by Role</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            role, 
            COUNT(*) as count,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif
        FROM users 
        GROUP BY role 
        ORDER BY role
    ");
    $stmt->execute();
    $roleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roleData as $data) {
        $icon = $data['role'] === 'admin' ? '👑' : ($data['role'] === 'monitor' ? '👁️' : '👤');
        echo "<div class='flex justify-between items-center py-2 border-b'>";
        echo "<span class='font-medium'>$icon {$data['role']}</span>";
        echo "<span class='text-sm text-gray-600'>{$data['count']} total ({$data['aktif']} aktif)</span>";
        echo "</div>";
    }
    
    // Test fundraiser specifically
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'user' ORDER BY name LIMIT 5");
    $stmt->execute();
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='mt-4 p-3 bg-blue-50 rounded'>";
    echo "<h3 class='font-semibold'>📋 Sample Fundraisers:</h3>";
    if (count($fundraisers) > 0) {
        foreach ($fundraisers as $f) {
            echo "<p class='text-sm'>• {$f['name']} (Target: {$f['target']}/hari, Status: {$f['status']})</p>";
        }
    } else {
        echo "<p class='text-yellow-600'>⚠️ No fundraiser data found</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Page Files
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-bold mb-4'>3. 📁 Page Files Status</h2>";

$pages = [
    'dashboard.php' => 'Dashboard',
    'fundraiser.php' => 'Fundraiser Management', 
    'admin-users.php' => 'Admin Users Management',
    'users.php' => 'Users (Original)',
    'donatur.php' => 'Donatur Management',
    'kunjungan.php' => 'Kunjungan Management',
    'settings.php' => 'Settings',
    'sidebar-admin.php' => 'Admin Sidebar',
    'sidebar-user.php' => 'User Sidebar',
    'layout-header.php' => 'Header Template',
    'layout-footer.php' => 'Footer Template'
];

foreach ($pages as $file => $name) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    $status = $exists ? '✅' : '❌';
    $sizeText = $exists ? number_format($size) . ' bytes' : 'Not found';
    
    echo "<div class='flex justify-between items-center py-2 border-b'>";
    echo "<span>$status <strong>$name</strong></span>";
    echo "<span class='text-sm text-gray-600'>$file ($sizeText)</span>";
    echo "</div>";
}
echo "</div>";

// Test 4: Quick Links
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-bold mb-4'>4. 🔗 Quick Test Links</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-3 gap-4'>";

$testLinks = [
    'fundraiser-debug.php' => '🧪 Fundraiser Debug',
    'fundraiser.php' => '📊 Fundraiser Management',
    'admin-users.php' => '👑 Admin Users',
    'dashboard.php' => '🏠 Dashboard',
    'donatur.php' => '👥 Donatur',
    'kunjungan.php' => '📍 Kunjungan'
];

foreach ($testLinks as $link => $name) {
    if (file_exists($link)) {
        echo "<a href='$link' class='block p-3 bg-blue-100 rounded-lg hover:bg-blue-200 text-center text-sm font-medium'>$name</a>";
    } else {
        echo "<div class='block p-3 bg-gray-100 rounded-lg text-center text-sm text-gray-500'>$name (Not Found)</div>";
    }
}
echo "</div>";
echo "</div>";

// Test 5: Expected Results
echo "<div class='bg-green-50 border border-green-200 rounded-lg p-6'>";
echo "<h2 class='text-xl font-bold text-green-800 mb-4'>5. ✅ EXPECTED RESULTS</h2>";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmt->execute();
$fundraiserCount = $stmt->fetchColumn();

if ($fundraiserCount > 0) {
    echo "<div class='space-y-2'>";
    echo "<p class='text-green-700'>✅ <strong>fundraiser.php</strong> akan menampilkan $fundraiserCount fundraiser</p>";
    echo "<p class='text-green-700'>✅ <strong>Tabel langsung muncul</strong> tanpa perlu klik</p>";
    echo "<p class='text-green-700'>✅ <strong>Target kunjungan</strong> visible untuk setiap fundraiser</p>";
    echo "<p class='text-green-700'>✅ <strong>Progress bars</strong> menunjukkan achievement</p>";
    echo "<p class='text-green-700'>✅ <strong>Stats cards</strong> menunjukkan summary</p>";
    echo "</div>";
} else {
    echo "<div class='space-y-2'>";
    echo "<p class='text-yellow-700'>⚠️ <strong>No fundraiser data</strong> - halaman akan show empty state</p>";
    echo "<p class='text-blue-700'>💡 <strong>Solution:</strong> Insert dummy data dari dashboard admin</p>";
    echo "<p class='text-blue-700'>🔧 <strong>Action:</strong> Login admin → Dashboard → Insert Data Dummy</p>";
    echo "</div>";
}

echo "</div>";

echo "<div class='mt-8 text-center'>";
echo "<p class='text-gray-600'>Test completed. Check the links above to verify functionality.</p>";
echo "</div>";

echo "</body></html>";
?>