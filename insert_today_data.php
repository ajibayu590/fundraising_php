<?php
// Insert Today's Data Script
echo "<h2>Insert Today's Data</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    // Check if we have users and donatur
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
    
    if ($userCount == 0 || $donaturCount == 0) {
        echo "<p>❌ No users or donatur found. Please run setup_database.php first.</p>";
        echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
        exit;
    }
    
    echo "<p>Users: $userCount, Donatur: $donaturCount</p>";
    
    // Get user IDs (fundraisers)
    $userIds = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($userIds)) {
        echo "<p>❌ No fundraiser users found.</p>";
        exit;
    }
    
    // Get donatur IDs
    $donaturIds = $pdo->query("SELECT id FROM donatur")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($donaturIds)) {
        echo "<p>❌ No donatur found.</p>";
        exit;
    }
    
    // Check if today's data already exists
    $today = date('Y-m-d');
    $todayCount = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $todayCount->execute([$today]);
    $existingToday = $todayCount->fetchColumn();
    
    if ($existingToday > 0) {
        echo "<p>✅ Today's data already exists ($existingToday records)</p>";
        echo "<p><a href='test_dashboard_api.php'>Test Dashboard API</a></p>";
        echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
        exit;
    }
    
    // Insert today's kunjungan data
    echo "<p>Inserting today's kunjungan data...</p>";
    
    $pdo->beginTransaction();
    
    $statuses = ['berhasil', 'tidak-berhasil', 'follow-up'];
    $alamatList = [
        'Jl. Sudirman No. 123, Jakarta Pusat',
        'Jl. Thamrin No. 45, Jakarta Pusat',
        'Jl. Gatot Subroto No. 67, Jakarta Selatan',
        'Jl. Rasuna Said No. 89, Jakarta Selatan',
        'Jl. Kuningan No. 12, Jakarta Selatan'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO kunjungan (fundraiser_id, donatur_id, alamat, status, nominal, catatan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $inserted = 0;
    for ($i = 0; $i < 15; $i++) {
        $userId = $userIds[array_rand($userIds)];
        $donaturId = $donaturIds[array_rand($donaturIds)];
        $status = $statuses[array_rand($statuses)];
        $alamat = $alamatList[array_rand($alamatList)];
        $nominal = ($status === 'berhasil') ? rand(50000, 500000) * 1000 : 0;
        $catatan = 'Kunjungan hari ini - ' . date('d/m/Y H:i');
        
        // Create time for today with different hours
        $hour = 8 + ($i % 10); // 8 AM to 6 PM
        $minute = rand(0, 59);
        $todayTime = date('Y-m-d') . " $hour:$minute:00";
        
        $stmt->execute([$userId, $donaturId, $alamat, $status, $nominal, $catatan, $todayTime]);
        $inserted++;
    }
    
    $pdo->commit();
    
    echo "<p>✅ Successfully inserted $inserted kunjungan records for today</p>";
    
    // Show summary
    $todayKunjungan = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $todayKunjungan->execute([$today]);
    $todayCount = $todayKunjungan->fetchColumn();
    
    $todayBerhasil = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $todayBerhasil->execute([$today]);
    $berhasilCount = $todayBerhasil->fetchColumn();
    
    $todayAmount = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $todayAmount->execute([$today]);
    $totalAmount = $todayAmount->fetchColumn();
    
    echo "<h3>Today's Summary:</h3>";
    echo "<ul>";
    echo "<li>Total Kunjungan: $todayCount</li>";
    echo "<li>Berhasil: $berhasilCount</li>";
    echo "<li>Total Donasi: Rp " . number_format($totalAmount, 0, ',', '.') . "</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='test_dashboard_api.php'>Test Dashboard API</a></li>";
    echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
    echo "<li>Refresh the dashboard page to see the data</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
