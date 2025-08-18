<?php
// Test Normal Dashboard
echo "<h2>Test Normal Dashboard</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    $today = date('Y-m-d');
    echo "<h3>Today: $today</h3>";
    
    // Test the same queries as dashboard.php
    echo "<h3>Dashboard Queries Test:</h3>";
    
    // 1. Total kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $total_kunjungan = $stmt->fetchColumn();
    echo "<p>✅ Total Kunjungan Hari Ini: $total_kunjungan</p>";
    
    // 2. Donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $donasi_berhasil = $stmt->fetchColumn();
    echo "<p>✅ Donasi Berhasil: $donasi_berhasil</p>";
    
    // 3. Total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $total_donasi = $stmt->fetchColumn();
    echo "<p>✅ Total Donasi Hari Ini: Rp " . number_format($total_donasi, 0, ',', '.') . "</p>";
    
    // 4. Fundraiser aktif
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $fundraiser_aktif = $stmt->fetchColumn();
    echo "<p>✅ Fundraiser Aktif: $fundraiser_aktif</p>";
    
    // 5. Recent activities
    $stmt = $pdo->prepare("
        SELECT k.*, u.name as fundraiser_name, d.nama as donatur_name 
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        ORDER BY k.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Recent Activities: " . count($recent_activities) . " items</p>";
    
    // 6. Progress data
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.target,
               COALESCE(COUNT(k.id), 0) as current_kunjungan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id AND DATE(k.created_at) = ?
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.target
        ORDER BY u.name
    ");
    $stmt->execute([$today]);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Progress Data: " . count($progress_data) . " users</p>";
    
    // Show what dashboard should display
    echo "<h3>Dashboard Should Display:</h3>";
    echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 8px;'>";
    echo "<h4>Stats Cards:</h4>";
    echo "<ul>";
    echo "<li>Total Kunjungan Hari Ini: <strong>$total_kunjungan</strong></li>";
    echo "<li>Donasi Berhasil: <strong>$donasi_berhasil</strong></li>";
    echo "<li>Total Donasi Hari Ini: <strong>Rp " . number_format($total_donasi, 0, ',', '.') . "</strong></li>";
    echo "<li>Fundraiser Aktif: <strong>$fundraiser_aktif</strong></li>";
    echo "</ul>";
    
    echo "<h4>Progress Data:</h4>";
    if (!empty($progress_data)) {
        echo "<ul>";
        foreach ($progress_data as $progress) {
            $percentage = $progress['target'] > 0 ? min(100, ($progress['current_kunjungan'] / $progress['target']) * 100) : 0;
            echo "<li>{$progress['name']}: {$progress['current_kunjungan']}/{$progress['target']} ({$percentage}%)</li>";
        }
        echo "</ul>";
    }
    
    echo "<h4>Recent Activities:</h4>";
    if (!empty($recent_activities)) {
        echo "<ul>";
        foreach (array_slice($recent_activities, 0, 3) as $activity) {
            echo "<li>{$activity['fundraiser_name']} → {$activity['donatur_name']} - Rp " . number_format($activity['nominal'], 0, ',', '.') . " ({$activity['status']})</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='dashboard.php'>Open Normal Dashboard</a></li>";
    echo "<li>Check if data matches the expected values above</li>";
    echo "<li>If data doesn't match, there might be a caching issue</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
