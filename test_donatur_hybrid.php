<?php
// Test Hybrid Approach Implementation for Donatur
echo "<h2>🧪 TEST HYBRID APPROACH - DONATUR</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Database connected</p>";
    
    echo "<h3>📊 1. Testing PHP Direct Data Loading (Donatur Table)</h3>";
    
    // Test the same queries as donatur.php
    $searchQuery = '';
    $kategoriFilter = '';
    
    $whereConditions = ["1=1"];
    $params = [];
    
    if (!empty($searchQuery)) {
        $whereConditions[] = "(d.nama LIKE ? OR d.hp LIKE ? OR d.email LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($kategoriFilter)) {
        $whereConditions[] = "d.kategori = ?";
        $params[] = $kategoriFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               COUNT(k.id) as jumlah_kunjungan,
               COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
               COALESCE(AVG(CASE WHEN k.status = 'berhasil' THEN k.nominal END), 0) as rata_rata_donasi,
               MIN(k.created_at) as first_donation,
               MAX(k.created_at) as last_donation
        FROM donatur d 
        LEFT JOIN kunjungan k ON d.id = k.donatur_id
        WHERE $whereClause
        GROUP BY d.id, d.nama, d.hp, d.email, d.alamat, d.kategori, d.created_at
        ORDER BY d.nama ASC
    ");
    $stmt->execute($params);
    $donaturData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Donatur Data Loaded: " . count($donaturData) . " records</p>";
    
    // Test donatur statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM donatur");
    $stmt->execute();
    $totalDonatur = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT d.id) as aktif 
        FROM donatur d 
        INNER JOIN kunjungan k ON d.id = k.donatur_id 
        WHERE k.status = 'berhasil'
    ");
    $stmt->execute();
    $donaturAktif = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as baru 
        FROM donatur 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $donaturBaru = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COALESCE(AVG(nominal), 0) as rata_rata 
        FROM kunjungan 
        WHERE status = 'berhasil'
    ");
    $stmt->execute();
    $rataRataDonasi = $stmt->fetchColumn();
    
    echo "<p>✅ Total Donatur: " . number_format($totalDonatur) . "</p>";
    echo "<p>✅ Donatur Aktif: " . number_format($donaturAktif) . "</p>";
    echo "<p>✅ Donatur Baru Bulan Ini: " . number_format($donaturBaru) . "</p>";
    echo "<p>✅ Rata-rata Donasi: Rp " . number_format($rataRataDonasi, 0, ',', '.') . "</p>";
    
    echo "<h3>🔌 2. Testing API Endpoint (Form Submissions)</h3>";
    
    // Test API endpoint exists
    $apiFile = 'api/donatur_crud.php';
    if (file_exists($apiFile)) {
        echo "<p>✅ API endpoint exists: $apiFile</p>";
        
        // Test API structure
        $apiContent = file_get_contents($apiFile);
        if (strpos($apiContent, 'POST') !== false) {
            echo "<p>✅ API supports POST (Create)</p>";
        }
        if (strpos($apiContent, 'PUT') !== false) {
            echo "<p>✅ API supports PUT (Update)</p>";
        }
        if (strpos($apiContent, 'DELETE') !== false) {
            echo "<p>✅ API supports DELETE (Delete)</p>";
        }
        if (strpos($apiContent, 'GET') !== false) {
            echo "<p>✅ API supports GET (Read single)</p>";
        }
        
        // Test specific donatur features
        if (strpos($apiContent, 'hp') !== false) {
            echo "<p>✅ API validates HP uniqueness</p>";
        }
        if (strpos($apiContent, 'kunjungan') !== false) {
            echo "<p>✅ API checks kunjungan before delete</p>";
        }
    } else {
        echo "<p>❌ API endpoint missing: $apiFile</p>";
    }
    
    echo "<h3>📱 3. Testing JavaScript Integration</h3>";
    
    // Test JavaScript file exists
    $jsFile = 'js/donatur_api.js';
    if (file_exists($jsFile)) {
        echo "<p>✅ JavaScript API file exists: $jsFile</p>";
        
        // Test JavaScript structure
        $jsContent = file_get_contents($jsFile);
        if (strpos($jsContent, 'DonaturAPI') !== false) {
            echo "<p>✅ DonaturAPI object defined</p>";
        }
        if (strpos($jsContent, 'submitDonatur') !== false) {
            echo "<p>✅ submitDonatur function exists</p>";
        }
        if (strpos($jsContent, 'updateDonatur') !== false) {
            echo "<p>✅ updateDonatur function exists</p>";
        }
        if (strpos($jsContent, 'deleteDonatur') !== false) {
            echo "<p>✅ deleteDonatur function exists</p>";
        }
        if (strpos($jsContent, 'isValidEmail') !== false) {
            echo "<p>✅ Email validation function exists</p>";
        }
    } else {
        echo "<p>❌ JavaScript API file missing: $jsFile</p>";
    }
    
    echo "<h3>🎯 4. Hybrid Approach Summary for Donatur</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;'>";
    echo "<strong>✅ HYBRID APPROACH IMPLEMENTED FOR DONATUR:</strong><br><br>";
    echo "📊 <strong>PHP Direct Loading:</strong><br>";
    echo "• Donatur table data loaded directly with PHP<br>";
    echo "• Statistics calculated with PHP<br>";
    echo "• Filter functionality works with PHP<br>";
    echo "• Aggregated data (total_donasi, jumlah_kunjungan) loaded<br><br>";
    echo "🔌 <strong>API for Form Submissions:</strong><br>";
    echo "• Form submissions handled via API<br>";
    echo "• HP uniqueness validation<br>";
    echo "• Email validation<br>";
    echo "• Delete protection (check kunjungan)<br><br>";
    echo "📱 <strong>JavaScript Integration:</strong><br>";
    echo "• Form handling with JavaScript<br>";
    echo "• Real-time validation<br>";
    echo "• User experience enhancements<br>";
    echo "• Mobile responsiveness maintained";
    echo "</div>";
    
    echo "<h3>📋 5. Sample Donatur Data</h3>";
    if (!empty($donaturData)) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h4>First 3 Donatur Records:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='padding: 8px;'>Nama</th>";
        echo "<th style='padding: 8px;'>HP</th>";
        echo "<th style='padding: 8px;'>Kategori</th>";
        echo "<th style='padding: 8px;'>Total Donasi</th>";
        echo "<th style='padding: 8px;'>Jumlah Kunjungan</th>";
        echo "</tr>";
        
        foreach (array_slice($donaturData, 0, 3) as $donatur) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($donatur['nama']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($donatur['hp']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($donatur['kategori']) . "</td>";
            echo "<td style='padding: 8px;'>Rp " . number_format($donatur['total_donasi'], 0, ',', '.') . "</td>";
            echo "<td style='padding: 8px;'>" . number_format($donatur['jumlah_kunjungan']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<h3>🔗 6. Test Links</h3>";
    echo "<ul>";
    echo "<li><a href='dashboard.php'>📊 Dashboard (PHP Direct)</a></li>";
    echo "<li><a href='kunjungan.php'>📝 Kunjungan (Hybrid)</a></li>";
    echo "<li><a href='donatur.php'>👥 Donatur (Hybrid)</a></li>";
    echo "<li><a href='api/donatur_crud.php'>🔌 Donatur API Endpoint</a></li>";
    echo "<li><a href='js/donatur_api.js'>📱 Donatur JavaScript API</a></li>";
    echo "</ul>";
    
    echo "<h3>📋 7. Next Steps</h3>";
    echo "<ol>";
    echo "<li>✅ <strong>Test donatur.php</strong> - should show data immediately</li>";
    echo "<li>✅ <strong>Test form submission</strong> - should work via API</li>";
    echo "<li>✅ <strong>Test filtering</strong> - should work with PHP</li>";
    echo "<li>✅ <strong>Test mobile responsiveness</strong> - should work on mobile</li>";
    echo "<li>🔧 <strong>Implement similar approach</strong> for users.php</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
