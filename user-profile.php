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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $hp = trim($_POST['hp']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($hp)) {
            throw new Exception("Nama, email, dan nomor HP harus diisi!");
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid!");
        }
        
        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Email sudah digunakan oleh user lain!");
        }
        
        // Check if HP already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE hp = ? AND id != ?");
        $stmt->execute([$hp, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Nomor HP sudah digunakan oleh user lain!");
        }
        
        // Handle password change
        if (!empty($current_password)) {
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Password saat ini salah!");
            }
            
            // Validate new password
            if (empty($new_password)) {
                throw new Exception("Password baru harus diisi!");
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception("Password baru minimal 6 karakter!");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok!");
            }
            
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            $password_hash = $user['password']; // Keep existing password
        }
        
        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, hp = ?, password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $hp, $password_hash, $user_id]);
        
        // Update session
        $_SESSION['user_name'] = $name;
        
        $success_message = "Profil berhasil diperbarui!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get user's performance stats
try {
    $today = date('Y-m-d');
    $current_month = date('Y-m');
    
    // Today's stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, $today]);
    $kunjungan_hari_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $berhasil_hari_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $donasi_hari_ini = $stmt->fetchColumn();
    
    // This month's stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$user_id, $current_month]);
    $kunjungan_bulan_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $current_month]);
    $berhasil_bulan_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $current_month]);
    $donasi_bulan_ini = $stmt->fetchColumn();
    
    // All time stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ?");
    $stmt->execute([$user_id]);
    $total_kunjungan = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user_id]);
    $total_berhasil = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user_id]);
    $total_donasi = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
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
    <title>Profil Saya - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f9fafb !important;
        }
        
        /* Fixed Header */
        header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000 !important;
            background: white !important;
            border-bottom: 1px solid #e5e7eb !important;
            height: 64px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed !important;
            top: 64px !important;
            left: 0 !important;
            width: 16rem !important;
            height: calc(100vh - 64px) !important;
            z-index: 500 !important;
            background: white !important;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1) !important;
            overflow-y: auto !important;
            transition: transform 0.3s ease-in-out !important;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: 16rem !important;
            margin-top: 64px !important;
            padding: 2rem !important;
            min-height: calc(100vh - 64px) !important;
            width: calc(100% - 16rem) !important;
            background-color: #f9fafb !important;
            box-sizing: border-box !important;
        }
        
        /* Responsive fixes */
        @media (max-width: 640px) {
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
                width: 100% !important;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .lg\:col-span-2 {
                grid-column: span 1 !important;
            }
            
            .lg\:col-span-3 {
                grid-template-columns: 1fr !important;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
        }
        
        /* Ensure all elements are responsive */
        * {
            box-sizing: border-box !important;
        }
        
        /* Remove any default margins/padding */
        .bg-gray-50 {
            background-color: #f9fafb !important;
        }
        
        /* Fix content spacing */
        .space-y-6 > * + * {
            margin-top: 1.5rem !important;
        }
        
        .space-y-4 > * + * {
            margin-top: 1rem !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'layout-header.php'; ?>
        <!-- Header Section -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
            <p class="text-gray-600">Kelola informasi profil dan pantau performa fundraising Anda</p>
        </div>
        
        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Profile Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Informasi Profil</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nomor HP</label>
                                    <input type="text" name="hp" value="<?php echo htmlspecialchars($user['hp']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <input type="text" value="<?php echo ucfirst($user['status']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Harian</label>
                                    <input type="text" value="<?php echo $user['target']; ?> kunjungan" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500">
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Ubah Password</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                                        <input type="password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kosongkan jika tidak ingin mengubah">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Minimal 6 karakter">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                                        <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ulangi password baru">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Performance Stats -->
            <div class="space-y-6">
                <!-- Today's Performance -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performa Hari Ini</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Kunjungan:</span>
                            <span class="font-semibold"><?php echo $kunjungan_hari_ini; ?>/<?php echo $user['target']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <?php 
                            $progress = $user['target'] > 0 ? min(100, ($kunjungan_hari_ini / $user['target']) * 100) : 0;
                            ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Berhasil:</span>
                            <span class="font-semibold text-green-600"><?php echo $berhasil_hari_ini; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Donasi:</span>
                            <span class="font-semibold">Rp <?php echo number_format($donasi_hari_ini, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- This Month's Performance -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performa Bulan Ini</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Kunjungan:</span>
                            <span class="font-semibold"><?php echo $kunjungan_bulan_ini; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Berhasil:</span>
                            <span class="font-semibold text-green-600"><?php echo $berhasil_bulan_ini; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Donasi:</span>
                            <span class="font-semibold">Rp <?php echo number_format($donasi_bulan_ini, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Rata-rata per Kunjungan:</span>
                            <span class="font-semibold">
                                <?php 
                                if ($kunjungan_bulan_ini > 0) {
                                    echo 'Rp ' . number_format($donasi_bulan_ini / $kunjungan_bulan_ini, 0, ',', '.');
                                } else {
                                    echo 'Rp 0';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- All Time Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik Keseluruhan</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Kunjungan:</span>
                            <span class="font-semibold"><?php echo $total_kunjungan; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Berhasil:</span>
                            <span class="font-semibold text-green-600"><?php echo $total_berhasil; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Donasi:</span>
                            <span class="font-semibold">Rp <?php echo number_format($total_donasi, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Success Rate:</span>
                            <span class="font-semibold">
                                <?php 
                                if ($total_kunjungan > 0) {
                                    echo round(($total_berhasil / $total_kunjungan) * 100, 1) . '%';
                                } else {
                                    echo '0%';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Account Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Akun</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Bergabung Sejak:</span>
                            <span class="font-semibold"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Terakhir Update:</span>
                            <span class="font-semibold"><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Terakhir Aktif:</span>
                            <span class="font-semibold">
                                <?php 
                                if ($user['last_active']) {
                                    echo date('d/m/Y H:i', strtotime($user['last_active']));
                                } else {
                                    echo 'Belum ada';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    </main>
    
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
    </script>
</body>
</html>