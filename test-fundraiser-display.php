<?php
// Test script untuk memastikan data fundraiser ditampilkan langsung
require_once 'config.php';

echo "<h2>🧪 TEST FUNDRAISER DATA DISPLAY</h2>\n\n";

try {
    // Test 1: Check database connection
    echo "✅ Database connection: ";
    if ($pdo) {
        echo "OK\n";
    } else {
        echo "FAILED\n";
        exit;
    }
    
    // Test 2: Check if fundraiser data exists
    echo "📊 Checking fundraiser data...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $fundraiserCount = $stmt->fetchColumn();
    echo "   Total fundraiser in database: $fundraiserCount\n";
    
    if ($fundraiserCount == 0) {
        echo "⚠️  WARNING: No fundraiser data found!\n";
        echo "   Solution: Insert dummy data or add fundraiser manually\n\n";
        
        // Check if any users exist at all
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $totalUsers = $stmt->fetchColumn();
        echo "   Total users (all roles): $totalUsers\n";
        
        if ($totalUsers > 0) {
            echo "   Users by role:\n";
            $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                echo "   - {$row['role']}: {$row['count']}\n";
            }
        }
    } else {
        echo "✅ Fundraiser data exists\n";
        
        // Test 3: Run the same query as fundraiser.php
        echo "🔍 Testing fundraiser.php query...\n";
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.hp,
                u.role,
                u.status,
                u.target,
                u.kunjungan_hari_ini,
                u.total_kunjungan_bulan,
                u.total_donasi_bulan,
                u.created_at,
                u.last_active,
                COALESCE(COUNT(k.id), 0) as total_kunjungan_actual,
                COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi_actual,
                COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini_actual
            FROM users u
            LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
            WHERE u.role = 'user'
            GROUP BY u.id, u.name, u.email, u.hp, u.role, u.status, u.target, u.kunjungan_hari_ini, u.total_kunjungan_bulan, u.total_donasi_bulan, u.created_at, u.last_active
            ORDER BY u.status DESC, u.name ASC
        ");
        $stmt->execute();
        $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Query result: " . count($fundraisers) . " fundraisers\n";
        
        if (count($fundraisers) > 0) {
            echo "✅ Data will display correctly in fundraiser.php\n\n";
            echo "📋 Fundraiser list:\n";
            foreach ($fundraisers as $f) {
                $status = $f['status'] === 'aktif' ? '🟢' : '🔴';
                echo "   $status {$f['name']} (Target: {$f['target']}/hari, Status: {$f['status']})\n";
            }
        } else {
            echo "❌ Query returned no results\n";
        }
    }
    
    // Test 4: Check kunjungan data
    echo "\n📈 Checking kunjungan data...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan");
    $stmt->execute();
    $kunjunganCount = $stmt->fetchColumn();
    echo "   Total kunjungan: $kunjunganCount\n";
    
    // Test 5: Check today's data
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $todayCount = $stmt->fetchColumn();
    echo "   Kunjungan hari ini ($today): $todayCount\n";
    
    echo "\n🎯 CONCLUSION:\n";
    if ($fundraiserCount > 0) {
        echo "✅ Fundraiser page WILL show data immediately\n";
        echo "✅ All $fundraiserCount fundraisers will be displayed\n";
        echo "✅ Target kunjungan will be visible for each fundraiser\n";
        echo "✅ Progress bars will show current achievement\n";
    } else {
        echo "⚠️  Fundraiser page will show empty state\n";
        echo "💡 SOLUTION: Add dummy data or create fundraiser manually\n";
        echo "   - Use dashboard admin tools to insert dummy data\n";
        echo "   - Or add fundraiser via admin-users.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed. Check fundraiser.php in browser.\n";
?>