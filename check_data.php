<?php
// Simple Data Check Script
echo "<h2>Database Data Check</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    // Check users
    echo "<h3>Users Table:</h3>";
    $users = $pdo->query("SELECT id, name, email, role FROM users")->fetchAll();
    echo "<p>Total users: " . count($users) . "</p>";
    if (count($users) > 0) {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']} - {$user['name']} ({$user['email']}) - {$user['role']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No users found!</p>";
    }
    
    // Check donatur
    echo "<h3>Donatur Table:</h3>";
    $donatur = $pdo->query("SELECT id, nama, email, kategori FROM donatur")->fetchAll();
    echo "<p>Total donatur: " . count($donatur) . "</p>";
    if (count($donatur) > 0) {
        echo "<ul>";
        foreach ($donatur as $d) {
            echo "<li>ID: {$d['id']} - {$d['nama']} ({$d['email']}) - {$d['kategori']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No donatur found!</p>";
    }
    
    // Check kunjungan
    echo "<h3>Kunjungan Table:</h3>";
    $kunjungan = $pdo->query("SELECT id, fundraiser_id, donatur_id, status, nominal, created_at FROM kunjungan")->fetchAll();
    echo "<p>Total kunjungan: " . count($kunjungan) . "</p>";
    if (count($kunjungan) > 0) {
        echo "<ul>";
        foreach (array_slice($kunjungan, 0, 5) as $k) {
            echo "<li>ID: {$k['id']} - Fundraiser: {$k['fundraiser_id']} → Donatur: {$k['donatur_id']} - {$k['status']} - Rp " . number_format($k['nominal'], 0, ',', '.') . " - {$k['created_at']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No kunjungan found!</p>";
    }
    
    // Check today's data
    echo "<h3>Today's Data:</h3>";
    $today = date('Y-m-d');
    $todayKunjungan = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $todayKunjungan->execute([$today]);
    $todayCount = $todayKunjungan->fetchColumn();
    echo "<p>Today's kunjungan: $todayCount</p>";
    
    if ($todayCount > 0) {
        $todayData = $pdo->prepare("SELECT * FROM kunjungan WHERE DATE(created_at) = ? LIMIT 3");
        $todayData->execute([$today]);
        $todayRecords = $todayData->fetchAll();
        echo "<ul>";
        foreach ($todayRecords as $record) {
            echo "<li>ID: {$record['id']} - Status: {$record['status']} - Nominal: Rp " . number_format($record['nominal'], 0, ',', '.') . "</li>";
        }
        echo "</ul>";
    }
    
    // Check if we need to run setup
    if (count($users) == 0 || count($donatur) == 0) {
        echo "<h3>⚠️ ACTION REQUIRED:</h3>";
        echo "<p>Database is empty! Please run setup_database.php</p>";
        echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
    } else {
        echo "<h3>✅ Database has data</h3>";
        echo "<p>If dashboard still shows 0, the issue might be with JavaScript or API calls.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
