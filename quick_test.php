<?php
// Quick Test Script - Simple and Safe
// No cURL, no complex API calls, just basic database check

echo "<h2>Quick Database Test</h2>";
echo "<p>Testing basic functionality...</p>";

// Test 1: Database Connection
echo "<h3>1. Database Connection:</h3>";
try {
    require_once 'config.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please check config.php and MySQL!</strong></p>";
    exit;
}

// Test 2: Check Tables
echo "<h3>2. Database Tables:</h3>";
try {
    $tables = ['users', 'donatur', 'kunjungan'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p>✅ Table '$table' exists ($count records)</p>";
        } else {
            echo "<p>❌ Table '$table' does not exist</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Table check failed: " . $e->getMessage() . "</p>";
}

// Test 3: Sample Data
echo "<h3>3. Sample Data:</h3>";
try {
    // Check users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>Users: $userCount records</p>";
    
    if ($userCount > 0) {
        $users = $pdo->query("SELECT name, email, role FROM users LIMIT 2")->fetchAll();
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>{$user['name']} ({$user['email']}) - {$user['role']}</li>";
        }
        echo "</ul>";
    }
    
    // Check donatur
    $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    echo "<p>Donatur: $donaturCount records</p>";
    
    if ($donaturCount > 0) {
        $donatur = $pdo->query("SELECT nama, kategori FROM donatur LIMIT 2")->fetchAll();
        echo "<ul>";
        foreach ($donatur as $d) {
            echo "<li>{$d['nama']} ({$d['kategori']})</li>";
        }
        echo "</ul>";
    }
    
    // Check kunjungan
    $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
    echo "<p>Kunjungan: $kunjunganCount records</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Data check failed: " . $e->getMessage() . "</p>";
}

// Test 4: Basic Query Test
echo "<h3>4. Basic Query Test:</h3>";
try {
    $today = date('Y-m-d');
    
    // Today's kunjungan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayKunjungan = $stmt->fetchColumn();
    
    // Active fundraisers
    $activeFundraisers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    
    echo "<p>Today's kunjungan: $todayKunjungan</p>";
    echo "<p>Active fundraisers: $activeFundraisers</p>";
    echo "<p>✅ Basic queries working</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Query test failed: " . $e->getMessage() . "</p>";
}

// Test 5: File Check
echo "<h3>5. File Check:</h3>";
$files = [
    'config.php' => 'Database configuration',
    'api/users.php' => 'Users API',
    'api/donatur.php' => 'Donatur API',
    'api/dashboard.php' => 'Dashboard API',
    'index.php' => 'Main application',
    'dashboard.php' => 'Dashboard page'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p>✅ $description ($file) exists</p>";
    } else {
        echo "<p>❌ $description ($file) missing</p>";
    }
}

echo "<h3>🎯 Summary:</h3>";
echo "<p>If all tests show ✅, your database is working.</p>";
echo "<p>If any test shows ❌, please check the issues.</p>";

if ($userCount == 0) {
    echo "<p><strong>⚠️ No users found! Please run setup_database.php</strong></p>";
}

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If database is empty, run: <a href='setup_database.php'>setup_database.php</a></li>";
echo "<li>If everything looks good, try: <a href='index.php'>index.php</a></li>";
echo "<li>Login with: ahmad.rizki@fundraising.com / password</li>";
echo "</ol>";

echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
