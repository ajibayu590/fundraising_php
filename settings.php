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

// Check if user has admin role
if ($user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Include app settings
require_once 'app_settings.php';
require_once 'logo_manager.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        
        if (isset($_POST['update_settings'])) {
            $new_version = trim($_POST['version']);
            $new_copyright = trim($_POST['copyright']);
            $new_company = trim($_POST['company']);
            $new_description = trim($_POST['description']);
            
            // Update settings
            update_app_setting('version', $new_version);
            update_app_setting('copyright', $new_copyright);
            update_app_setting('company', $new_company);
            update_app_setting('description', $new_description);
            
            $success_message = "Pengaturan berhasil diupdate";
            header("Location: settings.php?success=" . urlencode($success_message));
            exit;
        }
        
        // Handle logo upload
        if (isset($_POST['upload_logo']) && isset($_FILES['logo'])) {
            $result = upload_logo($_FILES['logo']);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            header("Location: settings.php?success=" . urlencode($success_message ?? '') . "&error=" . urlencode($error_message ?? ''));
            exit;
        }
        
        // Handle logo deletion
        if (isset($_POST['delete_logo'])) {
            $result = delete_logo();
            $success_message = $result['message'];
            header("Location: settings.php?success=" . urlencode($success_message));
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Determine sidebar
$sidebarFile = 'sidebar-admin.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Fundraising System</title>
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
                    <?php
                    require_once 'logo_manager.php';
                    echo get_logo_html('w-10 h-10', 'mr-3');
                    ?>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Settings</h1>
                        <p class="text-sm text-gray-600">System Settings</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Admin</span>
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
                    <a href="dashboard.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="users.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        User Management
                    </a>
                    
                    <a href="analytics-fixed.php" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Analytics
                    </a>
                </div>
            </div>

            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Application Settings</h2>
                <p class="text-gray-600 mt-2">Kelola pengaturan aplikasi</p>
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

            <!-- Logo Management Section -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🖼️ Logo Management</h3>
                
                <!-- Current Logo Display -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Current Logo</h4>
                    <div class="flex items-center space-x-4">
                        <?php echo get_logo_html('w-16 h-16', 'rounded-lg'); ?>
                        <div>
                            <p class="text-sm text-gray-600">
                                <?php echo get_logo_path() ? 'Logo uploaded' : 'No logo uploaded (using default)'; ?>
                            </p>
                            <?php if (get_logo_path()): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete the current logo?')">
                                <?php echo get_csrf_token_field(); ?>
                                <button type="submit" name="delete_logo" class="text-red-600 hover:text-red-800 text-sm">
                                    Delete Logo
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Logo Upload Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php echo get_csrf_token_field(); ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Logo</label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Format: PNG, JPEG. Max size: 2MB</p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="upload_logo" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Upload Logo
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Application Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚙️ Pengaturan Aplikasi</h3>
                    
                    <form method="POST" class="space-y-4">
                        <?php echo get_csrf_token_field(); ?>
                        <input type="hidden" name="update_settings" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perusahaan</label>
                            <input type="text" name="company" value="<?php echo htmlspecialchars(get_app_setting('company')); ?>" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Aplikasi</label>
                            <textarea name="description" rows="3" required 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars(get_app_setting('description')); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Versi Aplikasi</label>
                            <input type="text" name="version" value="<?php echo htmlspecialchars(get_app_setting('version')); ?>" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Format: x.x.x (contoh: 1.0.0)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Copyright Text</label>
                            <input type="text" name="copyright" value="<?php echo htmlspecialchars(get_app_setting('copyright')); ?>" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Contoh: © 2024 Fundraising System. All rights reserved.</p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Update Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Current Settings Preview -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">👁️ Preview Settings</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">Nama Perusahaan</p>
                                <p class="font-medium"><?php echo htmlspecialchars(get_app_setting('company')); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Deskripsi</p>
                                <p class="font-medium"><?php echo htmlspecialchars(get_app_setting('description')); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Versi</p>
                                <p class="font-medium"><?php echo htmlspecialchars(get_app_setting('version')); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Copyright</p>
                                <p class="font-medium"><?php echo htmlspecialchars(get_app_setting('copyright')); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Informasi Sistem</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">PHP Version</p>
                                <p class="font-medium"><?php echo PHP_VERSION; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Database</p>
                                <p class="font-medium">MySQL</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Server</p>
                                <p class="font-medium"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Last Updated</p>
                                <p class="font-medium"><?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
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