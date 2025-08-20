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
    header("Location: donatur.php");
    exit;
}

// Determine sidebar
$sidebarFile = 'sidebar-user.php';

// Handle form submissions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        
        if (isset($_POST['add_donatur'])) {
            $nama = trim($_POST['nama']);
            $hp = trim($_POST['hp']);
            $alamat = trim($_POST['alamat']);
            
            // Check if HP already exists
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ?");
            $stmt->execute([$hp]);
            if ($stmt->fetch()) {
                $error_message = "Nomor HP sudah terdaftar";
            } else {
                $stmt = $pdo->prepare("INSERT INTO donatur (nama, hp, alamat) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $hp, $alamat]);
                
                $success_message = "Donatur berhasil ditambahkan";
                header("Location: donatur-user.php?success=" . urlencode($success_message));
                exit;
            }
        }
        
        if (isset($_POST['edit_donatur'])) {
            $donatur_id = $_POST['donatur_id'];
            $nama = trim($_POST['nama']);
            $hp = trim($_POST['hp']);
            $alamat = trim($_POST['alamat']);
            
            // Check if HP already exists (excluding current donatur)
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ? AND id != ?");
            $stmt->execute([$hp, $donatur_id]);
            if ($stmt->fetch()) {
                $error_message = "Nomor HP sudah terdaftar";
            } else {
                $stmt = $pdo->prepare("UPDATE donatur SET nama = ?, hp = ?, alamat = ? WHERE id = ?");
                $stmt->execute([$nama, $hp, $alamat, $donatur_id]);
                
                $success_message = "Donatur berhasil diupdate";
                header("Location: donatur-user.php?success=" . urlencode($success_message));
                exit;
            }
        }
        
        if (isset($_POST['delete_donatur'])) {
            $donatur_id = $_POST['donatur_id'];
            
            // Check if donatur has kunjungan records
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE donatur_id = ?");
            $stmt->execute([$donatur_id]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Tidak dapat menghapus donatur yang memiliki riwayat kunjungan";
            } else {
                $stmt = $pdo->prepare("DELETE FROM donatur WHERE id = ?");
                $stmt->execute([$donatur_id]);
                
                $success_message = "Donatur berhasil dihapus";
                header("Location: donatur-user.php?success=" . urlencode($success_message));
                exit;
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle export requests BEFORE any output
if (!empty($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'donatur_' . $user['name'] . '_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Get all donatur data for export
    $stmt = $pdo->prepare("SELECT * FROM donatur ORDER BY nama");
    $stmt->execute();
    $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nama</th><th>HP</th><th>Alamat</th><th>Tanggal Dibuat</th></tr>";
    
    foreach ($exportData as $row) {
        $id = htmlspecialchars($row['id']);
        $nama = htmlspecialchars($row['nama']);
        $hp = htmlspecialchars($row['hp']);
        $alamat = htmlspecialchars($row['alamat']);
        $tanggal = date('d/m/Y H:i', strtotime($row['created_at']));
        
        echo "<tr><td>$id</td><td>$nama</td><td>$hp</td><td>$alamat</td><td>$tanggal</td></tr>";
    }
    echo "</table>";
    exit;
}

// Load page data
try {
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    
    // Build query for donatur data
    $where_conditions = ["1=1"];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(nama LIKE ? OR hp LIKE ? OR alamat LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get donatur data
    $stmt = $pdo->prepare("
        SELECT * FROM donatur 
        WHERE $where_clause
        ORDER BY nama
    ");
    $stmt->execute($params);
    $donaturData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donatur");
    $stmt->execute();
    $total_donatur = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT donatur_id) FROM kunjungan WHERE fundraiser_id = ?");
    $stmt->execute([$user['id']]);
    $donatur_visited = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT donatur_id) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user['id']]);
    $donatur_success = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $donaturData = [];
    $total_donatur = 0;
    $donatur_visited = 0;
    $donatur_success = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donatur - Fundraising System</title>
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
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Donatur</h1>
                        <p class="text-sm text-gray-600">My Donors</p>
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
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Data Donatur</h2>
                <p class="text-gray-600 mt-2">Kelola data donatur untuk kegiatan fundraising</p>
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Donatur</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $total_donatur; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Sudah Dikunjungi</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $donatur_visited; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $donatur_success; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Panel -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                    <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
                        <button onclick="showAddModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Donatur
                        </button>
                        
                        <button onclick="exportToExcel()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Excel
                        </button>
                    </div>
                    
                    <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
                        <input type="text" id="searchInput" placeholder="Cari donatur..." 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Donatur Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Donatur</h3>
                    <p class="text-sm text-gray-600">Data lengkap semua donatur</p>
                </div>
                
                <?php if (!empty($donaturData)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($donaturData as $donatur): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($donatur['nama']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($donatur['hp']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($donatur['alamat']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    // Check if this donatur has been visited by current user
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE donatur_id = ? AND fundraiser_id = ?");
                                    $stmt->execute([$donatur['id'], $user['id']]);
                                    $visited = $stmt->fetchColumn() > 0;
                                    
                                    if ($visited) {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE donatur_id = ? AND fundraiser_id = ? AND status = 'berhasil'");
                                        $stmt->execute([$donatur['id'], $user['id']]);
                                        $success = $stmt->fetchColumn() > 0;
                                        
                                        if ($success) {
                                            echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Berhasil</span>';
                                        } else {
                                            echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Sudah Dikunjungi</span>';
                                        }
                                    } else {
                                        echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Belum Dikunjungi</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editDonatur(<?php echo htmlspecialchars(json_encode($donatur)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button onclick="deleteDonatur(<?php echo $donatur['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <div class="text-6xl mb-4">👥</div>
                    <h3 class="text-lg font-medium mb-2">Belum ada donatur</h3>
                    <p class="text-sm">Mulai dengan menambahkan donatur baru</p>
                    <button onclick="showAddModal()" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Tambah Donatur
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="donaturModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Donatur</h3>
                <form id="donaturForm" method="POST">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" id="donatur_id" name="donatur_id">
                    <input type="hidden" id="form_action" name="add_donatur">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nomor HP</label>
                        <input type="tel" id="hp" name="hp" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus donatur ini?</p>
                <form id="deleteForm" method="POST">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" id="delete_donatur_id" name="donatur_id">
                    <input type="hidden" name="delete_donatur" value="1">
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Hapus
                        </button>
                    </div>
                </form>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const search = this.value;
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            window.location.href = 'donatur-user.php?' + params.toString();
        });

        // Modal functions
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Donatur';
            document.getElementById('form_action').name = 'add_donatur';
            document.getElementById('donaturForm').reset();
            document.getElementById('donaturModal').classList.remove('hidden');
        }

        function editDonatur(donatur) {
            document.getElementById('modalTitle').textContent = 'Edit Donatur';
            document.getElementById('form_action').name = 'edit_donatur';
            document.getElementById('donatur_id').value = donatur.id;
            document.getElementById('nama').value = donatur.nama;
            document.getElementById('hp').value = donatur.hp;
            document.getElementById('alamat').value = donatur.alamat;
            document.getElementById('donaturModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('donaturModal').classList.add('hidden');
        }

        function deleteDonatur(id) {
            document.getElementById('delete_donatur_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function exportToExcel() {
            try {
                const currentUrl = window.location.href;
                const exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=excel';

                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'donatur_data.xls';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Show notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                notification.textContent = 'Export Excel berhasil dimulai';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 3000);
            } catch (error) {
                console.error('Export error:', error);
                alert('Gagal export Excel');
            }
        }
    </script>
    
    <script src="js/icon-fixes.js"></script>
</body>
</html>