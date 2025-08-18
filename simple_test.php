<?php
// Simple Test Script
// Direct database and API testing without path issues

echo "<h2>Simple API & Database Test</h2>";

// Test database connection
echo "<h3>1. Database Connection Test:</h3>";
try {
    require_once 'config.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Test if tables exist
    $tables = ['users', 'donatur', 'kunjungan'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' does not exist</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please run setup_database.php first!</strong></p>";
    exit;
}

// Test data count
echo "<h3>2. Data Count Test:</h3>";
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    
    echo "<p>Users: $userCount</p>";
    echo "<p>Donatur: $donaturCount</p>";
    echo "<p>Kunjungan: $kunjunganCount</p>";
    
    if ($userCount == 0) {
        echo "<p>⚠️ No users found. Please run setup_database.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Data count failed: " . $e->getMessage() . "</p>";
}

// Test sample data
echo "<h3>3. Sample Data Test:</h3>";
try {
    // Test users
    $users = $pdo->query("SELECT id, name, email, role FROM users LIMIT 3")->fetchAll();
    echo "<h4>Sample Users:</h4>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>{$user['name']} ({$user['email']}) - {$user['role']}</li>";
    }
    echo "</ul>";
    
    // Test donatur
    $donatur = $pdo->query("SELECT id, nama, kategori FROM donatur LIMIT 3")->fetchAll();
    echo "<h4>Sample Donatur:</h4>";
    echo "<ul>";
    foreach ($donatur as $d) {
        echo "<li>{$d['nama']} ({$d['kategori']})</li>";
    }
    echo "</ul>";
    
    // Test kunjungan
    $kunjungan = $pdo->query("
        SELECT k.id, k.status, k.nominal, u.name as fundraiser, d.nama as donatur 
        FROM kunjungan k 
        JOIN users u ON k.fundraiser_id = u.id 
        JOIN donatur d ON k.donatur_id = d.id 
        LIMIT 3
    ")->fetchAll();
    echo "<h4>Sample Kunjungan:</h4>";
    echo "<ul>";
    foreach ($kunjungan as $k) {
        echo "<li>{$k['fundraiser']} → {$k['donatur']} ({$k['status']}) - Rp " . number_format($k['nominal'], 0, ',', '.') . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Sample data test failed: " . $e->getMessage() . "</p>";
}

// Test dashboard statistics
echo "<h3>4. Dashboard Statistics Test:</h3>";
try {
    $today = date('Y-m-d');
    
    // Total kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $totalKunjunganHariIni = $stmt->fetchColumn();
    
    // Donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $donasiBerhasilHariIni = $stmt->fetchColumn();
    
    // Total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $totalDonasiHariIni = $stmt->fetchColumn();
    
    // Fundraiser aktif
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $fundraiserAktif = $stmt->fetchColumn();
    
    echo "<p>Total Kunjungan Hari Ini: $totalKunjunganHariIni</p>";
    echo "<p>Donasi Berhasil: $donasiBerhasilHariIni</p>";
    echo "<p>Total Donasi: Rp " . number_format($totalDonasiHariIni, 0, ',', '.') . "</p>";
    echo "<p>Fundraiser Aktif: $fundraiserAktif</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Dashboard statistics failed: " . $e->getMessage() . "</p>";
}

// Test API endpoints using cURL
echo "<h3>5. API Endpoints Test (cURL):</h3>";

// Start session for API testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

// Test users API
echo "<h4>Users API:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/users.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "<p>✅ Users API working - Found " . count($data['data']) . " users</p>";
    } else {
        echo "<p>❌ Users API failed: " . $data['message'] . "</p>";
    }
} else {
    echo "<p>❌ Users API HTTP error: $httpCode</p>";
}

// Test donatur API
echo "<h4>Donatur API:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/donatur.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "<p>✅ Donatur API working - Found " . count($data['data']) . " donatur</p>";
    } else {
        echo "<p>❌ Donatur API failed: " . $data['message'] . "</p>";
    }
} else {
    echo "<p>❌ Donatur API HTTP error: $httpCode</p>";
}

// Test dashboard API
echo "<h4>Dashboard API:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/dashboard.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "<p>✅ Dashboard API working</p>";
        echo "<p>Stats: " . $data['stats']['total_kunjungan_hari_ini'] . " kunjungan, " . 
             $data['stats']['donasi_berhasil_hari_ini'] . " berhasil</p>";
    } else {
        echo "<p>❌ Dashboard API failed: " . $data['message'] . "</p>";
    }
} else {
    echo "<p>❌ Dashboard API HTTP error: $httpCode</p>";
}

echo "<h3>🎯 Summary:</h3>";
echo "<p>If all tests show ✅, your system is working correctly.</p>";
echo "<p>If any test shows ❌, please check the error messages.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If database tests fail, run setup_database.php</li>";
echo "<li>If API tests fail, check your web server configuration</li>";
echo "<li>Try accessing the main application at index.php</li>";
echo "</ol>";
?>
