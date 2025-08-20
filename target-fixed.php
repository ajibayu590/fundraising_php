<?php
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database
require_once 'config.php';

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika user tidak ditemukan, redirect ke login
if (!$user) {
    header("Location: login.php");
    exit;
}

// Check access
if (!in_array($user['role'], ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle global target updates (admin only) - BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'admin') {
    try {
        check_csrf();
        
        if (isset($_POST['update_global_target'])) {
            $newGlobalTarget = (int)$_POST['global_target'];
            if ($newGlobalTarget > 0) {
                // Update all users with the new global target
                $stmt = $pdo->prepare("UPDATE users SET target = ? WHERE role = 'user'");
                $stmt->execute([$newGlobalTarget]);
                $affected = $stmt->rowCount();
                
                $success_message = "Target global berhasil diupdate untuk $affected fundraiser";
                header("Location: target-fixed.php?success=" . urlencode($success_message));
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Tentukan sidebar berdasarkan role user
$sidebarFile = ($user['role'] == 'admin') ? 'sidebar-admin.php' : 'sidebar-user.php';

// Load performance data
try {
    $today = date('Y-m-d');
    
    // Get all fundraisers with their performance
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.target,
            u.status,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
            COALESCE(COUNT(CASE WHEN k.status = 'berhasil' THEN 1 END), 0) as sukses_kunjungan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.email, u.target, u.status
        ORDER BY total_donasi DESC
    ");
    $stmt->execute();
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate performance summary
    $totalFundraisers = count($fundraisers);
    $targetTercapai = 0;
    $dalamProgress = 0;
    $perluPerhatian = 0;
    $totalKunjunganHariIni = 0;
    $totalTargetHarian = 0;
    
    foreach ($fundraisers as $f) {
        $totalTargetHarian += $f['target'];
        $totalKunjunganHariIni += $f['kunjungan_hari_ini'];
        
        $percent = $f['target'] > 0 ? ($f['kunjungan_hari_ini'] / $f['target']) * 100 : 0;
        if ($percent >= 100) {
            $targetTercapai++;
        } elseif ($percent >= 50) {
            $dalamProgress++;
        } else {
            $perluPerhatian++;
        }
    }
    
    $rataPencapaian = $totalTargetHarian > 0 ? round(($totalKunjunganHariIni / $totalTargetHarian) * 100, 1) : 0;
    
    // Get current global target (most common target)
    $stmt = $pdo->prepare("SELECT target, COUNT(*) as count FROM users WHERE role = 'user' GROUP BY target ORDER BY count DESC LIMIT 1");
    $stmt->execute();
    $globalTargetData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentGlobalTarget = $globalTargetData ? $globalTargetData['target'] : 8;
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $fundraisers = [];
    $targetTercapai = 0;
    $dalamProgress = 0;
    $perluPerhatian = 0;
    $rataPencapaian = 0;
    $currentGlobalTarget = 8;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Target & Goals - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/icon-fixes.css">
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 50 !important;
            background: white !important;
        }
        
        .sidebar {
            position: fixed !important;
            top: 64px !important;
            left: 0 !important;
            height: calc(100vh - 64px) !important;
            width: 16rem !important;
            z-index: 40 !important;
            background: white !important;
            transform: translateX(0) !important;
        }
        
        .main-content {
            margin-left: 16rem !important;
            margin-top: 64px !important;
            min-height: calc(100vh - 64px) !important;
            width: calc(100% - 16rem) !important;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%) !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="mobile-menu-btn">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center" style="height: 64px !important;">
                <div class="flex items-center">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Target & Goals</h1>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?php echo ucfirst($user['role']); ?></span>
                    <a href="logout.php" class="text-xs md:text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <div class="flex">
        <!-- Sidebar -->
        <?php include $sidebarFile; ?>
        
        <div class="main-content flex-1 p-4 md:p-8">
            <!-- Quick Actions - Moved to top -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="fundraiser-target.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Kelola Target Individual
                    </a>
                    
                    <a href="analytics-fixed.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Lihat Analytics Detail
                    </a>
                    
                    <button onclick="window.print()" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Performance
                    </button>
                </div>
            </div>

            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Target & Goals Management</h2>
                <p class="text-gray-600 mt-2">Kelola target dan goals untuk sistem fundraising</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <strong>Success:</strong> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Performance Summary -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Performa Hari Ini</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-green-700">Target Tercapai</span>
                            <span class="text-2xl font-bold text-green-600"><?php echo $targetTercapai; ?></span>
                        </div>
                        <div class="text-xs text-green-600 mt-1">≥100% target harian</div>
                    </div>
                    
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-yellow-700">Dalam Progress</span>
                            <span class="text-2xl font-bold text-yellow-600"><?php echo $dalamProgress; ?></span>
                        </div>
                        <div class="text-xs text-yellow-600 mt-1">50-99% target</div>
                    </div>
                    
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-red-700">Perlu Perhatian</span>
                            <span class="text-2xl font-bold text-red-600"><?php echo $perluPerhatian; ?></span>
                        </div>
                        <div class="text-xs text-red-600 mt-1"><50% target</div>
                    </div>
                    
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $rataPencapaian; ?>%</div>
                            <div class="text-xs text-blue-600 mt-1">Rata-rata Pencapaian</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Target Update (Admin Only) -->
            <?php if ($user['role'] === 'admin'): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Target Global</h3>
                <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                    <?php echo get_csrf_token_field(); ?>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Baru untuk Semua Fundraiser</label>
                        <input type="number" name="global_target" 
                               min="1" max="50" value="<?php echo $currentGlobalTarget; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Target ini akan diterapkan ke semua <?php echo $totalFundraisers; ?> fundraiser
                        </p>
                    </div>
                    
                    <button type="submit" name="update_global_target"
                            onclick="return confirm('Update target global untuk semua fundraiser menjadi ' + document.querySelector('input[name=global_target]').value + ' kunjungan per hari?')" 
                            class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Update Target Global Semua Fundraiser
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Performance Leaderboard -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">🏆 Leaderboard Performance</h2>
                    <p class="text-sm text-gray-600">Ranking fundraiser berdasarkan total donasi</p>
                </div>
                
                <?php if (!empty($fundraisers)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Harian</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress Hari Ini</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Kunjungan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Donasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($fundraisers as $index => $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php 
                                        $rank = $index + 1;
                                        $rankIcon = $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : ''));
                                        $rankColor = $rank <= 3 ? 'text-yellow-600' : 'text-gray-600';
                                    ?>
                                    <div class="text-lg font-bold <?php echo $rankColor; ?>">
                                        <?php echo $rankIcon; ?> #<?php echo $rank; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <span class="text-sm font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($f['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-blue-600"><?php echo $f['target']; ?></div>
                                    <div class="text-xs text-gray-500">per hari</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="text-sm font-medium"><?php echo $f['kunjungan_hari_ini']; ?> / <?php echo $f['target']; ?></div>
                                        <?php 
                                            $percent = $f['target'] > 0 ? min(100, round(($f['kunjungan_hari_ini'] / $f['target']) * 100)) : 0;
                                            $color = $percent >= 100 ? 'bg-green-500' : ($percent >= 75 ? 'bg-yellow-500' : ($percent >= 50 ? 'bg-blue-500' : 'bg-red-500'));
                                        ?>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div class="h-2 rounded-full <?php echo $color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $percent; ?>%</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-900"><?php echo number_format($f['total_kunjungan']); ?></div>
                                    <div class="text-xs text-gray-500">kunjungan</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0; ?>
                                    <div class="text-sm font-medium <?php echo $successRate >= 70 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo $successRate; ?>%
                                    </div>
                                    <div class="text-xs text-gray-500"><?php echo $f['sukses_kunjungan']; ?> / <?php echo $f['total_kunjungan']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-green-600">Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                                    ?>">
                                        <?php echo ucfirst($f['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-lg font-medium mb-2">Tidak ada data performance</h3>
                    <p class="text-sm">Tambahkan data dummy atau fundraiser baru</p>
                    <a href="dashboard.php" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Kembali ke Dashboard
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- System Goals & Targets -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Daily Goals -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Goals Harian</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                            <span class="text-sm font-medium">Target Kunjungan Sistem</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo $totalTargetHarian; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm font-medium">Pencapaian Hari Ini</span>
                            <span class="text-lg font-bold text-green-600"><?php echo $totalKunjunganHariIni; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm font-medium">Progress Sistem</span>
                            <span class="text-lg font-bold text-purple-600"><?php echo $rataPencapaian; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Goals -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Goals Bulanan</h3>
                    <?php
                    // Calculate monthly data
                    $currentMonth = date('n');
                    $currentYear = date('Y');
                    $daysInMonth = date('t');
                    $daysPassed = date('j');
                    $targetBulanan = $totalTargetHarian * $daysInMonth;
                    $expectedToday = $totalTargetHarian * $daysPassed;
                    $monthlyProgress = $expectedToday > 0 ? round(($totalKunjunganHariIni / $expectedToday) * 100, 1) : 0;
                    ?>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-indigo-50 rounded-lg">
                            <span class="text-sm font-medium">Target Bulan Ini</span>
                            <span class="text-lg font-bold text-indigo-600"><?php echo number_format($targetBulanan); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-teal-50 rounded-lg">
                            <span class="text-sm font-medium">Expected Today</span>
                            <span class="text-lg font-bold text-teal-600"><?php echo number_format($expectedToday); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-pink-50 rounded-lg">
                            <span class="text-sm font-medium">Monthly Progress</span>
                            <span class="text-lg font-bold text-pink-600"><?php echo $monthlyProgress; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('hidden');
                    }
                });
            }
        });
    </script>
    
    <script src="js/icon-fixes.js"></script>
</body>
</html>