<?php
// Test API Script
// Run this script to test if API endpoints are working correctly

echo "<h2>API Test Script</h2>";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<h3>Testing API Endpoints:</h3>";

// Test database connection first
echo "<h4>0. Testing Database Connection:</h4>";
try {
    require_once 'config.php';
    
    // Test basic queries
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    
    echo "<p>✅ Database connection working</p>";
    echo "<ul>";
    echo "<li>Users in database: $userCount</li>";
    echo "<li>Donatur in database: $donaturCount</li>";
    echo "<li>Kunjungan in database: $kunjunganCount</li>";
    echo "</ul>";
    
    // Test table structure
    $userColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    $donaturColumns = $pdo->query("DESCRIBE donatur")->fetchAll(PDO::FETCH_COLUMN);
    $kunjunganColumns = $pdo->query("DESCRIBE kunjungan")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>✅ Table structure verified</p>";
    echo "<ul>";
    echo "<li>Users table columns: " . implode(', ', $userColumns) . "</li>";
    echo "<li>Donatur table columns: " . implode(', ', $donaturColumns) . "</li>";
    echo "<li>Kunjungan table columns: " . implode(', ', $kunjunganColumns) . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please run setup_database.php first!</strong></p>";
    exit;
}

// Test users API
echo "<h4>1. Testing Users API:</h4>";
try {
    // Set the working directory for API includes
    $originalDir = getcwd();
    chdir(__DIR__ . '/api');
    
    ob_start();
    include 'users.php';
    $usersResponse = ob_get_clean();
    
    // Restore original directory
    chdir($originalDir);
    
    $usersData = json_decode($usersResponse, true);
    
    if ($usersData['success']) {
        echo "<p>✅ Users API working - Found " . count($usersData['data']) . " users</p>";
        echo "<ul>";
        foreach (array_slice($usersData['data'], 0, 3) as $user) {
            echo "<li>{$user['nama']} ({$user['email']}) - Role: {$user['role']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Users API failed: " . $usersData['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Users API error: " . $e->getMessage() . "</p>";
}

// Test donatur API
echo "<h4>2. Testing Donatur API:</h4>";
try {
    // Set the working directory for API includes
    $originalDir = getcwd();
    chdir(__DIR__ . '/api');
    
    ob_start();
    include 'donatur.php';
    $donaturResponse = ob_get_clean();
    
    // Restore original directory
    chdir($originalDir);
    
    $donaturData = json_decode($donaturResponse, true);
    
    if ($donaturData['success']) {
        echo "<p>✅ Donatur API working - Found " . count($donaturData['data']) . " donatur</p>";
        echo "<ul>";
        foreach (array_slice($donaturData['data'], 0, 3) as $donatur) {
            echo "<li>{$donatur['nama']} ({$donatur['kategori']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Donatur API failed: " . $donaturData['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Donatur API error: " . $e->getMessage() . "</p>";
}

// Test kunjungan API
echo "<h4>3. Testing Kunjungan API:</h4>";
try {
    // Set the working directory for API includes
    $originalDir = getcwd();
    chdir(__DIR__ . '/api');
    
    ob_start();
    include 'kunjungan.php';
    $kunjunganResponse = ob_get_clean();
    
    // Restore original directory
    chdir($originalDir);
    
    $kunjunganData = json_decode($kunjunganResponse, true);
    
    if ($kunjunganData['success']) {
        echo "<p>✅ Kunjungan API working - Found " . count($kunjunganData['data']) . " kunjungan</p>";
        echo "<ul>";
        foreach (array_slice($kunjunganData['data'], 0, 3) as $kunjungan) {
            echo "<li>{$kunjungan['fundraiser_nama']} → {$kunjungan['donatur_nama']} ({$kunjungan['status']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Kunjungan API failed: " . $kunjunganData['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Kunjungan API error: " . $e->getMessage() . "</p>";
}

// Test dashboard API
echo "<h4>4. Testing Dashboard API:</h4>";
try {
    // Set the working directory for API includes
    $originalDir = getcwd();
    chdir(__DIR__ . '/api');
    
    ob_start();
    include 'dashboard.php';
    $dashboardResponse = ob_get_clean();
    
    // Restore original directory
    chdir($originalDir);
    
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData['success']) {
        echo "<p>✅ Dashboard API working</p>";
        echo "<ul>";
        echo "<li>Total Kunjungan Hari Ini: " . $dashboardData['stats']['total_kunjungan_hari_ini'] . "</li>";
        echo "<li>Donasi Berhasil: " . $dashboardData['stats']['donasi_berhasil_hari_ini'] . "</li>";
        echo "<li>Total Donasi: Rp " . number_format($dashboardData['stats']['total_donasi_hari_ini'], 0, ',', '.') . "</li>";
        echo "<li>Fundraiser Aktif: " . $dashboardData['stats']['fundraiser_aktif'] . "</li>";
        echo "</ul>";
        
        if ($dashboardData['dummy_data_info']['has_dummy_data']) {
            echo "<p>⚠️ Dummy data detected: " . $dashboardData['dummy_data_info']['dummy_users_count'] . " users, " . 
                 $dashboardData['dummy_data_info']['dummy_donatur_count'] . " donatur, " . 
                 $dashboardData['dummy_data_info']['dummy_kunjungan_count'] . " kunjungan</p>";
        }
    } else {
        echo "<p>❌ Dashboard API failed: " . $dashboardData['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Dashboard API error: " . $e->getMessage() . "</p>";
}

echo "<h3>🎯 Summary:</h3>";
echo "<p>If all tests show ✅, your API and database are working correctly.</p>";
echo "<p>If any test shows ❌, please check the error messages and fix the issues.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Make sure you've run setup_database.php first</li>";
echo "<li>Check that your web server (Apache/Nginx) is running</li>";
echo "<li>Verify that PHP and MySQL are properly configured</li>";
echo "<li>Try accessing the main application at index.php</li>";
echo "</ol>";
?>
