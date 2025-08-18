<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Check admin access
if (!in_array($user_role, ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fundraiser Debug</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo get_csrf_token_meta();
echo "</head><body class='bg-gray-100 p-8'>";

echo "<h1 class='text-2xl font-bold mb-4'>🧪 FUNDRAISER DEBUG PAGE</h1>";

// Test database connection
echo "<div class='bg-white p-4 rounded mb-4'>";
echo "<h2 class='font-bold'>1. Database Connection:</h2>";
try {
    if ($pdo) {
        echo "<p class='text-green-600'>✅ Database connected successfully</p>";
    } else {
        echo "<p class='text-red-600'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test fundraiser data
echo "<div class='bg-white p-4 rounded mb-4'>";
echo "<h2 class='font-bold'>2. Fundraiser Data Test:</h2>";
try {
    // Simple query first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'user' ORDER BY name");
    $stmt->execute();
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='text-blue-600'>📊 Found " . count($fundraisers) . " fundraisers</p>";
    
    if (count($fundraisers) > 0) {
        echo "<h3 class='font-semibold mt-2'>Fundraiser List:</h3>";
        echo "<ul class='list-disc list-inside'>";
        foreach ($fundraisers as $f) {
            echo "<li>{$f['name']} (Target: {$f['target']}/hari, Status: {$f['status']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='text-yellow-600'>⚠️ No fundraiser data found</p>";
        
        // Check all users
        $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='mt-2'>All users by role:</p>";
        echo "<ul class='list-disc list-inside'>";
        foreach ($roles as $role) {
            echo "<li>{$role['role']}: {$role['count']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Query Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test simple table display
if (isset($fundraisers) && count($fundraisers) > 0) {
    echo "<div class='bg-white p-4 rounded mb-4'>";
    echo "<h2 class='font-bold'>3. Simple Table Test:</h2>";
    echo "<table class='w-full border border-gray-300'>";
    echo "<thead class='bg-gray-50'>";
    echo "<tr>";
    echo "<th class='border p-2'>ID</th>";
    echo "<th class='border p-2'>Nama</th>";
    echo "<th class='border p-2'>Email</th>";
    echo "<th class='border p-2'>Target</th>";
    echo "<th class='border p-2'>Status</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($fundraisers as $f) {
        echo "<tr>";
        echo "<td class='border p-2'>{$f['id']}</td>";
        echo "<td class='border p-2'>{$f['name']}</td>";
        echo "<td class='border p-2'>{$f['email']}</td>";
        echo "<td class='border p-2'>{$f['target']}</td>";
        echo "<td class='border p-2'>{$f['status']}</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='bg-green-50 p-4 rounded'>";
    echo "<h2 class='font-bold text-green-800'>✅ SUCCESS!</h2>";
    echo "<p>Table data is working. The issue might be in the complex HTML structure.</p>";
    echo "<p><a href='fundraiser.php' class='text-blue-600 underline'>Go back to fundraiser.php</a></p>";
    echo "</div>";
} else {
    echo "<div class='bg-yellow-50 p-4 rounded'>";
    echo "<h2 class='font-bold text-yellow-800'>⚠️ NO DATA</h2>";
    echo "<p>No fundraiser data to display. Need to add data first.</p>";
    echo "<p><a href='dashboard.php' class='text-blue-600 underline'>Go to Dashboard</a> and insert dummy data.</p>";
    echo "</div>";
}

echo "</body></html>";
?>