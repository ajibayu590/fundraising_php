<?php
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit;
}

// Check if user has admin/monitor role
if ($_SESSION['user_role'] === 'user') {
    header("Location: admin-access-denied.php");
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
	$searchQuery = $_GET['search'] ?? '';
	$kategoriFilter = $_GET['kategori'] ?? '';
	
	// Build query with filters
	$whereConditions = ["1=1"]; // Always true condition
	$params = [];
	
	if (!empty($searchQuery)) {
		$whereConditions[] = "(d.nama LIKE ? OR d.hp LIKE ? OR d.email LIKE ?)";
		$searchParam = "%$searchQuery%";
		$params[] = $searchParam;
		$params[] = $searchParam;
		$params[] = $searchParam;
	}
	
	if (!empty($kategoriFilter)) {
		$whereConditions[] = "d.kategori = ?";
		$params[] = $kategoriFilter;
	}
	
	$whereClause = implode(' AND ', $whereConditions);
	
	// Get donatur data with filters and aggregated stats
	$stmt = $pdo->prepare("
		SELECT d.*, 
		       COUNT(k.id) as jumlah_kunjungan,
		       COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
		       COALESCE(AVG(CASE WHEN k.status = 'berhasil' THEN k.nominal END), 0) as rata_rata_donasi,
		       MIN(k.created_at) as first_donation,
		       MAX(k.created_at) as last_donation
		FROM donatur d 
		LEFT JOIN kunjungan k ON d.id = k.donatur_id
		WHERE $whereClause
		GROUP BY d.id, d.nama, d.hp, d.email, d.alamat, d.kategori, d.created_at
		ORDER BY d.nama ASC
	");
	$stmt->execute($params);
	$donaturData = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// If export requested, stream CSV or Excel
	if (!empty($_GET['export'])) {
		$type = strtolower($_GET['export']);
		if (in_array($type, ['xls','excel','xlsx'], true)) {
			$filename = 'donatur_' . date('Ymd_His') . '.xls';
			header('Content-Type: application/vnd.ms-excel; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			echo "<table border='1'>";
			echo "<tr><th>ID</th><th>Nama</th><th>HP</th><th>Email</th><th>Kategori</th><th>Alamat</th><th>Total Donasi</th><th>Jumlah Kunjungan</th><th>Terakhir Donasi</th></tr>";
			foreach ($donaturData as $row) {
				$id = htmlspecialchars($row['id']);
				$nama = htmlspecialchars($row['nama']);
				$hp = htmlspecialchars($row['hp']);
				$email = htmlspecialchars($row['email']);
				$kategori = htmlspecialchars($row['kategori']);
				$alamat = htmlspecialchars(preg_replace("/\s+/"," ", trim($row['alamat'] ?? '')));
				$total = number_format((float)$row['total_donasi'], 0, ',', '.');
				$jk = (int)$row['jumlah_kunjungan'];
				$last = $row['last_donation'] ? date('Y-m-d H:i:s', strtotime($row['last_donation'])) : '';
				echo "<tr><td>$id</td><td>$nama</td><td>$hp</td><td>$email</td><td>$kategori</td><td>$alamat</td><td>$total</td><td>$jk</td><td>$last</td></tr>";
			}
			echo "</table>";
			exit;
		} else {
			$filename = 'donatur_' . date('Ymd_His') . '.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			$out = fopen('php://output', 'w');
			// Header row
			fputcsv($out, ['ID','Nama','HP','Email','Kategori','Alamat','Total Donasi','Jumlah Kunjungan','Terakhir Donasi']);
			foreach ($donaturData as $row) {
				fputcsv($out, [
					$row['id'],
					$row['nama'],
					$row['hp'],
					$row['email'],
					$row['kategori'],
					preg_replace("/\s+/"," ", trim($row['alamat'] ?? '')),
					number_format((float)$row['total_donasi'], 0, ',', '.'),
					(int)$row['jumlah_kunjungan'],
					$row['last_donation'] ? date('Y-m-d H:i:s', strtotime($row['last_donation'])) : ''
				]);
			}
			fclose($out);
			exit;
		}
	}
	
	// Get donatur statistics
	$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM donatur");
	$stmt->execute();
	$totalDonatur = $stmt->fetchColumn();
	
	$stmt = $pdo->prepare("
		SELECT COUNT(DISTINCT d.id) as aktif 
		FROM donatur d 
		INNER JOIN kunjungan k ON d.id = k.donatur_id 
		WHERE k.status = 'berhasil'
	");
	$stmt->execute();
	$donaturAktif = $stmt->fetchColumn();
	
	$stmt = $pdo->prepare("
		SELECT COUNT(*) as baru 
		FROM donatur 
		WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
		AND YEAR(created_at) = YEAR(CURRENT_DATE())
	");
	$stmt->execute();
	$donaturBaru = $stmt->fetchColumn();
	
	$stmt = $pdo->prepare("
		SELECT COALESCE(AVG(nominal), 0) as rata_rata 
		FROM kunjungan 
		WHERE status = 'berhasil'
	");
	$stmt->execute();
	$rataRataDonasi = $stmt->fetchColumn();
	
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
	<title>Database Donatur</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="styles/main.css">
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
				<h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Management Donatur</h1>
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

	<div class="flex">
		<!-- Sidebar -->
		<?php include $sidebarFile; ?>
		
		<div class="main-content flex-1 p-4 md:p-8">
			<div class="mb-6 md:mb-8">
				<h2 class="text-2xl md:text-3xl font-bold text-gray-800">Database Donatur</h2>
				<p class="text-gray-600 mt-2">Kelola data donatur dan riwayat donasi lengkap</p>
			</div>
			
			<!-- Error Display -->
			<?php if (isset($error_message)): ?>
			<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
				<strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
			</div>
			<?php endif; ?>
			
			<!-- Donatur Stats -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-blue-600"><?php echo number_format($totalDonatur ?? 0); ?></div>
					<div class="text-sm text-gray-600">Total Donatur</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-green-600"><?php echo number_format($donaturAktif ?? 0); ?></div>
					<div class="text-sm text-gray-600">Donatur Aktif</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-yellow-600"><?php echo number_format($donaturBaru ?? 0); ?></div>
					<div class="text-sm text-gray-600">Donatur Baru Bulan Ini</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-xl md:text-2xl font-bold text-purple-600">Rp <?php echo number_format($rataRataDonasi ?? 0, 0, ',', '.'); ?></div>
					<div class="text-sm text-gray-600">Rata-rata Donasi</div>
				</div>
			</div>
			
			<!-- Advanced Filter -->
			<div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
				<form id="filter-form" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Cari Donatur</label>
						<input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" placeholder="Nama, HP, atau Email..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
						<select name="kategori" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
							<option value="">Semua Kategori</option>
							<option value="individu" <?php echo ($kategoriFilter == 'individu') ? 'selected' : ''; ?>>Individu</option>
							<option value="korporasi" <?php echo ($kategoriFilter == 'korporasi') ? 'selected' : ''; ?>>Korporasi</option>
							<option value="yayasan" <?php echo ($kategoriFilter == 'yayasan') ? 'selected' : ''; ?>>Yayasan</option>
							<option value="organisasi" <?php echo ($kategoriFilter == 'organisasi') ? 'selected' : ''; ?>>Organisasi</option>
						</select>
					</div>
					<div class="flex items-end gap-2">
						<button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
							Filter
						</button>
						<a href="donatur.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
							Reset
						</a>
						<a href="donatur.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
							Export CSV
						</a>
						<a href="donatur.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'xls'])); ?>" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
							Export Excel
						</a>
					</div>
				</form>
			</div>

			<!-- Action Buttons -->
			<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
				<div class="flex items-center space-x-2">
					<h3 class="text-lg font-semibold text-gray-900">Data Donatur</h3>
					<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
						<?php echo count($donaturData ?? []); ?> records
					</span>
				</div>
				<div class="flex flex-col sm:flex-row gap-2">
					<button onclick="showDonaturModal()" class="btn btn-primary">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
						</svg>
						Tambah Donatur
					</button>
					<button onclick="exportToExcel()" class="btn btn-secondary">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Donatur</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Donasi</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Kunjungan</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Donasi</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<?php if (!empty($donaturData)): ?>
								<?php foreach ($donaturData as $donatur): ?>
								<tr class="hover:bg-gray-50">
									<td class="px-6 py-4 whitespace-nowrap">
										<div class="text-sm font-medium text-gray-900">
											<?php echo htmlspecialchars($donatur['nama']); ?>
										</div>
										<div class="text-sm text-gray-500">
											<?php echo htmlspecialchars($donatur['alamat']); ?>
										</div>
									</td>
									<td class="px-6 py-4 whitespace-nowrap">
										<div class="text-sm text-gray-900">
											<?php echo htmlspecialchars($donatur['hp']); ?>
										</div>
										<?php if (!empty($donatur['email'])): ?>
										<div class="text-sm text-gray-500">
											<?php echo htmlspecialchars($donatur['email']); ?>
										</div>
										<?php endif; ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap">
										<?php 
											$kategoriColor = '';
											$kategoriText = '';
											switch($donatur['kategori']) {
												case 'individu':
													$kategoriColor = 'bg-blue-100 text-blue-800';
													$kategoriText = 'Individu';
													break;
												case 'korporasi':
													$kategoriColor = 'bg-green-100 text-green-800';
													$kategoriText = 'Korporasi';
													break;
												case 'yayasan':
													$kategoriColor = 'bg-amber-100 text-amber-800';
													$kategoriText = 'Yayasan';
													break;
												case 'organisasi':
													$kategoriColor = 'bg-purple-100 text-purple-800';
													$kategoriText = 'Organisasi';
													break;
												default:
													$kategoriColor = 'bg-gray-100 text-gray-800';
													$kategoriText = ucfirst($donatur['kategori']);
											}
										?>
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $kategoriColor; ?>">
											<?php echo $kategoriText; ?>
										</span>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
										Rp <?php echo number_format($donatur['total_donasi'], 0, ',', '.'); ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
										<?php echo number_format($donatur['jumlah_kunjungan']); ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
										<?php if ($donatur['last_donation']): ?>
											<?php echo date('d/m/Y', strtotime($donatur['last_donation'])); ?>
										<?php else: ?>
											-
										<?php endif; ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
										<button onclick="editDonatur(<?php echo $donatur['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
										<button onclick="deleteDonatur(<?php echo $donatur['id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
									</td>
								</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="7" class="px-6 py-4 text-center text-gray-500">
										Tidak ada data donatur ditemukan
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
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
			</svg>
			Dashboard
		</a>
		<a href="kunjungan.php" class="bottom-nav-item">
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
			</svg>
			Kunjungan
		</a>
		<a href="donatur.php" class="bottom-nav-item active">
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
			</svg>
			Donatur
		</a>
		<a href="analytics.php" class="bottom-nav-item">
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
			</svg>
			Analytics
		</a>
	</nav>

	<!-- Enhanced Modal for Adding Donatur -->
	<div id="donatur-modal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50 p-4">
		<div class="bg-white rounded-xl p-4 md:p-6 w-full max-w-3xl max-h-screen overflow-y-auto">
			<div class="flex justify-between items-center mb-4">
				<h3 class="text-lg md:text-xl font-semibold">Tambah Donatur Baru</h3>
				<button onclick="hideDonaturModal()" class="text-gray-400 hover:text-gray-600 p-2">
					<svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
			
			<form id="donatur-form">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Nama Donatur *</label>
						<input type="text" id="donatur-nama" name="nama" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama lengkap donatur" required>
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">No. HP *</label>
						<input type="text" id="donatur-hp" name="hp" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="08xxxxxxxxxx" required pattern="[0-9]{10,13}">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email" id="donatur-email" name="email" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="email@example.com">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Kategori *</label>
						<select id="donatur-kategori" name="kategori" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
							<option value="">Pilih Kategori</option>
							<option value="individu">Individu</option>
							<option value="korporasi">Korporasi</option>
							<option value="yayasan">Yayasan</option>
							<option value="organisasi">Organisasi</option>
						</select>
					</div>
					<div class="md:col-span-2">
						<label class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap *</label>
						<textarea id="donatur-alamat" name="alamat" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Alamat lengkap donatur" required></textarea>
					</div>
					<div class="md:col-span-2">
						<label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
						<textarea id="donatur-catatan" name="catatan" class="w-full px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Catatan tambahan tentang donatur..."></textarea>
					</div>
				</div>
				<div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 mt-6">
					<button type="button" onclick="hideDonaturModal()" class="w-full sm:w-auto px-4 py-3 md:py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors min-h-[44px]">
						Batal
					</button>
					<button type="submit" class="w-full sm:w-auto px-4 py-3 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center min-h-[44px]">
						<span class="loading hidden mr-2"></span>
						Simpan Donatur
					</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Pass PHP data to JavaScript for API operations -->
	<script>
		// Pass PHP data to JavaScript for API operations
		window.PHP_DATA = {
			csrfToken: '<?php echo generate_csrf_token(); ?>'
		};
	</script>

	<script src="js/config.js"></script>
	<script src="js/utils.js"></script>
	<script src="js/donatur_api.js"></script>
	<script src="js/mobile-menu.js"></script>
</body>
</html>