<?php
session_start();

// Check if user is logged in and is a user role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Database connection
require_once 'config.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get user-specific dashboard data
try {
    $today = date('Y-m-d');
    $current_month = date('Y-m');
    
    // User's kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, $today]);
    $kunjungan_hari_ini = $stmt->fetchColumn();
    
    // User's donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $donasi_berhasil_hari_ini = $stmt->fetchColumn();
    
    // User's total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $total_donasi_hari_ini = $stmt->fetchColumn();
    
    // User's kunjungan bulan ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$user_id, $current_month]);
    $kunjungan_bulan_ini = $stmt->fetchColumn();
    
    // User's total donasi bulan ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $current_month]);
    $total_donasi_bulan_ini = $stmt->fetchColumn();
    
    // User's recent activities (last 10)
    $stmt = $pdo->prepare("
        SELECT k.*, d.nama as donatur_name, d.hp as donatur_hp
        FROM kunjungan k 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE k.fundraiser_id = ?
        ORDER BY k.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User's target progress
    $target = $user['target'];
    $progress_percentage = $target > 0 ? min(100, ($kunjungan_hari_ini / $target) * 100) : 0;
    
    // Weekly progress (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as kunjungan_count, 
               SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END) as total_donasi
        FROM kunjungan 
        WHERE fundraiser_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$user_id]);
    $weekly_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Dashboard Fundraiser - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/main.css">
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
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        .main-content {
            margin-top: 64px !important;
            padding: 1rem !important;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px !important;
                margin-top: 64px !important;
            }
        }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .progress-ring-circle {
            transition: stroke-dasharray 0.35s;
            transform-origin: 50% 50%;
        }
        
        /* Responsive fixes */
        @media (max-width: 640px) {
            .main-content {
                margin-left: 0 !important;
                padding: 0.5rem !important;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
            }
            
            .overflow-x-auto {
                overflow-x: auto !important;
            }
            
            table {
                min-width: 600px !important;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'layout-header.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'sidebar-user.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Section -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Selamat Datang, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p class="text-gray-600">Dashboard Fundraiser - Pantau performa fundraising Anda</p>
        </div>
        
        <!-- Redirect Message -->
        <?php if (isset($_SESSION['redirect_message'])): ?>
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['redirect_message']); ?></span>
                <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-yellow-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
            <?php unset($_SESSION['redirect_message']); ?>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
            <!-- Target Progress -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Target Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $kunjungan_hari_ini; ?>/<?php echo $target; ?></p>
                    </div>
                    <div class="relative w-16 h-16">
                        <svg class="w-16 h-16 progress-ring">
                            <circle class="progress-ring-circle" stroke="#e5e7eb" stroke-width="4" fill="transparent" r="26" cx="32" cy="32"/>
                            <circle class="progress-ring-circle" stroke="#3b82f6" stroke-width="4" fill="transparent" r="26" cx="32" cy="32" 
                                    stroke-dasharray="<?php echo 2 * M_PI * 26 * $progress_percentage / 100; ?> <?php echo 2 * M_PI * 26; ?>"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-gray-900"><?php echo round($progress_percentage); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kunjungan Hari Ini -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Kunjungan Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $kunjungan_hari_ini; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Donasi Berhasil -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $donasi_berhasil_hari_ini; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Total Donasi Hari Ini -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Donasi Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_donasi_hari_ini, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8">
            <!-- Monthly Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Bulan Ini</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Kunjungan:</span>
                        <span class="font-semibold"><?php echo $kunjungan_bulan_ini; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Donasi:</span>
                        <span class="font-semibold">Rp <?php echo number_format($total_donasi_bulan_ini, 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Rata-rata per Kunjungan:</span>
                        <span class="font-semibold">
                            <?php 
                            if ($kunjungan_bulan_ini > 0) {
                                echo 'Rp ' . number_format($total_donasi_bulan_ini / $kunjungan_bulan_ini, 0, ',', '.');
                            } else {
                                echo 'Rp 0';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Performa 7 Hari Terakhir</h3>
                <canvas id="weeklyChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donatur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($recent_activities)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada aktivitas kunjungan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['donatur_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['donatur_hp']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'berhasil' => 'bg-green-100 text-green-800',
                                            'tidak-berhasil' => 'bg-red-100 text-red-800',
                                            'follow-up' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $statusColor = $statusColors[$activity['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $activity['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($activity['status'] === 'berhasil' && $activity['nominal'] > 0): ?>
                                            Rp <?php echo number_format($activity['nominal'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($activity['alamat']); ?>">
                                            <?php echo htmlspecialchars($activity['alamat']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'layout-footer.php'; ?>
    
    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            if (mobileMenuBtn && sidebar) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay.classList.toggle('active');
                });
                
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                });
            }
        });
        
        // Weekly Chart
        const weeklyData = <?php echo json_encode($weekly_progress); ?>;
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        
        const labels = weeklyData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
        }).reverse();
        
        const kunjunganData = weeklyData.map(item => parseInt(item.kunjungan_count)).reverse();
        const donasiData = weeklyData.map(item => parseInt(item.total_donasi) / 1000000).reverse(); // Convert to millions
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kunjungan',
                    data: kunjunganData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Donasi (Juta Rp)',
                    data: donasiData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Jumlah Kunjungan'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Donasi (Juta Rp)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>