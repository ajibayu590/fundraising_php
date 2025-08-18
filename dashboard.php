<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

// Get dashboard data directly from database (like debug dashboard)
try {
    $today = date('Y-m-d');
    
    // Total kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $total_kunjungan = $stmt->fetchColumn();
    
    // Donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $donasi_berhasil = $stmt->fetchColumn();
    
    // Total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $total_donasi = $stmt->fetchColumn();
    
    // Fundraiser aktif (user role, bukan fundraiser)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $fundraiser_aktif = $stmt->fetchColumn();
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT k.*, u.name as fundraiser_name, d.nama as donatur_name 
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        ORDER BY k.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Progress data for each user
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.target,
        COALESCE(COUNT(k.id), 0) as current_kunjungan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id AND DATE(k.created_at) = ?
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.target
        ORDER BY u.name
    ");
    $stmt->execute([$today]);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Dashboard - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/main.css">
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        /* Critical inline CSS to prevent header overlap */
        @media (max-width: 768px) {
            body { margin: 0 !important; padding: 0 !important; }
            header { 
                position: fixed !important; 
                top: 0 !important; 
                left: 0 !important; 
                right: 0 !important; 
                z-index: 10000 !important; 
                background: white !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }
            .page-wrapper { padding-top: 5rem !important; }
            .sidebar { z-index: 100 !important; }
            .main-content { padding-top: 1rem !important; }
        }
        
        @media (min-width: 769px) {
            header { 
                position: relative !important; 
                z-index: 10000 !important; 
                background: white !important; 
            }
            .sidebar { z-index: 100 !important; }
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

    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Fundraising System</h1>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo ucfirst($user_role); ?>
                    </span>
                    <a href="logout.php" class="text-xs md:text-sm text-red-600 hover:text-red-800 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="flex">
            <!-- Sidebar -->
            <?php include $user_role === 'admin' ? 'sidebar-admin.php' : 'sidebar-user.php'; ?>

            <!-- Main Content -->
            <div class="main-content flex-1 p-4 md:p-8">
            <!-- Dashboard Header -->
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Dashboard</h2>
                <p class="text-gray-600">Selamat datang di sistem fundraising</p>
            </div>

            <!-- Error Display -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="stats-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Kunjungan Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_kunjungan ?? 0; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $donasi_berhasil ?? 0; ?></p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Donasi Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_donasi ?? 0, 0, ',', '.'); ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Fundraiser Aktif</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $fundraiser_aktif ?? 0; ?></p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Target Hari Ini -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Progress Target Hari Ini</h3>
                <?php if (!empty($progress_data)): ?>
                    <div class="space-y-4">
                        <?php foreach ($progress_data as $progress): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($progress['name']); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo $progress['current_kunjungan']; ?>/<?php echo $progress['target']; ?></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <?php 
                                        $percentage = $progress['target'] > 0 ? min(100, ($progress['current_kunjungan'] / $progress['target']) * 100) : 0;
                                        ?>
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                </div>
                        <?php endforeach; ?>
            </div>
                <?php else: ?>
                    <p class="text-gray-500">Tidak ada data progress</p>
            <?php endif; ?>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Aktivitas Terbaru</h3>
                <?php if (!empty($recent_activities)): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($activity['fundraiser_name'] ?? 'Unknown'); ?> → 
                                            <?php echo htmlspecialchars($activity['donatur_name'] ?? 'Unknown'); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Status: <?php echo ucfirst($activity['status']); ?> | 
                                            Donasi: Rp <?php echo number_format($activity['nominal'] ?? 0, 0, ',', '.'); ?>
                                        </p>
                                        <?php if (!empty($activity['catatan'])): ?>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($activity['catatan']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-gray-500">Tidak ada aktivitas terbaru</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/utils.js"></script>
    <script src="js/data.js"></script>
    <script src="js/ui.js"></script>
    <script src="js/charts.js"></script>
    <script src="js/app.js"></script>
    <script src="js/mobile-menu.js"></script>
</body>
</html>
