<?php
$page_title = "Analytics & Reports - Fundraising System";
include 'layout-header.php';

// Check access
if (!in_array($user_role, ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

// Get date parameters
$currentYear = date('Y');
$currentMonth = date('n');
$selectedYear = $_GET['year'] ?? $currentYear;
$selectedMonth = $_GET['month'] ?? $currentMonth;

try {
    // Performance summary data
    $today = date('Y-m-d');
    
    // Today's performance
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_kunjungan,
            COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as sukses_kunjungan,
            COALESCE(SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END), 0) as total_donasi
        FROM kunjungan 
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$today]);
    $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly performance
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_kunjungan,
            COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as sukses_kunjungan,
            COALESCE(SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END), 0) as total_donasi,
            COUNT(DISTINCT fundraiser_id) as active_fundraisers
        FROM kunjungan 
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
    ");
    $stmt->execute([$selectedYear, $selectedMonth]);
    $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fundraiser performance
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.target,
            u.status,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(COUNT(CASE WHEN k.status = 'berhasil' THEN 1 END), 0) as sukses_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id 
            AND YEAR(k.created_at) = ? AND MONTH(k.created_at) = ?
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.target, u.status
        ORDER BY total_donasi DESC
    ");
    $stmt->execute([$selectedYear, $selectedMonth]);
    $fundraiserPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate conversion rates
    $todayConversion = $todayStats['total_kunjungan'] > 0 ? round(($todayStats['sukses_kunjungan'] / $todayStats['total_kunjungan']) * 100, 1) : 0;
    $monthlyConversion = $monthlyStats['total_kunjungan'] > 0 ? round(($monthlyStats['sukses_kunjungan'] / $monthlyStats['total_kunjungan']) * 100, 1) : 0;
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $todayStats = ['total_kunjungan' => 0, 'sukses_kunjungan' => 0, 'total_donasi' => 0];
    $monthlyStats = ['total_kunjungan' => 0, 'sukses_kunjungan' => 0, 'total_donasi' => 0, 'active_fundraisers' => 0];
    $fundraiserPerformance = [];
    $todayConversion = 0;
    $monthlyConversion = 0;
}

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

echo get_csrf_token_meta();
?>

<!-- Analytics & Reports Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analytics & Reports</h1>
            <p class="mt-1 text-sm text-gray-600">Analisis performa dan laporan fundraising</p>
            
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

    <!-- Today's Performance Summary -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">📊 Ringkasan Performa Hari Ini</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600"><?php echo number_format($todayStats['total_kunjungan']); ?></div>
                <div class="text-sm text-gray-600">Total Kunjungan</div>
                <div class="text-xs text-gray-500">Hari ini</div>
            </div>
            
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600"><?php echo number_format($todayStats['sukses_kunjungan']); ?></div>
                <div class="text-sm text-gray-600">Donasi Berhasil</div>
                <div class="text-xs text-gray-500">Hari ini</div>
            </div>
            
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600">Rp <?php echo number_format($todayStats['total_donasi'], 0, ',', '.'); ?></div>
                <div class="text-sm text-gray-600">Total Donasi</div>
                <div class="text-xs text-gray-500">Hari ini</div>
            </div>
            
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-3xl font-bold text-purple-600"><?php echo $todayConversion; ?>%</div>
                <div class="text-sm text-gray-600">Conversion Rate</div>
                <div class="text-xs text-gray-500">Hari ini</div>
            </div>
        </div>
    </div>

    <!-- Period Selection & Monthly Report -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">📈 Laporan Bulanan</h2>
            
            <!-- Year & Month Selection -->
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <form method="GET" class="flex items-center space-x-2">
                    <select name="year" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php for ($y = 2022; $y <= $currentYear + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <select name="month" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo $selectedMonth == $num ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Load Data
                    </button>
                </form>
                
                <button onclick="generateDetailedReport()" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a4 4 0 01-4-4V5a4 4 0 014-4h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a4 4 0 01-4 4z"></path>
                    </svg>
                    Generate Laporan
                </button>
                
                <button onclick="exportAnalytics()" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Excel
                </button>
            </div>
        </div>
        
        <!-- Monthly Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($monthlyStats['total_kunjungan']); ?></div>
                <div class="text-sm text-gray-600">Total Kunjungan</div>
                <div class="text-xs text-gray-500"><?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></div>
            </div>
            
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">Rp <?php echo number_format($monthlyStats['total_donasi'], 0, ',', '.'); ?></div>
                <div class="text-sm text-gray-600">Total Donasi</div>
                <div class="text-xs text-gray-500"><?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></div>
            </div>
            
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $monthlyConversion; ?>%</div>
                <div class="text-sm text-gray-600">Conversion Rate</div>
                <div class="text-xs text-gray-500"><?php echo $monthlyStats['sukses_kunjungan']; ?> dari <?php echo $monthlyStats['total_kunjungan']; ?></div>
            </div>
            
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-2xl font-bold text-purple-600"><?php echo $monthlyStats['active_fundraisers']; ?></div>
                <div class="text-sm text-gray-600">Active Fundraisers</div>
                <div class="text-xs text-gray-500">Bulan ini</div>
            </div>
        </div>
    </div>

    <!-- Fundraiser Performance Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Performance per Fundraiser - <?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?>
            </h2>
        </div>
        
        <?php if (!empty($fundraiserPerformance)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Harian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress Hari Ini</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kunjungan Bulan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sukses Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Donasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ranking</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($fundraiserPerformance as $index => $f): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <span class="text-xs font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                    <div class="text-xs text-gray-500">ID: <?php echo $f['id']; ?></div>
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
                            <div class="text-lg font-medium text-gray-900"><?php echo number_format($f['total_kunjungan']); ?></div>
                            <div class="text-xs text-gray-500">kunjungan</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0; ?>
                            <div class="text-lg font-medium <?php echo $successRate >= 70 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                <?php echo $successRate; ?>%
                            </div>
                            <div class="text-xs text-gray-500"><?php echo $f['sukses_kunjungan']; ?> / <?php echo $f['total_kunjungan']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-lg font-bold text-green-600">Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php 
                                $rank = $index + 1;
                                $rankColor = $rank <= 3 ? 'text-yellow-600' : 'text-gray-600';
                                $rankIcon = $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : ''));
                            ?>
                            <div class="text-lg font-bold <?php echo $rankColor; ?>">
                                <?php echo $rankIcon; ?> #<?php echo $rank; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-6 text-center text-gray-500">
            <div class="text-6xl mb-4">📊</div>
            <h3 class="text-lg font-medium mb-2">Tidak ada data performance</h3>
            <p class="text-sm">Pilih periode yang berbeda atau tambahkan data kunjungan</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">📥 Export & Reports</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <button onclick="exportMonthlyReport()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Excel Report
            </button>
            
            <button onclick="exportPDF()" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                PDF Report
            </button>
            
            <button onclick="exportCSV()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                CSV Export
            </button>
            
            <button onclick="printReport()" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Report
            </button>
        </div>
    </div>
</div>

<script>
// Export and report functions
function exportMonthlyReport() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    window.open(`analytics.php?export=excel&year=${year}&month=${month}`, '_blank');
}

function exportPDF() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    window.open(`analytics.php?export=pdf&year=${year}&month=${month}`, '_blank');
}

function exportCSV() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    window.open(`analytics.php?export=csv&year=${year}&month=${month}`, '_blank');
}

function generateDetailedReport() {
    const year = <?php echo $selectedYear; ?>;
    const month = <?php echo $selectedMonth; ?>;
    const monthName = '<?php echo $months[$selectedMonth]; ?>';
    
    // Open detailed report in new window
    const reportWindow = window.open('', '_blank');
    reportWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Laporan Detail ${monthName} ${year}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .card { padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
                .card h3 { margin: 0 0 10px 0; color: #666; }
                .card .value { font-size: 24px; font-weight: bold; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .print-section { margin: 20px 0; text-align: center; }
                @media print { .print-section { display: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 LAPORAN FUNDRAISING DETAIL</h1>
                <h2>${monthName} ${year}</h2>
                <p>Generated: ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</p>
            </div>
            
            <div class="summary">
                <div class="card">
                    <h3>Total Kunjungan</h3>
                    <div class="value" style="color: #3b82f6;">${<?php echo $monthlyStats['total_kunjungan']; ?>.toLocaleString('id-ID')}</div>
                </div>
                <div class="card">
                    <h3>Total Donasi</h3>
                    <div class="value" style="color: #10b981;">Rp ${<?php echo $monthlyStats['total_donasi']; ?>.toLocaleString('id-ID')}</div>
                </div>
                <div class="card">
                    <h3>Conversion Rate</h3>
                    <div class="value" style="color: #8b5cf6;">${<?php echo $monthlyConversion; ?>}%</div>
                </div>
                <div class="card">
                    <h3>Active Fundraisers</h3>
                    <div class="value" style="color: #f59e0b;">${<?php echo $monthlyStats['active_fundraisers']; ?>}</div>
                </div>
            </div>
            
            <h3>📋 Performance Detail per Fundraiser</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Nama Fundraiser</th>
                        <th>Target Harian</th>
                        <th>Total Kunjungan</th>
                        <th>Sukses Rate</th>
                        <th>Total Donasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${<?php echo json_encode($fundraiserPerformance); ?>.map((f, index) => {
                        const successRate = f.total_kunjungan > 0 ? Math.round((f.sukses_kunjungan / f.total_kunjungan) * 100 * 10) / 10 : 0;
                        const rank = index + 1;
                        const rankIcon = rank === 1 ? '🥇' : (rank === 2 ? '🥈' : (rank === 3 ? '🥉' : ''));
                        
                        return \`
                            <tr>
                                <td>\${rankIcon} #\${rank}</td>
                                <td>\${f.name}</td>
                                <td>\${f.target} kunjungan</td>
                                <td>\${f.total_kunjungan.toLocaleString('id-ID')}</td>
                                <td>\${successRate}%</td>
                                <td>Rp \${f.total_donasi.toLocaleString('id-ID')}</td>
                                <td>\${f.status}</td>
                            </tr>
                        \`;
                    }).join('')}
                </tbody>
            </table>
            
            <div class="print-section">
                <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 10px;">
                    🖨️ Print Laporan
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 10px;">
                    ✖️ Close
                </button>
            </div>
        </body>
        </html>
    `);
}

function printReport() {
    window.print();
}
</script>

<?php 
// Handle exports
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $exportYear = $_GET['year'] ?? $currentYear;
    $exportMonth = $_GET['month'] ?? $currentMonth;
    $monthName = $months[$exportMonth];
    
    if ($exportType === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="analytics_' . $exportYear . '_' . $exportMonth . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th colspan='7'>LAPORAN ANALYTICS - $monthName $exportYear</th></tr>";
        echo "<tr><th>Rank</th><th>Nama</th><th>Target Harian</th><th>Kunjungan Bulan</th><th>Sukses Rate</th><th>Total Donasi</th><th>Status</th></tr>";
        
        foreach ($fundraiserPerformance as $index => $f) {
            $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0;
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>" . htmlspecialchars($f['name']) . "</td>";
            echo "<td>" . $f['target'] . "</td>";
            echo "<td>" . $f['total_kunjungan'] . "</td>";
            echo "<td>" . $successRate . "%</td>";
            echo "<td>" . number_format($f['total_donasi'], 0, ',', '.') . "</td>";
            echo "<td>" . $f['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
        
    } elseif ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="analytics_' . $exportYear . '_' . $exportMonth . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Rank', 'Nama', 'Target Harian', 'Kunjungan Bulan', 'Sukses Rate', 'Total Donasi', 'Status']);
        
        foreach ($fundraiserPerformance as $index => $f) {
            $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0;
            fputcsv($output, [
                $index + 1,
                $f['name'],
                $f['target'],
                $f['total_kunjungan'],
                $successRate . '%',
                'Rp ' . number_format($f['total_donasi'], 0, ',', '.'),
                $f['status']
            ]);
        }
        fclose($output);
        exit;
    }
}
?>

<?php include 'layout-footer.php'; ?>