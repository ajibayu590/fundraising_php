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

// Tentukan sidebar berdasarkan role user
$sidebarFile = ($user['role'] == 'admin') ? 'sidebar-admin.php' : 'sidebar-user.php';

// Handle individual target updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'admin') {
    try {
        check_csrf();
        
        if (isset($_POST['update_individual_target'])) {
            $userId = (int)$_POST['user_id'];
            $newTarget = (int)$_POST['target'];
            
            if ($newTarget > 0 && $newTarget <= 50) {
                $stmt = $pdo->prepare("UPDATE users SET target = ? WHERE id = ? AND role = 'user'");
                $stmt->execute([$newTarget, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = "Target berhasil diupdate";
                } else {
                    $error_message = "Gagal update target";
                }
            } else {
                $error_message = "Target harus antara 1-50 kunjungan per hari";
            }
        }
        
        if (isset($_POST['bulk_update_target'])) {
            $newTarget = (int)$_POST['bulk_target'];
            
            if ($newTarget > 0 && $newTarget <= 50) {
                $stmt = $pdo->prepare("UPDATE users SET target = ? WHERE role = 'user'");
                $stmt->execute([$newTarget]);
                $affected = $stmt->rowCount();
                
                $success_message = "Target berhasil diupdate untuk $affected fundraiser";
            } else {
                $error_message = "Target harus antara 1-50 kunjungan per hari";
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Load fundraiser data with performance
try {
    $searchQuery = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    
    $where = ["u.role = 'user'"];
    $params = [];
    
    if (!empty($searchQuery)) {
        $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
        $like = "%$searchQuery%";
        $params[] = $like;
        $params[] = $like;
    }
    
    if (!empty($statusFilter)) {
        $where[] = "u.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.hp,
            u.target,
            u.status,
            u.created_at,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
            COALESCE(COUNT(CASE WHEN k.status = 'berhasil' THEN 1 END), 0) as sukses_kunjungan
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE $whereClause
        GROUP BY u.id, u.name, u.email, u.hp, u.target, u.status, u.created_at
        ORDER BY u.name ASC
    ");
    $stmt->execute($params);
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $fundraisers = [];
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
    <title>Kelola Target Fundraiser</title>
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Kelola Target Fundraiser</h1>
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
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Kelola Target Fundraiser</h2>
                <p class="text-gray-600 mt-2">Atur target kunjungan harian untuk setiap fundraiser</p>
            </div>

            <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Bulk Update Target -->
            <?php if ($user['role'] === 'admin'): ?>
            <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Target Massal</h3>
                <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                    <?php echo get_csrf_token_field(); ?>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Baru untuk Semua Fundraiser</label>
                        <input type="number" name="bulk_target" min="1" max="50" value="8" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Target kunjungan per hari (1-50)</p>
                    </div>
                    <button type="submit" name="bulk_update_target" 
                            onclick="return confirm('Update target untuk semua fundraiser?')"
                            class="px-6 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Update Semua Target
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Fundraiser</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" 
                               placeholder="Nama atau email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?php echo ($statusFilter=='aktif')?'selected':''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($statusFilter=='nonaktif')?'selected':''; ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Filter</button>
                        <a href="fundraiser-target.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Fundraiser List -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Fundraiser</h3>
                    <p class="text-sm text-gray-600"><?php echo count($fundraisers); ?> fundraiser ditemukan</p>
                </div>
                
                <?php if (!empty($fundraisers)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Saat Ini</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress Hari Ini</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($fundraisers as $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <span class="text-sm font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($f['email']); ?></div>
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
                                        <div class="w-32 bg-gray-200 rounded-full h-2 mt-1 mx-auto">
                                            <div class="h-2 rounded-full <?php echo $color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $percent; ?>%</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-900"><?php echo number_format($f['total_kunjungan']); ?> kunjungan</div>
                                    <div class="text-sm font-medium text-green-600">Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                                    <?php $successRate = $f['total_kunjungan'] > 0 ? round(($f['sukses_kunjungan'] / $f['total_kunjungan']) * 100, 1) : 0; ?>
                                    <div class="text-xs text-gray-500"><?php echo $successRate; ?>% success rate</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                                    ?>">
                                        <?php echo ucfirst($f['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <button onclick="showEditTargetModal(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars($f['name']); ?>', <?php echo $f['target']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit Target</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-lg font-medium mb-2">Tidak ada data fundraiser</h3>
                    <p class="text-sm">Tambahkan fundraiser baru atau coba filter yang berbeda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Target Modal -->
    <div id="edit-target-modal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Edit Target Fundraiser</h3>
                <button onclick="hideEditTargetModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="edit-target-form" method="POST">
                <?php echo get_csrf_token_field(); ?>
                <input type="hidden" name="update_individual_target" value="1">
                <input type="hidden" id="edit-user-id" name="user_id" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fundraiser</label>
                        <div id="edit-fundraiser-name" class="px-3 py-2 bg-gray-100 rounded-lg text-gray-900"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Kunjungan Harian *</label>
                        <input type="number" id="edit-target" name="target" min="1" max="50" value="8" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <p class="mt-1 text-xs text-gray-500">Target kunjungan per hari (1-50)</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideEditTargetModal()" 
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Update Target
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showEditTargetModal(userId, userName, currentTarget) {
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-fundraiser-name').textContent = userName;
            document.getElementById('edit-target').value = currentTarget;
            document.getElementById('edit-target-modal').classList.remove('hidden');
            document.getElementById('edit-target-modal').classList.add('flex');
        }
        
        function hideEditTargetModal() {
            document.getElementById('edit-target-modal').classList.add('hidden');
            document.getElementById('edit-target-modal').classList.remove('flex');
        }
        
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