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

// HYBRID APPROACH: Load data directly with PHP for table display
try {
    // Get filter parameters
    $dateStart = $_GET['date_start'] ?? date('Y-m-01'); // First day of current month
    $dateEnd = $_GET['date_end'] ?? date('Y-m-d'); // Today
    $fundraiserFilter = $_GET['fundraiser'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    
    // Build query with filters
    $whereConditions = ["DATE(k.created_at) BETWEEN ? AND ?"];
    $params = [$dateStart, $dateEnd];
    
    if (!empty($fundraiserFilter)) {
        $whereConditions[] = "k.fundraiser_id = ?";
        $params[] = $fundraiserFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "k.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get kunjungan data with filters
    $stmt = $pdo->prepare("
        SELECT k.*, u.name as fundraiser_name, d.nama as donatur_name, d.hp as donatur_hp
        FROM kunjungan k 
        LEFT JOIN users u ON k.fundraiser_id = u.id 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE $whereClause
        ORDER BY k.created_at DESC
    ");
    $stmt->execute($params);
    $kunjunganData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all fundraisers for filter dropdown
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'user' ORDER BY name");
    $stmt->execute();
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all donatur for suggestions
    $stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur ORDER BY nama");
    $stmt->execute();
    $donaturList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Data Kunjungan Fundraiser</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/icon-fixes.css">
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        /* FINAL HEADER FIX - SIMPLE & EFFECTIVE */
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000 !important;
            background: white !important;
            width: 100% !important;
            height: 64px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        .sidebar {
            z-index: 500 !important;
            position: fixed !important;
            top: 64px !important;
            left: 0 !important;
            width: 16rem !important;
            height: calc(100vh - 64px) !important;
            background: white !important;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1) !important;
            overflow-y: auto !important;
        }
        
        .main-content {
            margin-left: 16rem !important;
            margin-top: 64px !important;
            padding: 2rem !important;
            width: calc(100% - 16rem) !important;
            min-height: calc(100vh - 64px) !important;
        }
        
        @media (max-width: 768px) {
            header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 99999 !important;
            }
            
            .mobile-menu-btn {
                display: flex !important;
                position: fixed !important;
                top: 1rem !important;
                left: 1rem !important;
                z-index: 999999 !important;
                background: white !important;
                border-radius: 0.5rem !important;
                padding: 0.5rem !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            }
            
            .sidebar {
                transform: translateX(-100%) !important;
                padding-top: 5rem !important;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
                padding-top: 6rem !important;
                width: 100% !important;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
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

	<!-- Sidebar Overlay for Mobile -->
	<div id="sidebar-overlay" class="sidebar-overlay"></div>

	<!-- Header -->
	<header class="bg-white shadow-sm border-b">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center" style="height: 64px !important;">
                			<div class="flex items-center">
				<h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Management Kunjungan</h1>
			</div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                    <a href="logout.php" class="text-xs md:text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    	    <div class="flex kunjungan-page">
        <!-- Sidebar -->
        <?php include $sidebarFile; ?>
    
        <div class="main-content flex-1 p-4 md:p-8">
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Data Kunjungan Fundraiser</h2>
                <p class="text-gray-600 mt-2">Pantau semua kunjungan dan hasil fundraising secara real-time</p>
            </div>
            
            <!-- Error Display -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Advanced Filter -->
            <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
                <form id="filter-form" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                        <input type="date" name="date_start" value="<?php echo htmlspecialchars($dateStart ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="date_end" value="<?php echo htmlspecialchars($dateEnd ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fundraiser</label>
                        <select name="fundraiser" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Fundraiser</option>
                            <?php foreach ($fundraisers ?? [] as $fundraiser): ?>
                            <option value="<?php echo $fundraiser['id']; ?>" <?php echo ($fundraiserFilter == $fundraiser['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fundraiser['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="berhasil" <?php echo ($statusFilter == 'berhasil') ? 'selected' : ''; ?>>Berhasil</option>
                            <option value="tidak-berhasil" <?php echo ($statusFilter == 'tidak-berhasil') ? 'selected' : ''; ?>>Tidak Berhasil</option>
                            <option value="follow-up" <?php echo ($statusFilter == 'follow-up') ? 'selected' : ''; ?>>Follow Up</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Filter
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="kunjungan.php" class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-semibold text-gray-900">Data Kunjungan</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo count($kunjunganData ?? []); ?> records
                    </span>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button onclick="showKunjunganModal()" class="btn btn-primary">
                        <svg class="icon-sm mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Kunjungan
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-secondary">
                        <svg class="icon-sm mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Excel
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fundraiser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donatur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($kunjunganData)): ?>
                                <?php foreach ($kunjunganData as $kunjungan): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($kunjungan['fundraiser_name'] ?? 'Unknown'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($kunjungan['donatur_name'] ?? 'Unknown'); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($kunjungan['donatur_hp'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $statusColor = '';
                                        $statusText = '';
                                        switch($kunjungan['status']) {
                                            case 'berhasil':
                                                $statusColor = 'bg-green-100 text-green-800';
                                                $statusText = 'Berhasil';
                                                break;
                                            case 'tidak-berhasil':
                                                $statusColor = 'bg-red-100 text-red-800';
                                                $statusText = 'Tidak Berhasil';
                                                break;
                                            case 'follow-up':
                                                $statusColor = 'bg-yellow-100 text-yellow-800';
                                                $statusText = 'Follow Up';
                                                break;
                                            default:
                                                $statusColor = 'bg-gray-100 text-gray-800';
                                                $statusText = ucfirst($kunjungan['status']);
                                        }
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($kunjungan['status'] == 'berhasil' && $kunjungan['nominal'] > 0): ?>
                                            Rp <?php echo number_format($kunjungan['nominal'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($kunjungan['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editKunjungan(<?php echo $kunjungan['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button onclick="deleteKunjungan(<?php echo $kunjungan['id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data kunjungan ditemukan
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation for Mobile -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="bottom-nav-item">
            <svg class="icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
            </svg>
            Dashboard
        </a>
        <a href="kunjungan.php" class="bottom-nav-item active">
            <svg class="icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Kunjungan
        </a>
        <a href="donatur.php" class="bottom-nav-item">
            <svg class="icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            Donatur
        </a>
        <a href="analytics.php" class="bottom-nav-item">
            <svg class="icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Analytics
        </a>
    </nav>

    <!-- Enhanced Modal for Adding Kunjungan -->
    <div id="kunjungan-modal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-4 md:p-6 w-full max-w-3xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-semibold">Tambah Kunjungan Fundraiser</h3>
                <button onclick="hideKunjunganModal()" class="text-gray-400 hover:text-gray-600 p-2">
                    <svg class="icon-md md:icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="kunjungan-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fundraiser *</label>
                        <select id="kunjungan-fundraiser" name="fundraiser" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Pilih Fundraiser</option>
                            <?php foreach ($fundraisers ?? [] as $fundraiser): ?>
                            <option value="<?php echo $fundraiser['id']; ?>">
                                <?php echo htmlspecialchars($fundraiser['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Donatur *</label>
                        <input type="text" id="kunjungan-donatur" name="donatur" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ketik nama donatur..." required autocomplete="off">
                        <div id="donatur-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 hidden max-h-40 overflow-y-auto"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. HP Donatur *</label>
                        <input type="text" id="kunjungan-hp" name="hp" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="08xxxxxxxxxx" required pattern="[0-9]{10,13}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Kunjungan *</label>
                        <select id="kunjungan-status" name="status" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Pilih Status</option>
                            <option value="berhasil">Berhasil (Ada Donasi)</option>
                            <option value="tidak-berhasil">Tidak Berhasil</option>
                            <option value="follow-up">Follow Up</option>
                        </select>
                    </div>
                    <div id="nominal-field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nominal Donasi (Rp) *</label>
                        <input type="number" id="kunjungan-nominal" name="nominal" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0" min="1000" step="1000">
                    </div>
                    <div id="follow-up-field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Follow Up</label>
                        <input type="date" id="kunjungan-follow-up" name="followUpDate" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap *</label>
                        <textarea id="kunjungan-alamat" name="alamat" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Alamat lengkap donatur" required></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea id="kunjungan-catatan" name="catatan" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Catatan tambahan tentang kunjungan..."></textarea>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 mt-6">
                    <button type="button" onclick="hideKunjunganModal()" class="w-full sm:w-auto px-4 py-3 md:py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors min-h-[44px]">
                        Batal
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-4 py-3 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center min-h-[44px]">
                        <span class="loading hidden mr-2"></span>
                        Simpan Kunjungan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript for API operations -->
    <script>
        // Pass PHP data to JavaScript for API operations
        window.PHP_DATA = {
            donaturList: <?php echo json_encode($donaturList ?? []); ?>,
            fundraisers: <?php echo json_encode($fundraisers ?? []); ?>,
            csrfToken: '<?php echo generate_csrf_token(); ?>'
        };
    </script>

    	<script src="js/config.js"></script>
	<script src="js/utils.js"></script>
	<script src="js/kunjungan_api.js"></script>
	<script src="js/mobile-menu.js"></script>
	<script src="js/icon-fixes.js"></script>
</body>
</html>