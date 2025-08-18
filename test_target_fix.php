<?php
session_start();
require_once 'config.php';

// Test if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login first";
    exit;
}

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "Database connection: OK<br>";
    echo "Total users: $userCount<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test settings
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('target_global', 'target_donasi', 'target_donatur_baru')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Settings found: " . count($settings) . "<br>";
    foreach ($settings as $setting) {
        echo "- {$setting['setting_key']}: {$setting['setting_value']}<br>";
    }
} catch (Exception $e) {
    echo "Settings error: " . $e->getMessage() . "<br>";
}

// Test getSettingValue function
echo "Target Global: " . getSettingValue('target_global', 'NOT_FOUND') . "<br>";
echo "Target Donasi: " . getSettingValue('target_donasi', 'NOT_FOUND') . "<br>";
echo "Target Donatur Baru: " . getSettingValue('target_donatur_baru', 'NOT_FOUND') . "<br>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Target Fix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php echo get_csrf_token_meta(); ?>
</head>
<body class="p-8">
    <h1 class="text-2xl font-bold mb-4">Test Target Global Functionality</h1>
    
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Target Global:</label>
        <input type="number" id="test-target-global" value="<?php echo getSettingValue('target_global', 8); ?>" class="border p-2 rounded">
    </div>
    
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Target Donasi:</label>
        <input type="number" id="test-target-donasi" value="<?php echo getSettingValue('target_donasi', 1000000); ?>" class="border p-2 rounded">
    </div>
    
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Target Donatur Baru:</label>
        <input type="number" id="test-target-donatur-baru" value="<?php echo getSettingValue('target_donatur_baru', 50); ?>" class="border p-2 rounded">
    </div>
    
    <button onclick="testUpdateSettings()" class="bg-blue-500 text-white px-4 py-2 rounded">Test Update Settings</button>
    
    <div id="result" class="mt-4 p-4 bg-gray-100 rounded"></div>

    <script>
    async function testUpdateSettings() {
        const targetGlobal = parseInt(document.getElementById('test-target-global').value);
        const targetDonasi = parseInt(document.getElementById('test-target-donasi').value);
        const targetDonaturBaru = parseInt(document.getElementById('test-target-donatur-baru').value);
        
        const settings = {
            targetGlobal: targetGlobal,
            targetDonasi: targetDonasi,
            targetDonaturBaru: targetDonaturBaru
        };
        
        try {
            // Test target global update
            const response1 = await fetch('api/settings.php?key=target_global', {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ value: settings.targetGlobal })
            });
            
            const result1 = await response1.json();
            document.getElementById('result').innerHTML = `
                <h3 class="font-bold">Test Results:</h3>
                <p>Target Global Update: ${result1.success ? 'SUCCESS' : 'FAILED'}</p>
                <p>Response: ${JSON.stringify(result1)}</p>
            `;
            
        } catch (error) {
            document.getElementById('result').innerHTML = `
                <h3 class="font-bold">Test Results:</h3>
                <p>Error: ${error.message}</p>
            `;
        }
    }
    </script>
</body>
</html>