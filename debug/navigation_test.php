<?php
/**
 * Navigation Test Script
 * Test all sidebar navigation links
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>❌ Not logged in</h1>";
    echo "<p>Please login first to test navigation.</p>";
    echo "<a href='../login.php'>Login</a>";
    exit;
}

require_once '../config.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h1>❌ User not found</h1>";
    exit;
}

echo "<h1>🔍 Navigation Test - {$user['name']} ({$user['role']})</h1>";

// Define navigation links based on role
$navigation_links = [
    'admin' => [
        'Dashboard' => 'dashboard.php',
        'Kunjungan' => 'kunjungan.php',
        'Donatur' => 'donatur.php',
        'Fundraiser' => 'users.php',
        'Target Individual' => 'fundraiser-target.php',
        'Target Global' => 'target-fixed.php',
        'Analytics' => 'analytics-fixed.php',
        'Settings' => 'settings.php'
    ],
    'monitor' => [
        'Dashboard' => 'dashboard.php',
        'Kunjungan' => 'kunjungan.php',
        'Donatur' => 'donatur.php',
        'Fundraiser' => 'users.php',
        'Target Individual' => 'fundraiser-target.php',
        'Target Global' => 'target-fixed.php',
        'Analytics' => 'analytics-fixed.php',
        'Settings' => 'settings.php'
    ],
    'user' => [
        'Dashboard' => 'dashboard.php',
        'Kunjungan' => 'kunjungan.php',
        'Donatur' => 'donatur.php'
    ]
];

$user_links = $navigation_links[$user['role']] ?? [];

echo "<h2>Testing Navigation Links for {$user['role']} role</h2>";

$base_url = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = str_replace('/debug', '', $base_url);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f3f4f6;'>";
echo "<th style='padding: 10px; text-align: left;'>Page</th>";
echo "<th style='padding: 10px; text-align: left;'>File</th>";
echo "<th style='padding: 10px; text-align: left;'>Status</th>";
echo "<th style='padding: 10px; text-align: left;'>Action</th>";
echo "</tr>";

foreach ($user_links as $page_name => $file_path) {
    $full_path = "../$file_path";
    $file_exists = file_exists($full_path);
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>$page_name</td>";
    echo "<td style='padding: 10px;'>$file_path</td>";
    
    if ($file_exists) {
        echo "<td style='padding: 10px; color: green;'>✅ File exists</td>";
        echo "<td style='padding: 10px;'><a href='../$file_path' target='_blank'>Test Link</a></td>";
    } else {
        echo "<td style='padding: 10px; color: red;'>❌ File missing</td>";
        echo "<td style='padding: 10px;'>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test sidebar files
echo "<h2>Testing Sidebar Files</h2>";

$sidebar_files = [
    'Admin Sidebar' => 'sidebar-admin.php',
    'User Sidebar' => 'sidebar-user.php'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f3f4f6;'>";
echo "<th style='padding: 10px; text-align: left;'>Sidebar</th>";
echo "<th style='padding: 10px; text-align: left;'>File</th>";
echo "<th style='padding: 10px; text-align: left;'>Status</th>";
echo "</tr>";

foreach ($sidebar_files as $sidebar_name => $file_path) {
    $full_path = "../$file_path";
    $file_exists = file_exists($full_path);
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>$sidebar_name</td>";
    echo "<td style='padding: 10px;'>$file_path</td>";
    
    if ($file_exists) {
        echo "<td style='padding: 10px; color: green;'>✅ File exists</td>";
    } else {
        echo "<td style='padding: 10px; color: red;'>❌ File missing</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test critical files
echo "<h2>Testing Critical Files</h2>";

$critical_files = [
    'Config' => 'config.php',
    'Login' => 'login.php',
    'Logout' => 'logout.php',
    'Main CSS' => 'styles/main.css',
    'Icon Fixes CSS' => 'styles/icon-fixes.css'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f3f4f6;'>";
echo "<th style='padding: 10px; text-align: left;'>File</th>";
echo "<th style='padding: 10px; text-align: left;'>Path</th>";
echo "<th style='padding: 10px; text-align: left;'>Status</th>";
echo "</tr>";

foreach ($critical_files as $file_name => $file_path) {
    $full_path = "../$file_path";
    $file_exists = file_exists($full_path);
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>$file_name</td>";
    echo "<td style='padding: 10px;'>$file_path</td>";
    
    if ($file_exists) {
        echo "<td style='padding: 10px; color: green;'>✅ File exists</td>";
    } else {
        echo "<td style='padding: 10px; color: red;'>❌ File missing</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test JavaScript files
echo "<h2>Testing JavaScript Files</h2>";

$js_files = [
    'App JS' => 'js/app.js',
    'Utils JS' => 'js/utils.js',
    'Config JS' => 'js/config.js',
    'Kunjungan API' => 'js/kunjungan_api.js',
    'Donatur API' => 'js/donatur_api.js',
    'Users API' => 'js/users_api.js',
    'Mobile Menu' => 'js/mobile-menu.js',
    'Icon Fixes' => 'js/icon-fixes.js'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f3f4f6;'>";
echo "<th style='padding: 10px; text-align: left;'>File</th>";
echo "<th style='padding: 10px; text-align: left;'>Path</th>";
echo "<th style='padding: 10px; text-align: left;'>Status</th>";
echo "</tr>";

foreach ($js_files as $file_name => $file_path) {
    $full_path = "../$file_path";
    $file_exists = file_exists($full_path);
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>$file_name</td>";
    echo "<td style='padding: 10px;'>$file_path</td>";
    
    if ($file_exists) {
        echo "<td style='padding: 10px; color: green;'>✅ File exists</td>";
    } else {
        echo "<td style='padding: 10px; color: red;'>❌ File missing</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<h2>🎉 Navigation Test Summary</h2>";
echo "<p>All navigation links have been tested. Check the results above for any missing files.</p>";
echo "<a href='../dashboard.php'>← Back to Dashboard</a>";
?>