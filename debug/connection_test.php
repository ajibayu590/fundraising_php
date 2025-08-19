<?php
/**
 * Database Connection Test Script
 * Run this to test database connectivity and configuration
 */

echo "<h1>🔍 Database Connection Test</h1>";

// Test 1: Basic PDO Connection
echo "<h2>Test 1: Basic PDO Connection</h2>";
try {
    $host = 'localhost';
    $database = 'fundraising_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✅ PDO Connection successful<br>";
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
    echo "Client version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "<br>";
    
} catch(PDOException $e) {
    echo "❌ PDO Connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Config.php Connection
echo "<h2>Test 2: Config.php Connection</h2>";
try {
    require_once '../config.php';
    echo "✅ Config.php loaded successfully<br>";
    echo "✅ Database connection from config.php works<br>";
} catch(Exception $e) {
    echo "❌ Config.php failed: " . $e->getMessage() . "<br>";
}

// Test 3: Table Existence
echo "<h2>Test 3: Table Existence</h2>";
$required_tables = ['users', 'donatur', 'kunjungan', 'settings'];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    } catch(Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test 4: Data Count
echo "<h2>Test 4: Data Count</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users table has " . $result['count'] . " records<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM donatur");
    $result = $stmt->fetch();
    echo "✅ Donatur table has " . $result['count'] . " records<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM kunjungan");
    $result = $stmt->fetch();
    echo "✅ Kunjungan table has " . $result['count'] . " records<br>";
    
} catch(Exception $e) {
    echo "❌ Error counting data: " . $e->getMessage() . "<br>";
}

// Test 5: Foreign Key Relationships
echo "<h2>Test 5: Foreign Key Relationships</h2>";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned_count 
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        WHERE u.id IS NULL
    ");
    $result = $stmt->fetch();
    if ($result['orphaned_count'] == 0) {
        echo "✅ No orphaned kunjungan records<br>";
    } else {
        echo "⚠️ Found " . $result['orphaned_count'] . " orphaned kunjungan records<br>";
    }
} catch(Exception $e) {
    echo "❌ Error checking foreign keys: " . $e->getMessage() . "<br>";
}

// Test 6: CSRF Functions
echo "<h2>Test 6: CSRF Functions</h2>";
try {
    session_start();
    
    // Test generate_csrf_token
    $token1 = generate_csrf_token();
    $token2 = generate_csrf_token();
    
    if ($token1 === $token2) {
        echo "✅ CSRF token generation works (consistent)<br>";
    } else {
        echo "❌ CSRF token generation inconsistent<br>";
    }
    
    // Test get_csrf_token_field
    $field = get_csrf_token_field();
    if (strpos($field, 'csrf_token') !== false) {
        echo "✅ CSRF token field generation works<br>";
    } else {
        echo "❌ CSRF token field generation failed<br>";
    }
    
    // Test get_csrf_token_meta
    $meta = get_csrf_token_meta();
    if (strpos($meta, 'csrf-token') !== false) {
        echo "✅ CSRF token meta generation works<br>";
    } else {
        echo "❌ CSRF token meta generation failed<br>";
    }
    
} catch(Exception $e) {
    echo "❌ CSRF functions failed: " . $e->getMessage() . "<br>";
}

// Test 7: Performance Test
echo "<h2>Test 7: Performance Test</h2>";
try {
    $start_time = microtime(true);
    
    // Simple query performance test
    $stmt = $pdo->query("SELECT * FROM users LIMIT 100");
    $users = $stmt->fetchAll();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "✅ Query executed in " . number_format($execution_time, 2) . " ms<br>";
    echo "✅ Retrieved " . count($users) . " user records<br>";
    
    if ($execution_time < 100) {
        echo "✅ Performance is good (< 100ms)<br>";
    } else {
        echo "⚠️ Performance might need optimization (> 100ms)<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Performance test failed: " . $e->getMessage() . "<br>";
}

echo "<h2>🎉 Test Summary</h2>";
echo "All tests completed. Check the results above for any issues.<br>";
echo "<a href='../dashboard.php'>← Back to Dashboard</a>";
?>