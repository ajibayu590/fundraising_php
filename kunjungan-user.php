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
    header("Location: kunjungan.php");
    exit;
}

// Determine sidebar
$sidebarFile = 'sidebar-user.php';

// Handle form submissions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        
        if (isset($_POST['add_kunjungan'])) {
            $donatur_id = $_POST['donatur_id'];
            $status = $_POST['status'];
            $nominal = $status === 'berhasil' ? (int)$_POST['nominal'] : 0;
            $catatan = $_POST['catatan'];
            
            // Handle file upload for foto
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/kunjungan/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $foto_filename = 'kunjungan_' . $user['id'] . '_' . date('Ymd_His') . '.' . $file_extension;
                    $foto_path = $upload_dir . $foto_filename;
                    
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                        // File uploaded successfully
                    } else {
                        $error_message = "Gagal mengupload foto";
                    }
                } else {
                    $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF";
                }
            }
            
            // Handle GPS data
            $latitude = null;
            $longitude = null;
            $location_address = null;
            
            if (isset($_POST['latitude']) && isset($_POST['longitude']) && 
                !empty($_POST['latitude']) && !empty($_POST['longitude'])) {
                $latitude = (float)$_POST['latitude'];
                $longitude = (float)$_POST['longitude'];
                $location_address = $_POST['location_address'] ?? null;
                
                // Validate GPS coordinates
                if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                    $error_message = "Koordinat GPS tidak valid";
                }
            }
            
            if (!isset($error_message)) {
                $stmt = $pdo->prepare("INSERT INTO kunjungan (fundraiser_id, donatur_id, status, nominal, catatan, foto, latitude, longitude, location_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $donatur_id, $status, $nominal, $catatan, $foto_path, $latitude, $longitude, $location_address]);
                
                $success_message = "Kunjungan berhasil ditambahkan";
                header("Location: kunjungan-user.php?success=" . urlencode($success_message));
                exit;
            }
        }
        
        if (isset($_POST['edit_kunjungan'])) {
            $kunjungan_id = $_POST['kunjungan_id'];
            $donatur_id = $_POST['donatur_id'];
            $status = $_POST['status'];
            $nominal = $status === 'berhasil' ? (int)$_POST['nominal'] : 0;
            $catatan = $_POST['catatan'];
            
            // Verify kunjungan belongs to this user
            $stmt = $pdo->prepare("SELECT id FROM kunjungan WHERE id = ? AND fundraiser_id = ?");
            $stmt->execute([$kunjungan_id, $user['id']]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE kunjungan SET donatur_id = ?, status = ?, nominal = ?, catatan = ? WHERE id = ? AND fundraiser_id = ?");
                $stmt->execute([$donatur_id, $status, $nominal, $catatan, $kunjungan_id, $user['id']]);
                
                $success_message = "Kunjungan berhasil diupdate";
                header("Location: kunjungan-user.php?success=" . urlencode($success_message));
                exit;
            } else {
                $error_message = "Kunjungan tidak ditemukan atau tidak memiliki akses";
            }
        }
        
        if (isset($_POST['delete_kunjungan'])) {
            $kunjungan_id = $_POST['kunjungan_id'];
            
            // Verify kunjungan belongs to this user
            $stmt = $pdo->prepare("SELECT id FROM kunjungan WHERE id = ? AND fundraiser_id = ?");
            $stmt->execute([$kunjungan_id, $user['id']]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM kunjungan WHERE id = ? AND fundraiser_id = ?");
                $stmt->execute([$kunjungan_id, $user['id']]);
                
                $success_message = "Kunjungan berhasil dihapus";
                header("Location: kunjungan-user.php?success=" . urlencode($success_message));
                exit;
            } else {
                $error_message = "Kunjungan tidak ditemukan atau tidak memiliki akses";
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle export requests BEFORE any output
if (!empty($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'kunjungan_' . $user['name'] . '_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Get user's kunjungan data for export
    $stmt = $pdo->prepare("
        SELECT k.*, d.nama as donatur_name, d.hp as donatur_hp, d.alamat
        FROM kunjungan k 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE k.fundraiser_id = ?
        ORDER BY k.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Donatur</th><th>HP Donatur</th><th>Alamat</th><th>Status</th><th>Nominal</th><th>Tanggal</th><th>Foto</th><th>Latitude</th><th>Longitude</th><th>Lokasi</th><th>Catatan</th></tr>";
    
    foreach ($exportData as $row) {
        $id = htmlspecialchars($row['id']);
        $donatur = htmlspecialchars($row['donatur_name'] ?? 'Unknown');
        $hp = htmlspecialchars($row['donatur_hp'] ?? '');
        $alamat = htmlspecialchars($row['alamat'] ?? '');
        $status = htmlspecialchars($row['status'] ?? '');
        $nominal = $row['status'] == 'berhasil' ? number_format($row['nominal'] ?? 0, 0, ',', '.') : '-';
        $tanggal = date('d/m/Y H:i', strtotime($row['created_at']));
        $foto = $row['foto'] ? 'Ada' : 'Tidak ada';
        $latitude = $row['latitude'] ?? '-';
        $longitude = $row['longitude'] ?? '-';
        $lokasi = htmlspecialchars($row['location_address'] ?? '-');
        $catatan = htmlspecialchars($row['catatan'] ?? '');
        
        echo "<tr><td>$id</td><td>$donatur</td><td>$hp</td><td>$alamat</td><td>$status</td><td>$nominal</td><td>$tanggal</td><td>$foto</td><td>$latitude</td><td>$longitude</td><td>$lokasi</td><td>$catatan</td></tr>";
    }
    echo "</table>";
    exit;
}

// Load page data
try {
    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query for user's kunjungan data
    $where_conditions = ["k.fundraiser_id = ?"];
    $params = [$user['id']];
    
    if ($status_filter) {
        $where_conditions[] = "k.status = ?";
        $params[] = $status_filter;
    }
    
    if ($date_filter) {
        $where_conditions[] = "DATE(k.created_at) = ?";
        $params[] = $date_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(d.nama LIKE ? OR d.hp LIKE ? OR k.catatan LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get kunjungan data
    $stmt = $pdo->prepare("
        SELECT k.*, d.nama as donatur_name, d.hp as donatur_hp, d.alamat
        FROM kunjungan k 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE $where_clause
        ORDER BY k.created_at DESC
    ");
    $stmt->execute($params);
    $kunjunganData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get donatur list for forms
    $stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur ORDER BY nama");
    $stmt->execute();
    $donaturList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ?");
    $stmt->execute([$user['id']]);
    $total_kunjungan = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user['id']]);
    $total_berhasil = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND status = 'berhasil'");
    $stmt->execute([$user['id']]);
    $total_donasi = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $kunjunganData = [];
    $donaturList = [];
    $total_kunjungan = 0;
    $total_berhasil = 0;
    $total_donasi = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunjungan Saya - Fundraising System</title>
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Kunjungan Saya</h1>
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
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Kunjungan Saya</h2>
                <p class="text-gray-600 mt-2">Kelola kunjungan dan donasi Anda</p>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Kunjungan</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $total_kunjungan; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $total_berhasil; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Donasi</p>
                            <p class="text-2xl font-semibold text-gray-900">Rp <?php echo number_format($total_donasi, 0, ',', '.'); ?></p>
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
                            Tambah Kunjungan
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
                        <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="berhasil">Berhasil</option>
                            <option value="tidak-berhasil">Tidak Berhasil</option>
                            <option value="follow-up">Follow Up</option>
                        </select>
                        <input type="date" id="dateFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Kunjungan Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Kunjungan</h3>
                    <p class="text-sm text-gray-600">Kunjungan dan donasi yang Anda kelola</p>
                </div>
                
                <?php if (!empty($kunjunganData)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donatur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($kunjunganData as $kunjungan): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($kunjungan['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($kunjungan['donatur_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($kunjungan['donatur_hp'] ?? ''); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $kunjungan['status'] === 'berhasil' ? 'bg-green-100 text-green-800' : 
                                             ($kunjungan['status'] === 'follow-up' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                    ?>">
                                        <?php echo ucfirst($kunjungan['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($kunjungan['status'] === 'berhasil' && $kunjungan['nominal']): ?>
                                        Rp <?php echo number_format($kunjungan['nominal'], 0, ',', '.'); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($kunjungan['foto']): ?>
                                        <a href="<?php echo htmlspecialchars($kunjungan['foto']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Lihat Foto
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($kunjungan['latitude'] && $kunjungan['longitude']): ?>
                                        <a href="https://maps.google.com/?q=<?php echo $kunjungan['latitude']; ?>,<?php echo $kunjungan['longitude']; ?>" target="_blank" class="text-green-600 hover:text-green-800">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Lihat Lokasi
                                        </a>
                                        <?php if ($kunjungan['location_address']): ?>
                                            <br><span class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($kunjungan['location_address'], 0, 30)) . (strlen($kunjungan['location_address']) > 30 ? '...' : ''); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($kunjungan['catatan'] ?? ''); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editKunjungan(<?php echo htmlspecialchars(json_encode($kunjungan)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button onclick="deleteKunjungan(<?php echo $kunjungan['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <div class="text-6xl mb-4">📝</div>
                    <h3 class="text-lg font-medium mb-2">Belum ada kunjungan</h3>
                    <p class="text-sm">Mulai dengan menambahkan kunjungan baru</p>
                    <button onclick="showAddModal()" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Tambah Kunjungan
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="kunjunganModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Kunjungan</h3>
                <form id="kunjunganForm" method="POST" enctype="multipart/form-data">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" id="kunjungan_id" name="kunjungan_id">
                    <input type="hidden" id="form_action" name="add_kunjungan">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Donatur <span class="text-red-500">*</span></label>
                        <select id="donatur_id" name="donatur_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Donatur</option>
                            <?php foreach ($donaturList as $donatur): ?>
                            <option value="<?php echo $donatur['id']; ?>">
                                <?php echo htmlspecialchars($donatur['nama'] . ' (' . $donatur['hp'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required onchange="toggleNominalField()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Status</option>
                            <option value="berhasil">Berhasil</option>
                            <option value="tidak-berhasil">Tidak Berhasil</option>
                            <option value="follow-up">Follow Up</option>
                        </select>
                    </div>
                    
                    <div class="mb-4" id="nominalField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nominal Donasi <span class="text-red-500">*</span></label>
                        <input type="number" id="nominal" name="nominal" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kunjungan <span class="text-red-500">*</span></label>
                        <input type="file" id="foto" name="foto" accept="image/*" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB</p>
                    </div>
                    
                    <!-- GPS Location Section -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi GPS <span class="text-red-500">*</span></label>
                        <div class="space-y-3">
                            <div class="flex space-x-2">
                                <button type="button" id="getLocationBtn" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Ambil Lokasi GPS
                                </button>
                                <span id="locationStatus" class="text-sm text-gray-500"></span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Latitude</label>
                                    <input type="number" id="latitude" name="latitude" step="any" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                           placeholder="Contoh: -6.2088">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Longitude</label>
                                    <input type="number" id="longitude" name="longitude" step="any" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                           placeholder="Contoh: 106.8456">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Alamat Lokasi</label>
                                <input type="text" id="location_address" name="location_address" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                       placeholder="Alamat lengkap lokasi kunjungan">
                            </div>
                            
                            <div id="mapPreview" class="hidden">
                                <label class="block text-xs text-gray-600 mb-1">Preview Lokasi</label>
                                <div id="map" class="w-full h-32 bg-gray-100 rounded-lg border"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea id="catatan" name="catatan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
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
                <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus kunjungan ini?</p>
                <form id="deleteForm" method="POST">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" id="delete_kunjungan_id" name="kunjungan_id">
                    <input type="hidden" name="delete_kunjungan" value="1">
                    
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

        // Filter functionality
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFilter').addEventListener('change', applyFilters);

        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (date) params.append('date', date);
            
            window.location.href = 'kunjungan-user.php?' + params.toString();
        }

        // Modal functions
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kunjungan';
            document.getElementById('form_action').name = 'add_kunjungan';
            document.getElementById('kunjunganForm').reset();
            document.getElementById('nominalField').style.display = 'none';
            document.getElementById('kunjunganModal').classList.remove('hidden');
        }

        function editKunjungan(kunjungan) {
            document.getElementById('modalTitle').textContent = 'Edit Kunjungan';
            document.getElementById('form_action').name = 'edit_kunjungan';
            document.getElementById('kunjungan_id').value = kunjungan.id;
            document.getElementById('donatur_id').value = kunjungan.donatur_id;
            document.getElementById('status').value = kunjungan.status;
            document.getElementById('nominal').value = kunjungan.nominal || '';
            document.getElementById('catatan').value = kunjungan.catatan || '';
            
            toggleNominalField();
            document.getElementById('kunjunganModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('kunjunganModal').classList.add('hidden');
        }

        function deleteKunjungan(id) {
            document.getElementById('delete_kunjungan_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function toggleNominalField() {
            const status = document.getElementById('status').value;
            const nominalField = document.getElementById('nominalField');
            const nominal = document.getElementById('nominal');
            
            if (status === 'berhasil') {
                nominalField.style.display = 'block';
                nominal.required = true;
            } else {
                nominalField.style.display = 'none';
                nominal.required = false;
                nominal.value = '';
            }
        }

        // GPS Location Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const getLocationBtn = document.getElementById('getLocationBtn');
            const locationStatus = document.getElementById('locationStatus');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const locationAddressInput = document.getElementById('location_address');
            const mapPreview = document.getElementById('mapPreview');
            
            if (getLocationBtn) {
                getLocationBtn.addEventListener('click', function() {
                    if (navigator.geolocation) {
                        locationStatus.textContent = 'Mengambil lokasi...';
                        locationStatus.className = 'text-sm text-blue-500';
                        
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                
                                latitudeInput.value = lat.toFixed(6);
                                longitudeInput.value = lng.toFixed(6);
                                
                                locationStatus.textContent = 'Lokasi berhasil diambil!';
                                locationStatus.className = 'text-sm text-green-500';
                                
                                // Show map preview
                                mapPreview.classList.remove('hidden');
                                
                                // Get address from coordinates (reverse geocoding)
                                getAddressFromCoordinates(lat, lng);
                            },
                            function(error) {
                                let errorMessage = 'Gagal mengambil lokasi';
                                switch(error.code) {
                                    case error.PERMISSION_DENIED:
                                        errorMessage = 'Izin lokasi ditolak. Silakan izinkan akses lokasi.';
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        errorMessage = 'Informasi lokasi tidak tersedia.';
                                        break;
                                    case error.TIMEOUT:
                                        errorMessage = 'Waktu tunggu habis.';
                                        break;
                                }
                                locationStatus.textContent = errorMessage;
                                locationStatus.className = 'text-sm text-red-500';
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 60000
                            }
                        );
                    } else {
                        locationStatus.textContent = 'Geolokasi tidak didukung oleh browser ini.';
                        locationStatus.className = 'text-sm text-red-500';
                    }
                });
            }
            
            // Function to get address from coordinates
            function getAddressFromCoordinates(lat, lng) {
                // Using OpenStreetMap Nominatim API for reverse geocoding
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.display_name) {
                            locationAddressInput.value = data.display_name;
                        }
                    })
                    .catch(error => {
                        console.log('Error getting address:', error);
                    });
            }
            
            // Manual GPS coordinate validation
            latitudeInput.addEventListener('input', validateGPS);
            longitudeInput.addEventListener('input', validateGPS);
            
            function validateGPS() {
                const lat = parseFloat(latitudeInput.value);
                const lng = parseFloat(longitudeInput.value);
                
                if (latitudeInput.value && longitudeInput.value) {
                    if (lat < -90 || lat > 90) {
                        latitudeInput.setCustomValidity('Latitude harus antara -90 dan 90');
                    } else if (lng < -180 || lng > 180) {
                        longitudeInput.setCustomValidity('Longitude harus antara -180 dan 180');
                    } else {
                        latitudeInput.setCustomValidity('');
                        longitudeInput.setCustomValidity('');
                    }
                }
            }
        });

        // Form validation
        document.getElementById('kunjunganForm').addEventListener('submit', function(e) {
            const donatur = document.getElementById('donatur_id').value;
            const status = document.getElementById('status').value;
            const foto = document.getElementById('foto').files[0];
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!donatur) {
                e.preventDefault();
                alert('Pilih donatur terlebih dahulu');
                return false;
            }
            
            if (!status) {
                e.preventDefault();
                alert('Pilih status terlebih dahulu');
                return false;
            }
            
            if (status === 'berhasil') {
                const nominal = document.getElementById('nominal').value;
                if (!nominal || nominal <= 0) {
                    e.preventDefault();
                    alert('Masukkan nominal donasi yang valid');
                    return false;
                }
            }
            
            if (!foto) {
                e.preventDefault();
                alert('Upload foto kunjungan terlebih dahulu');
                return false;
            }
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Ambil lokasi GPS terlebih dahulu');
                return false;
            }
            
            // Check file size (5MB limit)
            if (foto.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('Ukuran foto maksimal 5MB');
                return false;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(foto.type)) {
                e.preventDefault();
                alert('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF');
                return false;
            }
            
            // Validate GPS coordinates
            const lat = parseFloat(latitude);
            const lng = parseFloat(longitude);
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                e.preventDefault();
                alert('Koordinat GPS tidak valid');
                return false;
            }
        });

        function exportToExcel() {
            try {
                const currentUrl = window.location.href;
                const exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=excel';

                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'kunjungan_data.xls';
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