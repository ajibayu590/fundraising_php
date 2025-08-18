<?php
$page_title = "Target & Goals - Fundraising System";
include 'layout-header.php';

// Check access
if (!in_array($user_role, ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

// Get current date info
$currentYear = date('Y');
$currentMonth = date('n');
$selectedYear = $_GET['year'] ?? $currentYear;
$selectedMonth = $_GET['month'] ?? $currentMonth;

// Load target and performance data
try {
    // Get all fundraisers with their targets and performance
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.target,
            u.status,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini,
            COALESCE(COUNT(CASE WHEN YEAR(k.created_at) = ? AND MONTH(k.created_at) = ? THEN 1 END), 0) as kunjungan_bulan,
            COALESCE(SUM(CASE WHEN YEAR(k.created_at) = ? AND MONTH(k.created_at) = ? AND k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as donasi_bulan,
            COALESCE(COUNT(CASE WHEN YEAR(k.created_at) = ? AND MONTH(k.created_at) = ? AND k.status = 'berhasil' THEN 1 END), 0) as sukses_bulan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.email, u.target, u.status
        ORDER BY u.name
    ");
    $stmt->execute([$selectedYear, $selectedMonth, $selectedYear, $selectedMonth, $selectedYear, $selectedMonth]);
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate performance summary
    $totalFundraisers = count($fundraisers);
    $targetTercapai = 0;
    $dalamProgress = 0;
    $perluPerhatian = 0;
    $totalTargetHarian = 0;
    $totalKunjunganHariIni = 0;
    
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
    
    // Monthly report data
    $totalKunjunganBulan = array_sum(array_column($fundraisers, 'kunjungan_bulan'));
    $totalDonasiBulan = array_sum(array_column($fundraisers, 'donasi_bulan'));
    $totalSuksesBulan = array_sum(array_column($fundraisers, 'sukses_bulan'));
    $conversionRate = $totalKunjunganBulan > 0 ? round(($totalSuksesBulan / $totalKunjunganBulan) * 100, 1) : 0;
    
} catch (Exception $e) {
    $fundraisers = [];
    $error_message = $e->getMessage();
    $targetTercapai = 0;
    $dalamProgress = 0;
    $perluPerhatian = 0;
    $rataPencapaian = 0;
    $totalKunjunganBulan = 0;
    $totalDonasiBulan = 0;
    $conversionRate = 0;
}

// Handle target updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'admin') {
    try {
        check_csrf();
        
        if (isset($_POST['bulk_target'])) {
            // Bulk update
            $newTarget = (int)$_POST['new_target'];
            if ($newTarget > 0) {
                $stmt = $pdo->prepare("UPDATE users SET target = ? WHERE role = 'user'");
                $stmt->execute([$newTarget]);
                $affected = $stmt->rowCount();
                
                $success_message = "Berhasil update target untuk $affected fundraiser";
                header("Location: target.php?success=" . urlencode($success_message));
                exit;
            }
        } elseif (isset($_POST['individual_target'])) {
            // Individual update
            $userId = (int)$_POST['user_id'];
            $newTarget = (int)$_POST['target'];
            
            if ($userId > 0 && $newTarget > 0) {
                $stmt = $pdo->prepare("UPDATE users SET target = ? WHERE id = ? AND role = 'user'");
                $stmt->execute([$newTarget, $userId]);
                
                $success_message = "Target berhasil diupdate";
                header("Location: target.php?success=" . urlencode($success_message));
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

echo get_csrf_token_meta();
?>

<style>
/* Target page specific styles */
.target-card {
    transition: transform 0.2s ease-in-out;
}

.target-card:hover {
    transform: translateY(-2px);
}

.progress-bar {
    transition: width 0.5s ease-in-out;
}
</style>

<!-- Target & Goals Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Target & Goals</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola target kunjungan dan monitor performa</p>
            
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6 target-card">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600"><?php echo $targetTercapai; ?></div>
                <div class="text-sm text-gray-600">Target Tercapai</div>
                <div class="text-xs text-gray-500 mt-1">≥100% target harian</div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 target-card">
            <div class="text-center">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $dalamProgress; ?></div>
                <div class="text-sm text-gray-600">Dalam Progress</div>
                <div class="text-xs text-gray-500 mt-1">50-99% target</div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 target-card">
            <div class="text-center">
                <div class="text-3xl font-bold text-red-600"><?php echo $perluPerhatian; ?></div>
                <div class="text-sm text-gray-600">Perlu Perhatian</div>
                <div class="text-xs text-gray-500 mt-1"><50% target</div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 target-card">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600"><?php echo $rataPencapaian; ?>%</div>
                <div class="text-sm text-gray-600">Rata-rata Pencapaian</div>
                <div class="text-xs text-gray-500 mt-1">Target harian</div>
            </div>
        </div>
    </div>

    <!-- Target Management -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Individual Target Setting -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Set Target Individual</h2>
            
            <?php if (!empty($fundraisers)): ?>
            <div class="space-y-3">
                <?php foreach ($fundraisers as $f): ?>
                <div class="flex items-center justify-between p-3 border rounded-lg">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                        <div class="text-sm text-gray-500">Current: <?php echo $f['target']; ?> kunjungan/hari</div>
                        <div class="text-xs text-gray-400">Progress: <?php echo $f['kunjungan_hari_ini']; ?>/<?php echo $f['target']; ?> (<?php echo $f['target'] > 0 ? round(($f['kunjungan_hari_ini'] / $f['target']) * 100, 1) : 0; ?>%)</div>
                    </div>
                    <?php if ($user_role === 'admin'): ?>
                    <div class="flex items-center space-x-2">
                        <form method="POST" class="flex items-center space-x-2">
                            <?php echo get_csrf_token_field(); ?>
                            <input type="hidden" name="individual_target" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $f['id']; ?>">
                            <input type="number" name="target" value="<?php echo $f['target']; ?>" min="1" max="50" 
                                   class="w-16 px-2 py-1 text-sm border rounded focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                Update
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <p>Tidak ada data fundraiser</p>
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Insert dummy data terlebih dahulu</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bulk Target Setting -->
        <?php if ($user_role === 'admin'): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Target Massal</h2>
            
            <form method="POST" class="space-y-4">
                <?php echo get_csrf_token_field(); ?>
                <input type="hidden" name="bulk_target" value="1">
                
                <div>
                    <label for="new_target" class="block text-sm font-medium text-gray-700 mb-2">
                        Target Harian Baru untuk Semua Fundraiser
                    </label>
                    <input type="number" id="new_target" name="new_target" min="1" max="50" value="8" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Target akan diterapkan ke semua <?php echo $totalFundraisers; ?> fundraiser</p>
                </div>
                
                <button type="submit" onclick="return confirm('Update target semua fundraiser ke ' + document.getElementById('new_target').value + ' kunjungan per hari?')" 
                        class="w-full px-4 py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Update Target Semua Fundraiser
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Report -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Laporan Bulanan</h2>
            
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <!-- Year & Month Selection -->
                <form method="GET" class="flex items-center space-x-2">
                    <select name="year" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php for ($y = 2023; $y <= $currentYear + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <select name="month" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php 
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        foreach ($months as $num => $name):
                        ?>
                        <option value="<?php echo $num; ?>" <?php echo $selectedMonth == $num ? 'selected' : ''; ?>>
                            <?php echo $name; ?> <?php echo $selectedYear; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Load
                    </button>
                </form>
                
                <button onclick="generateReport()" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a4 4 0 01-4-4V5a4 4 0 014-4h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a4 4 0 01-4 4z"></path>
                    </svg>
                    Generate Laporan
                </button>
                
                <button onclick="exportReport()" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Excel
                </button>
            </div>
        </div>
        
        <!-- Report Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($totalKunjunganBulan); ?></div>
                <div class="text-sm text-gray-600">Total Kunjungan</div>
                <div class="text-xs text-gray-500"><?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></div>
            </div>
            
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">Rp <?php echo number_format($totalDonasiBulan, 0, ',', '.'); ?></div>
                <div class="text-sm text-gray-600">Total Donasi</div>
                <div class="text-xs text-gray-500"><?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></div>
            </div>
            
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-2xl font-bold text-purple-600"><?php echo $conversionRate; ?>%</div>
                <div class="text-sm text-gray-600">Conversion Rate</div>
                <div class="text-xs text-gray-500"><?php echo $totalSuksesBulan; ?> sukses dari <?php echo $totalKunjunganBulan; ?> kunjungan</div>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Performance Detail - <?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></h2>
        </div>
        
        <?php if (!empty($fundraisers)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Harian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress Hari Ini</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kunjungan Bulan Ini</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donasi Bulan Ini</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($fundraisers as $f): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <span class="text-xs font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($f['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-lg font-bold text-blue-600"><?php echo $f['target']; ?></div>
                            <div class="text-xs text-gray-500">kunjungan</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-center">
                                <div class="text-sm font-medium"><?php echo $f['kunjungan_hari_ini']; ?> / <?php echo $f['target']; ?></div>
                                <?php 
                                    $percent = $f['target'] > 0 ? min(100, round(($f['kunjungan_hari_ini'] / $f['target']) * 100)) : 0;
                                    $color = $percent >= 100 ? 'bg-green-500' : ($percent >= 75 ? 'bg-yellow-500' : ($percent >= 50 ? 'bg-blue-500' : 'bg-red-500'));
                                ?>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="h-2 rounded-full <?php echo $color; ?> progress-bar" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo $percent; ?>%</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm font-medium text-gray-900"><?php echo number_format($f['kunjungan_bulan']); ?></div>
                            <div class="text-xs text-gray-500">kunjungan</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm font-medium text-green-600">Rp <?php echo number_format($f['donasi_bulan'], 0, ',', '.'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo number_format($f['sukses_bulan']); ?> sukses</div>
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
            <p>Tidak ada data untuk ditampilkan</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Report functions
function generateReport() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    const monthName = '<?php echo $months[$selectedMonth]; ?>';
    
    // Generate detailed report
    const reportData = {
        period: `${monthName} ${year}`,
        totalKunjungan: <?php echo $totalKunjunganBulan; ?>,
        totalDonasi: <?php echo $totalDonasiBulan; ?>,
        conversionRate: <?php echo $conversionRate; ?>,
        fundraisers: <?php echo json_encode($fundraisers); ?>
    };
    
    // Create report window
    const reportWindow = window.open('', '_blank');
    reportWindow.document.write(`
        <html>
        <head>
            <title>Laporan ${reportData.period}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
                .card { padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f5f5f5; }
                .print-btn { margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 Laporan Fundraising</h1>
                <h2>${reportData.period}</h2>
                <p>Generated: ${new Date().toLocaleDateString('id-ID')}</p>
            </div>
            
            <div class="summary">
                <div class="card">
                    <h3>Total Kunjungan</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #3b82f6;">${reportData.totalKunjungan.toLocaleString('id-ID')}</div>
                </div>
                <div class="card">
                    <h3>Total Donasi</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #10b981;">Rp ${reportData.totalDonasi.toLocaleString('id-ID')}</div>
                </div>
                <div class="card">
                    <h3>Conversion Rate</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #8b5cf6;">${reportData.conversionRate}%</div>
                </div>
            </div>
            
            <h3>Detail per Fundraiser</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Target Harian</th>
                        <th>Kunjungan Bulan Ini</th>
                        <th>Donasi Bulan Ini</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${reportData.fundraisers.map(f => \`
                        <tr>
                            <td>\${f.name}</td>
                            <td>\${f.target} kunjungan</td>
                            <td>\${f.kunjungan_bulan.toLocaleString('id-ID')}</td>
                            <td>Rp \${f.donasi_bulan.toLocaleString('id-ID')}</td>
                            <td>\${f.status}</td>
                        </tr>
                    \`).join('')}
                </tbody>
            </table>
            
            <div class="print-btn">
                <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    🖨️ Print Laporan
                </button>
            </div>
        </body>
        </html>
    `);
}

function exportReport() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    const monthName = '<?php echo $months[$selectedMonth]; ?>';
    
    // Export to Excel format
    window.open(`target.php?export=excel&year=${year}&month=${month}`, '_blank');
}
</script>

<?php 
// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $exportYear = $_GET['year'] ?? $currentYear;
    $exportMonth = $_GET['month'] ?? $currentMonth;
    $monthName = $months[$exportMonth];
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_target_' . $exportYear . '_' . $exportMonth . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='5'>Laporan Target & Performance - $monthName $exportYear</th></tr>";
    echo "<tr><th>Nama</th><th>Target Harian</th><th>Kunjungan Bulan Ini</th><th>Donasi Bulan Ini</th><th>Status</th></tr>";
    
    foreach ($fundraisers as $f) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($f['name']) . "</td>";
        echo "<td>" . $f['target'] . "</td>";
        echo "<td>" . $f['kunjungan_bulan'] . "</td>";
        echo "<td>" . number_format($f['donasi_bulan'], 0, ',', '.') . "</td>";
        echo "<td>" . $f['status'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;
}
?>

<?php include 'layout-footer.php'; ?>