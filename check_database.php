<?php
// Database Check Script
// Check if database is properly set up and has data

echo "<h2>Database Check Script</h2>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL successfully</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'fundraising_db'");
    $databaseExists = $stmt->fetch();
    
    if ($databaseExists) {
        echo "<p>✅ Database 'fundraising_db' exists</p>";
        
        // Select the database
        $pdo->exec("USE fundraising_db");
        echo "<p>✅ Using database 'fundraising_db'</p>";
        
        // Check tables
        $tables = ['users', 'donatur', 'kunjungan', 'settings'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                echo "<p>✅ Table '$table' exists</p>";
                
                // Count records
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p>   - Records: $count</p>";
                
                // Show table structure
                $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                echo "<p>   - Columns: " . implode(', ', $columns) . "</p>";
            } else {
                echo "<p>❌ Table '$table' does not exist</p>";
            }
        }
        
        // Check for data
        echo "<h3>Data Check:</h3>";
        
        // Users
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($userCount > 0) {
            echo "<p>✅ Users table has $userCount records</p>";
            $users = $pdo->query("SELECT name, email, role FROM users LIMIT 3")->fetchAll();
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>{$user['name']} ({$user['email']}) - {$user['role']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ Users table is empty</p>";
        }
        
        // Donatur
        $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur")->fetchColumn();
        if ($donaturCount > 0) {
            echo "<p>✅ Donatur table has $donaturCount records</p>";
            $donatur = $pdo->query("SELECT nama, kategori FROM donatur LIMIT 3")->fetchAll();
            echo "<ul>";
            foreach ($donatur as $d) {
                echo "<li>{$d['nama']} ({$d['kategori']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ Donatur table is empty</p>";
        }
        
        // Kunjungan
        $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan")->fetchColumn();
        if ($kunjunganCount > 0) {
            echo "<p>✅ Kunjungan table has $kunjunganCount records</p>";
            $kunjungan = $pdo->query("
                SELECT k.status, k.nominal, u.name as fundraiser, d.nama as donatur 
                FROM kunjungan k 
                JOIN users u ON k.fundraiser_id = u.id 
                JOIN donatur d ON k.donatur_id = d.id 
                LIMIT 3
            ")->fetchAll();
            echo "<ul>";
            foreach ($kunjungan as $k) {
                echo "<li>{$k['fundraiser']} → {$k['donatur']} ({$k['status']}) - Rp " . number_format($k['nominal'], 0, ',', '.') . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ Kunjungan table is empty</p>";
        }
        
        // Test relationships
        echo "<h3>Relationship Test:</h3>";
        try {
            $testQuery = $pdo->query("
                SELECT k.id, u.name as fundraiser, d.nama as donatur, k.status, k.nominal
                FROM kunjungan k 
                JOIN users u ON k.fundraiser_id = u.id 
                JOIN donatur d ON k.donatur_id = d.id 
                LIMIT 1
            ");
            $result = $testQuery->fetch();
            
            if ($result) {
                echo "<p>✅ Relationships working correctly</p>";
                echo "<p>Sample: {$result['fundraiser']} → {$result['donatur']} ({$result['status']})</p>";
            } else {
                echo "<p>⚠️ No kunjungan records found for relationship test</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Relationship test failed: " . $e->getMessage() . "</p>";
        }
        
        // Test dashboard queries
        echo "<h3>Dashboard Query Test:</h3>";
        try {
            $today = date('Y-m-d');
            
            // Today's kunjungan
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
            $stmt->execute([$today]);
            $todayKunjungan = $stmt->fetchColumn();
            
            // Today's successful donations
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
            $stmt->execute([$today]);
            $todayBerhasil = $stmt->fetchColumn();
            
            // Total donation amount today
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
            $stmt->execute([$today]);
            $todayAmount = $stmt->fetchColumn();
            
            // Active fundraisers
            $activeFundraisers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
            
            echo "<p>✅ Dashboard queries working</p>";
            echo "<p>Today's kunjungan: $todayKunjungan</p>";
            echo "<p>Today's successful: $todayBerhasil</p>";
            echo "<p>Today's amount: Rp " . number_format($todayAmount, 0, ',', '.') . "</p>";
            echo "<p>Active fundraisers: $activeFundraisers</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Dashboard query test failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ Database 'fundraising_db' does not exist</p>";
        echo "<p><strong>Please run setup_database.php first!</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please check your MySQL configuration!</strong></p>";
}

echo "<h3>🎯 Summary:</h3>";
echo "<p>If all checks show ✅, your database is properly set up.</p>";
echo "<p>If any check shows ❌, please run setup_database.php.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If database doesn't exist, run setup_database.php</li>";
echo "<li>If tables are empty, run setup_database.php</li>";
echo "<li>If relationships fail, check foreign key constraints</li>";
echo "<li>Try accessing the main application at index.php</li>";
echo "</ol>";
?>
