<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Silakan login terlebih dahulu";
    exit;
}

$tests = [];
$errors = [];

// Test 1: Database Connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    $tests[] = "✅ Koneksi database: OK ($userCount users)";
} catch (Exception $e) {
    $errors[] = "❌ Koneksi database: " . $e->getMessage();
}

// Test 2: Settings Table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM settings");
    $settingsCount = $stmt->fetchColumn();
    $tests[] = "✅ Tabel settings: OK ($settingsCount settings)";
} catch (Exception $e) {
    $errors[] = "❌ Tabel settings: " . $e->getMessage();
}

// Test 3: Required Settings
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('target_global', 'target_donasi', 'target_donatur_baru')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($settings) >= 3) {
        $tests[] = "✅ Settings target: OK (" . count($settings) . " settings)";
    } else {
        $errors[] = "❌ Settings target: Kurang dari 3 settings (" . count($settings) . ")";
    }
} catch (Exception $e) {
    $errors[] = "❌ Settings target: " . $e->getMessage();
}

// Test 4: getSettingValue Function
try {
    $targetGlobal = getSettingValue('target_global', 'NOT_FOUND');
    if ($targetGlobal !== 'NOT_FOUND') {
        $tests[] = "✅ getSettingValue function: OK (target_global = $targetGlobal)";
    } else {
        $errors[] = "❌ getSettingValue function: Setting tidak ditemukan";
    }
} catch (Exception $e) {
    $errors[] = "❌ getSettingValue function: " . $e->getMessage();
}

// Test 5: CSRF Token
try {
    $csrfToken = generate_csrf_token();
    if (!empty($csrfToken)) {
        $tests[] = "✅ CSRF Token: OK";
    } else {
        $errors[] = "❌ CSRF Token: Token kosong";
    }
} catch (Exception $e) {
    $errors[] = "❌ CSRF Token: " . $e->getMessage();
}

// Test 6: Users Data
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            u.status,
            COALESCE(COUNT(k.id), 0) as total_kunjungan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        GROUP BY u.id, u.name, u.email, u.role, u.status
        LIMIT 5
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        $tests[] = "✅ Data users: OK (" . count($users) . " users loaded)";
    } else {
        $errors[] = "❌ Data users: Tidak ada data user";
    }
} catch (Exception $e) {
    $errors[] = "❌ Data users: " . $e->getMessage();
}

// Test 7: Kunjungan Data
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM kunjungan");
    $kunjunganCount = $stmt->fetchColumn();
    $tests[] = "✅ Data kunjungan: OK ($kunjunganCount records)";
} catch (Exception $e) {
    $errors[] = "❌ Data kunjungan: " . $e->getMessage();
}

// Test 8: Donatur Data
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM donatur");
    $donaturCount = $stmt->fetchColumn();
    $tests[] = "✅ Data donatur: OK ($donaturCount records)";
} catch (Exception $e) {
    $errors[] = "❌ Data donatur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Semua Perbaikan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php echo get_csrf_token_meta(); ?>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Test Semua Perbaikan Sistem</h1>
        
        <!-- Test Results -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Success Tests -->
            <?php if (!empty($tests)): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-green-600 mb-4">✅ Tests Berhasil</h3>
                <ul class="space-y-2">
                    <?php foreach ($tests as $test): ?>
                        <li class="text-sm text-gray-700"><?php echo htmlspecialchars($test); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Error Tests -->
            <?php if (!empty($errors)): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-red-600 mb-4">❌ Tests Gagal</h3>
                <ul class="space-y-2">
                    <?php foreach ($errors as $error): ?>
                        <li class="text-sm text-gray-700"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="setup_database_settings.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-center">
                    Setup Database Settings
                </a>
                <a href="target.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 text-center">
                    Test Target Global
                </a>
                <a href="users.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 text-center">
                    Test Users Page
                </a>
            </div>
        </div>
        
        <!-- JavaScript Test -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Test JavaScript</h3>
            <div class="space-y-4">
                <button onclick="testNotification()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Test Notification
                </button>
                <button onclick="testCSRFToken()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Test CSRF Token
                </button>
                <button onclick="testSettingsAPI()" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Test Settings API
                </button>
            </div>
            <div id="js-test-results" class="mt-4 p-4 bg-gray-100 rounded hidden"></div>
        </div>
        
        <!-- Navigation -->
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Dashboard
            </a>
            <a href="kunjungan.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Kunjungan
            </a>
            <a href="donatur.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Donatur
            </a>
            <a href="settings.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                Settings
            </a>
        </div>
    </div>

    <script>
    function testNotification() {
        if (typeof Utils !== 'undefined' && Utils.showNotification) {
            Utils.showNotification('Test notification berhasil!', 'success');
            document.getElementById('js-test-results').innerHTML = '<p class="text-green-600">✅ Notification system berfungsi</p>';
            document.getElementById('js-test-results').classList.remove('hidden');
        } else {
            document.getElementById('js-test-results').innerHTML = '<p class="text-red-600">❌ Utils.showNotification tidak tersedia</p>';
            document.getElementById('js-test-results').classList.remove('hidden');
        }
    }
    
    function testCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token && token.getAttribute('content')) {
            document.getElementById('js-test-results').innerHTML = '<p class="text-green-600">✅ CSRF Token tersedia</p>';
            document.getElementById('js-test-results').classList.remove('hidden');
        } else {
            document.getElementById('js-test-results').innerHTML = '<p class="text-red-600">❌ CSRF Token tidak tersedia</p>';
            document.getElementById('js-test-results').classList.remove('hidden');
        }
    }
    
    async function testSettingsAPI() {
        try {
            const response = await fetch('api/settings.php', {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            if (result.success) {
                document.getElementById('js-test-results').innerHTML = '<p class="text-green-600">✅ Settings API berfungsi</p>';
            } else {
                document.getElementById('js-test-results').innerHTML = '<p class="text-red-600">❌ Settings API error: ' + result.message + '</p>';
            }
            document.getElementById('js-test-results').classList.remove('hidden');
        } catch (error) {
            document.getElementById('js-test-results').innerHTML = '<p class="text-red-600">❌ Settings API error: ' + error.message + '</p>';
            document.getElementById('js-test-results').classList.remove('hidden');
        }
    }
    </script>
</body>
</html>