<?php
// Test Dashboard API Directly
echo "<h2>Dashboard API Test</h2>";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<p>Testing dashboard API...</p>";

try {
    // Include dashboard API directly
    ob_start();
    include 'api/dashboard.php';
    $response = ob_get_clean();
    
    echo "<h3>Raw API Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "<h3>✅ API Response Parsed Successfully</h3>";
        
        echo "<h4>Dashboard Stats:</h4>";
        echo "<ul>";
        echo "<li>Total Kunjungan Hari Ini: " . $data['stats']['total_kunjungan_hari_ini'] . "</li>";
        echo "<li>Donasi Berhasil: " . $data['stats']['donasi_berhasil_hari_ini'] . "</li>";
        echo "<li>Total Donasi: Rp " . number_format($data['stats']['total_donasi_hari_ini'], 0, ',', '.') . "</li>";
        echo "<li>Fundraiser Aktif: " . $data['stats']['fundraiser_aktif'] . "</li>";
        echo "</ul>";
        
        if ($data['progress']) {
            echo "<h4>Progress Data (" . count($data['progress']) . " items):</h4>";
            echo "<ul>";
            foreach (array_slice($data['progress'], 0, 3) as $progress) {
                echo "<li>{$progress['name']} - {$progress['current']}/{$progress['target']}</li>";
            }
            echo "</ul>";
        }
        
        if ($data['recent_activities']) {
            echo "<h4>Recent Activities (" . count($data['recent_activities']) . " items):</h4>";
            echo "<ul>";
            foreach (array_slice($data['recent_activities'], 0, 3) as $activity) {
                echo "<li>{$activity['description']} - {$activity['time']}</li>";
            }
            echo "</ul>";
        }
        
        if ($data['dummy_data_info']) {
            echo "<h4>Dummy Data Info:</h4>";
            echo "<ul>";
            echo "<li>Has Dummy Data: " . ($data['dummy_data_info']['has_dummy_data'] ? 'Yes' : 'No') . "</li>";
            echo "<li>Dummy Users: " . $data['dummy_data_info']['dummy_users_count'] . "</li>";
            echo "<li>Dummy Donatur: " . $data['dummy_data_info']['dummy_donatur_count'] . "</li>";
            echo "<li>Dummy Kunjungan: " . $data['dummy_data_info']['dummy_kunjungan_count'] . "</li>";
            echo "</ul>";
        }
        
    } else {
        echo "<h3>❌ API Response Failed</h3>";
        echo "<p>Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ API Test Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='check_data.php'>Check Database Data</a></li>";
echo "<li><a href='setup_database.php'>Setup Database</a></li>";
echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
echo "</ol>";
?>
