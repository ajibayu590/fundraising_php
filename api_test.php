<?php
// Simple API Test - Direct Include Method
// Test API endpoints by directly including them

echo "<h2>Simple API Test</h2>";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<p>Testing API endpoints...</p>";

// Test 1: Users API
echo "<h3>1. Users API Test:</h3>";
try {
    // Capture output from API
    ob_start();
    include 'api/users.php';
    $usersResponse = ob_get_clean();
    
    $usersData = json_decode($usersResponse, true);
    
    if ($usersData && $usersData['success']) {
        echo "<p>✅ Users API working</p>";
        echo "<p>Found " . count($usersData['data']) . " users</p>";
        
        // Show first 2 users
        echo "<ul>";
        foreach (array_slice($usersData['data'], 0, 2) as $user) {
            echo "<li>{$user['nama']} ({$user['email']}) - {$user['role']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Users API failed</p>";
        echo "<p>Response: " . htmlspecialchars($usersResponse) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Users API error: " . $e->getMessage() . "</p>";
}

// Test 2: Donatur API
echo "<h3>2. Donatur API Test:</h3>";
try {
    ob_start();
    include 'api/donatur.php';
    $donaturResponse = ob_get_clean();
    
    $donaturData = json_decode($donaturResponse, true);
    
    if ($donaturData && $donaturData['success']) {
        echo "<p>✅ Donatur API working</p>";
        echo "<p>Found " . count($donaturData['data']) . " donatur</p>";
        
        // Show first 2 donatur
        echo "<ul>";
        foreach (array_slice($donaturData['data'], 0, 2) as $donatur) {
            echo "<li>{$donatur['nama']} ({$donatur['kategori']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Donatur API failed</p>";
        echo "<p>Response: " . htmlspecialchars($donaturResponse) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Donatur API error: " . $e->getMessage() . "</p>";
}

// Test 3: Dashboard API
echo "<h3>3. Dashboard API Test:</h3>";
try {
    ob_start();
    include 'api/dashboard.php';
    $dashboardResponse = ob_get_clean();
    
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData && $dashboardData['success']) {
        echo "<p>✅ Dashboard API working</p>";
        echo "<p>Stats:</p>";
        echo "<ul>";
        echo "<li>Kunjungan hari ini: " . $dashboardData['stats']['total_kunjungan_hari_ini'] . "</li>";
        echo "<li>Donasi berhasil: " . $dashboardData['stats']['donasi_berhasil_hari_ini'] . "</li>";
        echo "<li>Total donasi: Rp " . number_format($dashboardData['stats']['total_donasi_hari_ini'], 0, ',', '.') . "</li>";
        echo "<li>Fundraiser aktif: " . $dashboardData['stats']['fundraiser_aktif'] . "</li>";
        echo "</ul>";
        
        if ($dashboardData['dummy_data_info']['has_dummy_data']) {
            echo "<p>⚠️ Dummy data detected</p>";
        }
    } else {
        echo "<p>❌ Dashboard API failed</p>";
        echo "<p>Response: " . htmlspecialchars($dashboardResponse) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Dashboard API error: " . $e->getMessage() . "</p>";
}

// Test 4: Kunjungan API
echo "<h3>4. Kunjungan API Test:</h3>";
try {
    ob_start();
    include 'api/kunjungan.php';
    $kunjunganResponse = ob_get_clean();
    
    $kunjunganData = json_decode($kunjunganResponse, true);
    
    if ($kunjunganData && $kunjunganData['success']) {
        echo "<p>✅ Kunjungan API working</p>";
        echo "<p>Found " . count($kunjunganData['data']) . " kunjungan</p>";
        
        // Show first 2 kunjungan
        echo "<ul>";
        foreach (array_slice($kunjunganData['data'], 0, 2) as $kunjungan) {
            echo "<li>{$kunjungan['fundraiser_nama']} → {$kunjungan['donatur_nama']} ({$kunjungan['status']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Kunjungan API failed</p>";
        echo "<p>Response: " . htmlspecialchars($kunjunganResponse) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Kunjungan API error: " . $e->getMessage() . "</p>";
}

echo "<h3>🎯 Summary:</h3>";
echo "<p>If all APIs show ✅, your system is working correctly.</p>";
echo "<p>If any API shows ❌, check the error messages above.</p>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If APIs fail, check database with <a href='quick_test.php'>quick_test.php</a></li>";
echo "<li>If database is empty, run <a href='setup_database.php'>setup_database.php</a></li>";
echo "<li>If everything works, try <a href='index.php'>index.php</a></li>";
echo "</ol>";

echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
