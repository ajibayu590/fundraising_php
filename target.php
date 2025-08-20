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
                header("Location: target.php?success=" . urlencode($success_message));
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

echo get_csrf_token_meta();
?>

<!-- Target & Goals Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Target & Goals</h1>
            <p class="mt-1 text-sm text-gray-600">Monitor performa dan kelola target global sistem</p>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="mt-2 p-3 bg-green-100 border border-green-300 rounded text-sm text-green-700">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="mt-2 p-3 bg-red-100 border border-red-300 rounded text-sm text-red-700">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="mt-4 sm:mt-0">
            <button onclick="window.location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Performance Summary -->
    <div class="bg-white rounded-lg shadow p-6">
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
                    <div class="text-3xl font-bold text-blue-600"><?php echo $rataPencapaian; ?>%</div>
                    <div class="text-sm text-blue-700 font-medium">Rata-rata Pencapaian</div>
                    <div class="text-xs text-blue-600 mt-1"><?php echo $totalKunjunganHariIni; ?> dari <?php echo $totalTargetHarian; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Target Management (Admin Only) -->
    <?php if ($user_role === 'admin'): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">🎯 Global Target Management</h2>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-yellow-800">
                    <strong>Global Target:</strong> Update target untuk semua fundraiser sekaligus. 
                    Untuk target individual, gunakan menu <a href="fundraiser.php" class="underline font-medium">Fundraiser</a>.
                </p>
            </div>
        </div>
        
        <form method="POST" class="max-w-md">
            <?php echo get_csrf_token_field(); ?>
            <input type="hidden" name="update_global_target" value="1">
            
            <div class="space-y-4">
                <div>
                    <label for="global_target" class="block text-sm font-medium text-gray-700 mb-2">
                        Target Harian Global untuk Semua Fundraiser
                    </label>
                    <input type="number" id="global_target" name="global_target" 
                           min="1" max="50" value="<?php echo $currentGlobalTarget; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">
                        Target ini akan diterapkan ke semua <?php echo $totalFundraisers; ?> fundraiser
                    </p>
                </div>
                
                <button type="submit" 
                        onclick="return confirm('Update target global untuk semua fundraiser menjadi ' + document.getElementById('global_target').value + ' kunjungan per hari?')" 
                        class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Update Target Global Semua Fundraiser
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Performance Leaderboard -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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

<?php include 'layout-footer.php'; ?>