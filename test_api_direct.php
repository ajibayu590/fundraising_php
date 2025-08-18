<?php
// Direct API Test Script
echo "<h2>Direct API Test</h2>";
echo "<p>Testing API endpoints directly without JavaScript...</p>";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<h3>Session Info:</h3>";
echo "<ul>";
echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
echo "<li>User Role: " . $_SESSION['user_role'] . "</li>";
echo "<li>Session Status: " . session_status() . "</li>";
echo "</ul>";

// Test 1: Dashboard API
echo "<h3>1. Testing Dashboard API</h3>";
try {
    ob_start();
    include 'api/dashboard.php';
    $dashboardResponse = ob_get_clean();
    
    echo "<p><strong>Raw Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($dashboardResponse) . "</pre>";
    
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData && $dashboardData['success']) {
        echo "<p style='color: green;'>✅ Dashboard API Working</p>";
        echo "<h4>Dashboard Stats:</h4>";
        echo "<ul>";
        echo "<li>Total Kunjungan: " . $dashboardData['stats']['total_kunjungan_hari_ini'] . "</li>";
        echo "<li>Donasi Berhasil: " . $dashboardData['stats']['donasi_berhasil_hari_ini'] . "</li>";
        echo "<li>Total Donasi: Rp " . number_format($dashboardData['stats']['total_donasi_hari_ini'], 0, ',', '.') . "</li>";
        echo "<li>Fundraiser Aktif: " . $dashboardData['stats']['fundraiser_aktif'] . "</li>";
        echo "</ul>";
        
        if ($dashboardData['progress']) {
            echo "<h4>Progress Data (" . count($dashboardData['progress']) . " items):</h4>";
            foreach (array_slice($dashboardData['progress'], 0, 3) as $progress) {
                echo "<li>{$progress['name']} - {$progress['current']}/{$progress['target']}</li>";
            }
        }
        
        if ($dashboardData['recent_activities']) {
            echo "<h4>Recent Activities (" . count($dashboardData['recent_activities']) . " items):</h4>";
            foreach (array_slice($dashboardData['recent_activities'], 0, 3) as $activity) {
                echo "<li>{$activity['description']} - {$activity['time']}</li>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Dashboard API Failed</p>";
        echo "<p>Error: " . ($dashboardData['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Dashboard API Error: " . $e->getMessage() . "</p>";
}

// Test 2: Users API
echo "<h3>2. Testing Users API</h3>";
try {
    ob_start();
    include 'api/users.php';
    $usersResponse = ob_get_clean();
    
    $usersData = json_decode($usersResponse, true);
    
    if ($usersData && $usersData['success']) {
        echo "<p style='color: green;'>✅ Users API Working</p>";
        echo "<p>Total Users: " . count($usersData['data']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Users API Failed</p>";
        echo "<p>Error: " . ($usersData['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Users API Error: " . $e->getMessage() . "</p>";
}

// Test 3: Donatur API
echo "<h3>3. Testing Donatur API</h3>";
try {
    ob_start();
    include 'api/donatur.php';
    $donaturResponse = ob_get_clean();
    
    $donaturData = json_decode($donaturResponse, true);
    
    if ($donaturData && $donaturData['success']) {
        echo "<p style='color: green;'>✅ Donatur API Working</p>";
        echo "<p>Total Donatur: " . count($donaturData['data']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Donatur API Failed</p>";
        echo "<p>Error: " . ($donaturData['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Donatur API Error: " . $e->getMessage() . "</p>";
}

// Test 4: Kunjungan API
echo "<h3>4. Testing Kunjungan API</h3>";
try {
    ob_start();
    include 'api/kunjungan.php';
    $kunjunganResponse = ob_get_clean();
    
    $kunjunganData = json_decode($kunjunganResponse, true);
    
    if ($kunjunganData && $kunjunganData['success']) {
        echo "<p style='color: green;'>✅ Kunjungan API Working</p>";
        echo "<p>Total Kunjungan: " . count($kunjunganData['data']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Kunjungan API Failed</p>";
        echo "<p>Error: " . ($kunjunganData['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Kunjungan API Error: " . $e->getMessage() . "</p>";
}

// Test 5: Check if data exists for today
echo "<h3>5. Checking Today's Data</h3>";
try {
    require_once 'config.php';
    
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayCount = $stmt->fetchColumn();
    
    echo "<p>Today's kunjungan count: $todayCount</p>";
    
    if ($todayCount == 0) {
        echo "<p style='color: orange;'>⚠️ No data for today! This is why dashboard shows 0.</p>";
        echo "<p><a href='insert_today_data.php'>Insert Today's Data</a></p>";
    } else {
        echo "<p style='color: green;'>✅ Data exists for today</p>";
    }
    
    // Check total data
    $totalKunjungan = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalDonatur = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    
    echo "<p>Total data in database:</p>";
    echo "<ul>";
    echo "<li>Users: $totalUsers</li>";
    echo "<li>Donatur: $totalDonatur</li>";
    echo "<li>Kunjungan: $totalKunjungan</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database check error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='debug_dashboard.php'>Go to Debug Dashboard</a></li>";
echo "<li><a href='insert_today_data.php'>Insert Today's Data</a></li>";
echo "<li><a href='dashboard.php'>Go to Normal Dashboard</a></li>";
echo "</ol>";
?>
