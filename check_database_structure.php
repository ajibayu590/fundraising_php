<?php
// Check Database Structure
echo "<h2>Database Structure Check</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    // Check users table structure
    echo "<h3>1. Users Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check kunjungan table structure
    echo "<h3>2. Kunjungan Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE kunjungan");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check donatur table structure
    echo "<h3>3. Donatur Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE donatur");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check sample data
    echo "<h3>4. Sample Data Check:</h3>";
    
    // Users sample
    echo "<h4>Users Sample:</h4>";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    // Kunjungan sample
    echo "<h4>Kunjungan Sample:</h4>";
    $stmt = $pdo->query("SELECT id, fundraiser_id, donatur_id, nominal, status, created_at FROM kunjungan LIMIT 3");
    $kunjungan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($kunjungan);
    echo "</pre>";
    
    // Donatur sample
    echo "<h4>Donatur Sample:</h4>";
    $stmt = $pdo->query("SELECT id, nama, hp, email FROM donatur LIMIT 3");
    $donatur = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($donatur);
    echo "</pre>";
    
    // Test today's data query
    echo "<h3>5. Today's Data Test:</h3>";
    $today = date('Y-m-d');
    echo "<p>Today: $today</p>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayCount = $stmt->fetchColumn();
    echo "<p>Today's kunjungan count: $todayCount</p>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $todaySuccess = $stmt->fetchColumn();
    echo "<p>Today's successful donations: $todaySuccess</p>";
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $todayTotal = $stmt->fetchColumn();
    echo "<p>Today's total donation: Rp " . number_format($todayTotal, 0, ',', '.') . "</p>";
    
    // Test recent activities query
    echo "<h3>6. Recent Activities Test:</h3>";
    $stmt = $pdo->prepare("
        SELECT k.*, u.name as fundraiser_name, d.nama as donatur_name 
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        ORDER BY k.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($recent);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
