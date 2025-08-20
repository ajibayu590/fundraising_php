<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'config.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Check if user has 'user' role
if ($user['role'] !== 'user') {
    header("Location: dashboard.php");
    exit;
}

// Determine sidebar
$sidebarFile = 'sidebar-user.php';

// Get user-specific dashboard data
try {
    $today = date('Y-m-d');
    $user_id = $user['id'];
    
    // User's kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, $today]);
    $user_kunjungan_hari_ini = $stmt->fetchColumn();
    
    // User's donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $user_donasi_berhasil = $stmt->fetchColumn();
    
    // User's total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $user_total_donasi = $stmt->fetchColumn();
    
    // User's target progress
    $user_target = $user['target'] ?? 8;
    $user_progress_percent = $user_target > 0 ? min(100, round(($user_kunjungan_hari_ini / $user_target) * 100)) : 0;
    
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
    $user_recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User's monthly statistics
    $current_month = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_kunjungan,
            COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as sukses_kunjungan,
            COALESCE(SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END), 0) as total_donasi
        FROM kunjungan 
        WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$user_id, $current_month]);
    $user_monthly_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // User's weekly progress (last 7 days)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as tanggal,
            COUNT(*) as kunjungan,
            COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as sukses,
            COALESCE(SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END), 0) as donasi
        FROM kunjungan 
        WHERE fundraiser_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY tanggal DESC
    ");
    $stmt->execute([$user_id]);
    $user_weekly_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User's performance ranking (among all users)
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id AND DATE_FORMAT(k.created_at, '%Y-%m') = ?
        WHERE u.role = 'user'
        GROUP BY u.id, u.name
        ORDER BY total_donasi DESC, total_kunjungan DESC
    ");
    $stmt->execute([$current_month]);
    $all_users_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find user's rank
    $user_rank = 0;
    foreach ($all_users_ranking as $index => $rank_user) {
        if ($rank_user['id'] == $user_id) {
            $user_rank = $index + 1;
            break;
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $user_kunjungan_hari_ini = 0;
    $user_donasi_berhasil = 0;
    $user_total_donasi = 0;
    $user_progress_percent = 0;
    $user_recent_activities = [];
    $user_monthly_stats = ['total_kunjungan' => 0, 'sukses_kunjungan' => 0, 'total_donasi' => 0];
    $user_weekly_progress = [];
    $user_rank = 0;
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
    <title>Dashboard - Fundraising System</title>
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
                    <?php
                    require_once 'logo_manager.php';
                    echo get_logo_html('w-10 h-10', 'mr-3');
                    ?>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Dashboard Saya</h1>
                        <p class="text-sm text-gray-600">Fundraiser Portal</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Fundraiser</span>
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
                    <a href="kunjungan-user.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Kunjungan
                    </a>
                    
                    <a href="donatur-user.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Donatur
                    </a>
                    
                    <a href="profile.php" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Edit Profile
                    </a>
                </div>
            </div>

            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard Fundraiser</h2>
                <p class="text-gray-600 mt-2">Selamat datang, <?php echo htmlspecialchars($user['name']); ?>! Monitor performa fundraising Anda.</p>
            </div>

            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- User Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Today's Visits -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Kunjungan Hari Ini</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $user_kunjungan_hari_ini; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Successful Donations -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $user_donasi_berhasil; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Donations -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Donasi</p>
                            <p class="text-2xl font-semibold text-gray-900">Rp <?php echo number_format($user_total_donasi, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Target Progress -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Target Progress</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $user_progress_percent; ?>%</p>
                            <p class="text-xs text-gray-500"><?php echo $user_kunjungan_hari_ini; ?>/<?php echo $user_target; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Target Progress Bar -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 Progress Target Hari Ini</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Target: <?php echo $user_target; ?> kunjungan</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo $user_kunjungan_hari_ini; ?> kunjungan</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full <?php echo $user_progress_percent >= 100 ? 'bg-green-500' : ($user_progress_percent >= 75 ? 'bg-yellow-500' : ($user_progress_percent >= 50 ? 'bg-blue-500' : 'bg-red-500')); ?>" 
                             style="width: <?php echo min(100, $user_progress_percent); ?>%"></div>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">0%</span>
                        <span class="text-gray-500">50%</span>
                        <span class="text-gray-500">100%</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Performance & Ranking -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Statistics -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Performa Bulan Ini</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                            <span class="text-sm font-medium">Total Kunjungan</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo $user_monthly_stats['total_kunjungan']; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm font-medium">Donasi Berhasil</span>
                            <span class="text-lg font-bold text-green-600"><?php echo $user_monthly_stats['sukses_kunjungan']; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm font-medium">Total Donasi</span>
                            <span class="text-lg font-bold text-purple-600">Rp <?php echo number_format($user_monthly_stats['total_donasi'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Ranking -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🏆 Ranking Bulan Ini</h3>
                    <div class="text-center">
                        <?php if ($user_rank > 0): ?>
                            <div class="text-4xl font-bold text-yellow-600 mb-2">
                                <?php 
                                    $rank_icon = $user_rank == 1 ? '🥇' : ($user_rank == 2 ? '🥈' : ($user_rank == 3 ? '🥉' : ''));
                                    echo $rank_icon . ' #' . $user_rank;
                                ?>
                            </div>
                            <p class="text-sm text-gray-600">Dari <?php echo count($all_users_ranking); ?> fundraiser</p>
                        <?php else: ?>
                            <div class="text-4xl font-bold text-gray-400 mb-2">-</div>
                            <p class="text-sm text-gray-600">Belum ada data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">📝 Aktivitas Terbaru</h3>
                    <p class="text-sm text-gray-600">Kunjungan dan donasi terbaru Anda</p>
                </div>
                
                <?php if (!empty($user_recent_activities)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donatur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($user_recent_activities as $activity): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['donatur_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['donatur_hp'] ?? ''); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $activity['status'] === 'berhasil' ? 'bg-green-100 text-green-800' : 
                                             ($activity['status'] === 'follow-up' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                    ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($activity['status'] === 'berhasil' && $activity['nominal']): ?>
                                        Rp <?php echo number_format($activity['nominal'], 0, ',', '.'); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($activity['catatan'] ?? ''); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <div class="text-6xl mb-4">📝</div>
                    <h3 class="text-lg font-medium mb-2">Belum ada aktivitas</h3>
                    <p class="text-sm">Mulai dengan menambahkan kunjungan baru</p>
                    <a href="kunjungan-user.php" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Tambah Kunjungan
                    </a>
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