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

// Get dashboard data directly from database
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
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Debug - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
</head>
<body class="bg-gray-100">
         <!-- Simple Debug Panel -->
     <div class="fixed top-0 right-0 bg-yellow-100 border-l-4 border-yellow-400 p-4 m-4 rounded-lg shadow-lg z-50 max-w-md">
         <h3 class="font-bold text-yellow-800 mb-2">🔍 Simple Debug Panel</h3>
         <div class="text-xs text-yellow-700 space-y-1">
             <div>✅ PHP Working</div>
             <div>✅ Database Connected</div>
             <div>✅ Session Active</div>
             <div>User: <?php echo htmlspecialchars($user_name); ?></div>
             <div>Role: <?php echo ucfirst($user_role); ?></div>
             <div>Today: <?php echo date('Y-m-d'); ?></div>
             <div>Kunjungan: <?php echo $total_kunjungan ?? 0; ?></div>
             <div>Berhasil: <?php echo $donasi_berhasil ?? 0; ?></div>
             <div>Total: Rp <?php echo number_format($total_donasi ?? 0, 0, ',', '.'); ?></div>
         </div>
     </div>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">Fundraising System (SIMPLE DEBUG)</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo ucfirst($user_role); ?>
                    </span>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <?php include $user_role === 'admin' ? 'sidebar-admin.php' : 'sidebar-user.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Dashboard Header -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Dashboard (SIMPLE DEBUG)</h2>
                <p class="text-gray-600">Simple debug mode - direct database queries</p>
            </div>

            <!-- Error Display -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
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

                <div class="bg-white rounded-lg shadow p-6">
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

                <div class="bg-white rounded-lg shadow p-6">
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

                <div class="bg-white rounded-lg shadow p-6">
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

            <!-- Recent Activities -->
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

            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Go to Normal Dashboard</a>
                    <a href="test_api_direct.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Test API Direct</a>
                    <a href="check_data.php" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Check Database Data</a>
                    <a href="insert_today_data.php" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">Insert Today's Data</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple JavaScript -->
    <script>
        // Very simple JavaScript - no complex operations
        console.log('Simple debug dashboard loaded');
        
        // Add click handlers for mobile menu if needed
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded successfully');
            
            // Simple mobile menu toggle
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
</body>
</html>
