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

// Handle form submissions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $hp = trim($_POST['hp']);
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate current password if changing password
            if (!empty($new_password)) {
                if (!password_verify($current_password, $user['password'])) {
                    $error_message = "Password saat ini salah";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "Password baru tidak cocok";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "Password minimal 6 karakter";
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, hp = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hp, $hashed_password, $user['id']]);
                    
                    $success_message = "Profile berhasil diupdate";
                    header("Location: profile.php?success=" . urlencode($success_message));
                    exit;
                }
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, hp = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hp, $user['id']]);
                
                $success_message = "Profile berhasil diupdate";
                header("Location: profile.php?success=" . urlencode($success_message));
                exit;
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get user statistics
try {
    $user_id = $user['id'];
    
    // Total kunjungan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ?");
    $stmt->execute([$user_id]);
    $total_kunjungan = $stmt->fetchColumn();
    
    // Total donasi berhasil
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user_id]);
    $total_berhasil = $stmt->fetchColumn();
    
    // Total nominal donasi
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user_id]);
    $total_nominal = $stmt->fetchColumn();
    
    // Average per day (last 30 days)
    $stmt = $pdo->prepare("
        SELECT AVG(daily_count) as avg_per_day 
        FROM (
            SELECT DATE(created_at) as visit_date, COUNT(*) as daily_count
            FROM kunjungan 
            WHERE fundraiser_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
        ) as daily_stats
    ");
    $stmt->execute([$user_id]);
    $avg_per_day = round($stmt->fetchColumn(), 1);
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT k.*, d.nama as donatur_name
        FROM kunjungan k 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE k.fundraiser_id = ?
        ORDER BY k.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $total_kunjungan = 0;
    $total_berhasil = 0;
    $total_nominal = 0;
    $avg_per_day = 0;
    $recent_activities = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Saya - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Profile Saya</h1>
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
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Profile Saya</h2>
                <p class="text-gray-600 mt-2">Kelola informasi profile dan password Anda</p>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📝 Edit Profile</h3>
                        
                        <form method="POST" class="space-y-4">
                            <?php echo get_csrf_token_field(); ?>
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nomor HP</label>
                                <input type="tel" name="hp" value="<?php echo htmlspecialchars($user['hp']); ?>" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div class="border-t pt-4">
                                <h4 class="text-md font-medium text-gray-900 mb-4">🔐 Ganti Password (Opsional)</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                                        <input type="password" name="current_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="new_password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <p class="text-sm text-gray-500 mt-2">* Kosongkan field password jika tidak ingin mengubah password</p>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics Sidebar -->
                <div class="space-y-6">
                    <!-- User Info Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">👤 Informasi User</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">Role</p>
                                <p class="font-medium"><?php echo ucfirst($user['role']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Target Harian</p>
                                <p class="font-medium"><?php echo $user['target'] ?? 8; ?> kunjungan</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Bergabung Sejak</p>
                                <p class="font-medium"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Stats -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Statistik Performa</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Kunjungan</span>
                                <span class="font-semibold"><?php echo $total_kunjungan; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Donasi Berhasil</span>
                                <span class="font-semibold"><?php echo $total_berhasil; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Donasi</span>
                                <span class="font-semibold">Rp <?php echo number_format($total_nominal, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Rata-rata/Hari</span>
                                <span class="font-semibold"><?php echo $avg_per_day; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📝 Aktivitas Terbaru</h3>
                        <?php if (!empty($recent_activities)): ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="border-l-4 border-blue-500 pl-3">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($activity['donatur_name'] ?? 'Unknown'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></p>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                    echo $activity['status'] === 'berhasil' ? 'bg-green-100 text-green-800' : 
                                         ($activity['status'] === 'follow-up' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                ?>">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-gray-500">Belum ada aktivitas</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="dashboard-user.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="kunjungan-user.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Kunjungan
                    </a>
                    
                    <a href="donatur-user.php" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Donatur
                    </a>
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