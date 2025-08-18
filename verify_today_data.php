<?php
// Verify Today's Data
echo "<h2>Verify Today's Data</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    $today = date('Y-m-d');
    echo "<h3>Today: $today</h3>";
    
    // Check all kunjungan for today
    echo "<h3>All Kunjungan for Today:</h3>";
    $stmt = $pdo->prepare("
        SELECT k.id, k.fundraiser_id, k.donatur_id, k.nominal, k.status, k.created_at,
               u.name as fundraiser_name, d.nama as donatur_name
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE DATE(k.created_at) = ?
        ORDER BY k.created_at DESC
    ");
    $stmt->execute([$today]);
    $todayKunjungan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Fundraiser</th><th>Donatur</th><th>Nominal</th><th>Status</th><th>Created At</th></tr>";
    foreach ($todayKunjungan as $kunjungan) {
        echo "<tr>";
        echo "<td>" . $kunjungan['id'] . "</td>";
        echo "<td>" . $kunjungan['fundraiser_name'] . "</td>";
        echo "<td>" . $kunjungan['donatur_name'] . "</td>";
        echo "<td>Rp " . number_format($kunjungan['nominal'], 0, ',', '.') . "</td>";
        echo "<td>" . ucfirst($kunjungan['status']) . "</td>";
        echo "<td>" . $kunjungan['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Summary
    echo "<h3>Summary:</h3>";
    $totalKunjungan = count($todayKunjungan);
    $berhasilKunjungan = array_filter($todayKunjungan, function($k) { return $k['status'] === 'berhasil'; });
    $totalDonasi = array_sum(array_column($berhasilKunjungan, 'nominal'));
    
    echo "<ul>";
    echo "<li>Total Kunjungan: $totalKunjungan</li>";
    echo "<li>Berhasil: " . count($berhasilKunjungan) . "</li>";
    echo "<li>Total Donasi: Rp " . number_format($totalDonasi, 0, ',', '.') . "</li>";
    echo "</ul>";
    
    // Check if this matches what debug dashboard should show
    echo "<h3>Debug Dashboard Should Show:</h3>";
    echo "<ul>";
    echo "<li>Total Kunjungan Hari Ini: $totalKunjungan</li>";
    echo "<li>Donasi Berhasil: " . count($berhasilKunjungan) . "</li>";
    echo "<li>Total Donasi Hari Ini: Rp " . number_format($totalDonasi, 0, ',', '.') . "</li>";
    echo "<li>Fundraiser Aktif: 5 (users with role='user')</li>";
    echo "</ul>";
    
    // Check recent activities (all time, not just today)
    echo "<h3>Recent Activities (All Time):</h3>";
    $stmt = $pdo->prepare("
        SELECT k.id, k.fundraiser_id, k.donatur_id, k.nominal, k.status, k.created_at,
               u.name as fundraiser_name, d.nama as donatur_name
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        ORDER BY k.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Fundraiser</th><th>Donatur</th><th>Nominal</th><th>Status</th><th>Date</th></tr>";
    foreach ($recentActivities as $activity) {
        echo "<tr>";
        echo "<td>" . $activity['fundraiser_name'] . "</td>";
        echo "<td>" . $activity['donatur_name'] . "</td>";
        echo "<td>Rp " . number_format($activity['nominal'], 0, ',', '.') . "</td>";
        echo "<td>" . ucfirst($activity['status']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($activity['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
