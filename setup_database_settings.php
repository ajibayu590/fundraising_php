<?php
// Script untuk setup database settings
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "Akses ditolak. Hanya admin yang dapat menjalankan script ini.";
    exit;
}

$success_messages = [];
$error_messages = [];

try {
    // Insert default settings
    $defaultSettings = [
        'target_global' => '8',
        'target_donasi' => '1000000',
        'target_donatur_baru' => '50'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        // Check if setting already exists
        $stmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        
        if (!$stmt->fetch()) {
            // Insert new setting
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$key, $value]);
            $success_messages[] = "Berhasil menambahkan setting: $key = $value";
        } else {
            $success_messages[] = "Setting sudah ada: $key";
        }
    }
    
    // Verify settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('target_global', 'target_donasi', 'target_donatur_baru')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_messages[] = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Setup Database Settings</h1>
        
        <?php if (!empty($success_messages)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <h3 class="font-bold">Berhasil:</h3>
                <ul class="list-disc list-inside">
                    <?php foreach ($success_messages as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_messages)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <h3 class="font-bold">Error:</h3>
                <ul class="list-disc list-inside">
                    <?php foreach ($error_messages as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($settings)): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Settings yang Tersedia:</h3>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Key</th>
                            <th class="text-left py-2">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $setting): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($setting['setting_key']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($setting['setting_value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="target.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Kembali ke Target & Laporan</a>
            <a href="users.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">Lihat Users</a>
        </div>
    </div>
</body>
</html>