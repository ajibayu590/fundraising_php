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

// Tentukan sidebar berdasarkan role user
$sidebarFile = ($user['role'] == 'admin') ? 'sidebar-admin.php' : 'sidebar-user.php';

// Get date parameters
$currentYear = date('Y');
$currentMonth = date('n');
$selectedYear = $_GET['year'] ?? $currentYear;
$selectedMonth = $_GET['month'] ?? $currentMonth;

// Handle export requests BEFORE any output
if (!empty($_GET['export'])) {
    $type = strtolower($_GET['export']);
    if (in_array($type, ['xls','excel','xlsx'], true)) {
        // Generate Excel export
        $filename = 'analytics_' . $selectedYear . '_' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        // Get data for export
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.name as fundraiser,
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
            $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>Fundraiser</th><th>Target</th><th>Status</th><th>Total Kunjungan</th><th>Sukses Kunjungan</th><th>Total Donasi</th><th>Kunjungan Hari Ini</th></tr>";
            
            foreach ($exportData as $row) {
                $fundraiser = htmlspecialchars($row['fundraiser']);
                $target = (int)$row['target'];
                $status = htmlspecialchars($row['status']);
                $totalKunjungan = (int)$row['total_kunjungan'];
                $suksesKunjungan = (int)$row['sukses_kunjungan'];
                $totalDonasi = number_format((float)$row['total_donasi'], 0, ',', '.');
                $kunjunganHariIni = (int)$row['kunjungan_hari_ini'];
                
                echo "<tr><td>$fundraiser</td><td>$target</td><td>$status</td><td>$totalKunjungan</td><td>$suksesKunjungan</td><td>$totalDonasi</td><td>$kunjunganHariIni</td></tr>";
            }
            echo "</table>";
            exit;
        } catch (Exception $e) {
            // If export fails, redirect back with error
            header("Location: analytics-fixed.php?error=" . urlencode("Export failed: " . $e->getMessage()));
            exit;
        }
    }
}

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Analytics & Reports - Fundraising System</title>
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Analytics & Reports</h1>
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
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Analytics & Reports</h2>
                <p class="text-gray-600 mt-2">Analisis performa dan laporan fundraising</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Date Filter -->
            <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php for ($year = $currentYear; $year >= $currentYear - 2; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($months as $num => $name): ?>
                            <option value="<?php echo $num; ?>" <?php echo $selectedMonth == $num ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Filter
                        </button>
                        <a href="analytics-fixed.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            Reset
                        </a>
                        <a href="analytics-fixed.php?year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>&export=excel" 
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Export Excel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Performance Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Today's Performance -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Performa Hari Ini</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Total Kunjungan</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo number_format($todayStats['total_kunjungan']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Sukses</span>
                            <span class="text-lg font-bold text-green-600"><?php echo number_format($todayStats['sukses_kunjungan']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Total Donasi</span>
                            <span class="text-lg font-bold text-purple-600">Rp <?php echo number_format($todayStats['total_donasi'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Conversion Rate</span>
                            <span class="text-lg font-bold text-orange-600"><?php echo $todayConversion; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Performance -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Performa Bulan <?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Total Kunjungan</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo number_format($monthlyStats['total_kunjungan']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Sukses</span>
                            <span class="text-lg font-bold text-green-600"><?php echo number_format($monthlyStats['sukses_kunjungan']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Total Donasi</span>
                            <span class="text-lg font-bold text-purple-600">Rp <?php echo number_format($monthlyStats['total_donasi'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Active Fundraisers</span>
                            <span class="text-lg font-bold text-indigo-600"><?php echo number_format($monthlyStats['active_fundraisers']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Conversion Rate</span>
                            <span class="text-lg font-bold text-orange-600"><?php echo $monthlyConversion; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Quick Stats</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Avg Donasi</span>
                            <span class="text-lg font-bold text-green-600">
                                Rp <?php echo $monthlyStats['sukses_kunjungan'] > 0 ? number_format($monthlyStats['total_donasi'] / $monthlyStats['sukses_kunjungan'], 0, ',', '.') : '0'; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Success Rate</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo $monthlyConversion; ?>%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Per Fundraiser</span>
                            <span class="text-lg font-bold text-purple-600">
                                <?php echo $monthlyStats['active_fundraisers'] > 0 ? number_format($monthlyStats['total_kunjungan'] / $monthlyStats['active_fundraisers'], 1) : '0'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🔧 Actions</h3>
                    <div class="space-y-3">
                        <a href="analytics-fixed.php?year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>&export=excel" 
                           class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Excel
                        </a>
                        <button onclick="window.print()" 
                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Print Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fundraiser Performance Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">🏆 Fundraiser Performance</h2>
                    <p class="text-sm text-gray-600">Ranking fundraiser berdasarkan total donasi bulan <?php echo $months[$selectedMonth]; ?> <?php echo $selectedYear; ?></p>
                </div>
                
                <?php if (!empty($fundraiserPerformance)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Kunjungan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sukses</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Donasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari Ini</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($fundraiserPerformance as $index => $f): ?>
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
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-blue-600"><?php echo $f['target']; ?></div>
                                    <div class="text-xs text-gray-500">per hari</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-900"><?php echo number_format($f['total_kunjungan']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-green-600"><?php echo number_format($f['sukses_kunjungan']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0; ?>
                                    <div class="text-sm font-medium <?php echo $successRate >= 70 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo $successRate; ?>%
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-green-600">Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-blue-600"><?php echo number_format($f['kunjungan_hari_ini']); ?></div>
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
                    <div class="text-6xl mb-4">📊</div>
                    <h3 class="text-lg font-medium mb-2">Tidak ada data performance</h3>
                    <p class="text-sm">Tidak ada data untuk periode yang dipilih</p>
                </div>
                <?php endif; ?>
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