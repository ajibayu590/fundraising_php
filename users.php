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
	$searchQuery = $_GET['search'] ?? '';
	$roleFilter = $_GET['role'] ?? '';
	$statusFilter = $_GET['status'] ?? '';

	$where = ["1=1"];
	$params = [];

	if (!empty($searchQuery)) {
		$where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.hp LIKE ?)";
		$like = "%$searchQuery%";
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	if (!empty($roleFilter)) {
		$where[] = "u.role = ?";
		$params[] = $roleFilter;
	}
	if (!empty($statusFilter)) {
		$where[] = "u.status = ?";
		$params[] = $statusFilter;
	}
	$whereClause = implode(' AND ', $where);

	// Aggregated user performance data
	$stmt = $pdo->prepare("
		SELECT 
			u.id,
			u.name,
			u.email,
			u.hp,
			u.target,
			u.role,
			u.status,
			u.created_at,
			COALESCE(COUNT(k.id), 0) AS total_kunjungan,
			COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) AS total_donasi,
			COALESCE(SUM(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS kunjungan_hari_ini,
			COALESCE(SUM(CASE WHEN MONTH(k.created_at) = MONTH(CURRENT_DATE()) AND YEAR(k.created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END), 0) AS kunjungan_bulan_ini,
			COALESCE(SUM(CASE WHEN MONTH(k.created_at) = MONTH(CURRENT_DATE()) AND YEAR(k.created_at) = YEAR(CURRENT_DATE()) AND k.status='berhasil' THEN k.nominal ELSE 0 END), 0) AS donasi_bulan_ini
		FROM users u
		LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
		WHERE $whereClause
		GROUP BY u.id, u.name, u.email, u.hp, u.target, u.role, u.status, u.created_at
		ORDER BY u.name ASC
	");
	$stmt->execute($params);
	$usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// If export requested, stream CSV or Excel
	if (!empty($_GET['export'])) {
		$type = strtolower($_GET['export']);
		if (in_array($type, ['xls','excel','xlsx'], true)) {
			$filename = 'users_' . date('Ymd_His') . '.xls';
			header('Content-Type: application/vnd.ms-excel; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			echo "<table border='1'>";
			echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>HP</th><th>Role</th><th>Status</th><th>Target</th><th>Kunjungan Hari Ini</th><th>Kunjungan Bulan Ini</th><th>Donasi Bulan Ini</th></tr>";
			foreach ($usersData as $row) {
				$id = htmlspecialchars($row['id']);
				$nama = htmlspecialchars($row['name']);
				$email = htmlspecialchars($row['email']);
				$hp = htmlspecialchars($row['hp']);
				$role = htmlspecialchars($row['role']);
				$status = htmlspecialchars($row['status']);
				$target = (int)$row['target'];
				$kh = (int)$row['kunjungan_hari_ini'];
				$kb = (int)$row['kunjungan_bulan_ini'];
				$db = number_format((float)$row['donasi_bulan_ini'], 0, ',', '.');
				echo "<tr><td>$id</td><td>$nama</td><td>$email</td><td>$hp</td><td>$role</td><td>$status</td><td>$target</td><td>$kh</td><td>$kb</td><td>$db</td></tr>";
			}
			echo "</table>";
			exit;
		} else {
			$filename = 'users_' . date('Ymd_His') . '.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			$out = fopen('php://output', 'w');
			fputcsv($out, ['ID','Nama','Email','HP','Role','Status','Target','Kunjungan Hari Ini','Kunjungan Bulan Ini','Donasi Bulan Ini']);
			foreach ($usersData as $row) {
				fputcsv($out, [
					$row['id'],
					$row['name'],
					$row['email'],
					$row['hp'],
					$row['role'],
					$row['status'],
					(int)$row['target'],
					(int)$row['kunjungan_hari_ini'],
					(int)$row['kunjungan_bulan_ini'],
					number_format((float)$row['donasi_bulan_ini'], 0, ',', '.')
				]);
			}
			fclose($out);
			exit;
		}
	}

	// Stats cards
	$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
	$aktifUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='aktif'")->fetchColumn();
	$fundraiserUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
	$avgTarget = (int)$pdo->query("SELECT COALESCE(AVG(target),0) FROM users")->fetchColumn();

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
	<title>Kelola Fundraiser</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="styles/main.css">
	<?php echo get_csrf_token_meta(); ?>
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
					<h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Management Users</h1>
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
				<h2 class="text-2xl md:text-3xl font-bold text-gray-800">Kelola Fundraiser</h2>
				<p class="text-gray-600 mt-2">Manajemen data fundraiser, target, dan performa</p>
			</div>

			<?php if (isset($error_message)): ?>
			<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
				<strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?>
			</div>
			<?php endif; ?>

			<!-- Stats cards -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-blue-600"><?php echo number_format($totalUsers ?? 0); ?></div>
					<div class="text-sm text-gray-600">Total Pengguna</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-green-600"><?php echo number_format($aktifUsers ?? 0); ?></div>
					<div class="text-sm text-gray-600">Status Aktif</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-2xl font-bold text-yellow-600"><?php echo number_format($fundraiserUsers ?? 0); ?></div>
					<div class="text-sm text-gray-600">Total Fundraiser</div>
				</div>
				<div class="bg-white p-4 rounded-lg shadow">
					<div class="text-xl md:text-2xl font-bold text-purple-600"><?php echo number_format($avgTarget ?? 0); ?></div>
					<div class="text-sm text-gray-600">Rata-rata Target Harian</div>
				</div>
			</div>

			<!-- Filters -->
			<div class="bg-white rounded-xl shadow-lg p-4 md:p-6 mb-6">
				<form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
						<input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" placeholder="Nama, Email, atau HP" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
						<select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
							<option value="">Semua</option>
							<option value="admin" <?php echo ($roleFilter=='admin')?'selected':''; ?>>Admin</option>
							<option value="user" <?php echo ($roleFilter=='user')?'selected':''; ?>>Fundraiser</option>
							<option value="monitor" <?php echo ($roleFilter=='monitor')?'selected':''; ?>>Monitor</option>
						</select>
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
						<select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
							<option value="">Semua</option>
							<option value="aktif" <?php echo ($statusFilter=='aktif')?'selected':''; ?>>Aktif</option>
							<option value="nonaktif" <?php echo ($statusFilter=='nonaktif')?'selected':''; ?>>Nonaktif</option>
						</select>
					</div>
					<div class="flex items-end gap-2">
						<button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Filter</button>
						<a href="users.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">Reset</a>
						<a href="users.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">Export CSV</a>
						<a href="users.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'xls'])); ?>" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">Export Excel</a>
					</div>
				</form>
			</div>

			<!-- Actions -->
			<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
				<div class="flex items-center space-x-2">
					<h3 class="text-lg font-semibold text-gray-900">Daftar Fundraiser</h3>
					<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($usersData ?? []); ?> records</span>
				</div>
				<div class="flex flex-col sm:flex-row gap-2">
					<button onclick="bulkUpdateTarget()" class="btn btn-secondary">Update Target Massal</button>
					<button onclick="showUserModal()" class="btn btn-primary">+ Tambah Fundraiser</button>
				</div>
			</div>

			<!-- Data Table -->
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200">
						<thead class="bg-gray-50">
							<tr>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fundraiser</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Harian</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress Hari Ini</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performa Bulan Ini</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
								<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<?php if (!empty($usersData)): ?>
								<?php foreach ($usersData as $u): ?>
								<tr class="hover:bg-gray-50">
									<td class="px-6 py-4 whitespace-nowrap">
										<div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></div>
										<div class="text-xs text-gray-500">Role: <?php echo htmlspecialchars($u['role']); ?></div>
									</td>
									<td class="px-6 py-4 whitespace-nowrap">
										<div class="text-sm text-gray-900"><?php echo htmlspecialchars($u['email']); ?></div>
										<?php if (!empty($u['hp'])): ?><div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['hp']); ?></div><?php endif; ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
										<?php echo (int)$u['target']; ?> kunjungan
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
										<?php echo (int)$u['kunjungan_hari_ini']; ?> / <?php echo (int)$u['target']; ?>
										<?php 
											$percent = (int)$u['target'] > 0 ? min(100, round(($u['kunjungan_hari_ini'] / max(1,(int)$u['target']))*100)) : 0; 
										?>
										<div class="w-32 bg-gray-200 rounded-full h-2 mt-1">
											<div class="h-2 rounded-full bg-blue-600" style="width: <?php echo $percent; ?>%"></div>
										</div>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
										<?php echo (int)$u['kunjungan_bulan_ini']; ?> kunjungan • Rp <?php echo number_format($u['donasi_bulan_ini'], 0, ',', '.'); ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap">
										<?php 
											$badge = $u['status']==='aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
										?>
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge; ?>"><?php echo ucfirst($u['status']); ?></span>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
										<button onclick="editUser(<?php echo (int)$u['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
										<button onclick="deleteUser(<?php echo (int)$u['id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
									</td>
								</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data pengguna ditemukan</td>
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
		<a href="donatur.php" class="bottom-nav-item">
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
			</svg>
			Donatur
		</a>
		<a href="users.php" class="bottom-nav-item active">
			<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 017 17h10a4 4 0 011.879.804M15 11a3 3 0 10-6 0 3 3 0 006 0z"></path>
			</svg>
			Users
		</a>
	</nav>

	<!-- Enhanced Modal for Adding User -->
	<div id="user-modal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50 p-4">
		<div class="bg-white rounded-xl p-6 w-full max-w-md">
			<div class="flex justify-between items-center mb-4">
				<h3 class="text-xl font-semibold">Tambah Fundraiser</h3>
				<button onclick="hideUserModal()" class="text-gray-400 hover:text-gray-600">
					<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
			
			<form id="user-form">
				<div class="space-y-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
						<input type="text" id="user-nama" name="nama" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
						<input type="email" id="user-email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">No. HP *</label>
						<input type="text" id="user-hp" name="hp" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required pattern="[0-9]{10,13}">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Target Kunjungan Harian *</label>
						<input type="number" id="user-target" name="target" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="8" required min="1" max="20">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
						<select id="user-role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
							<option value="user">Fundraiser</option>
							<option value="monitor">Monitor</option>
							<option value="admin">Admin</option>
						</select>
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
						<input type="password" id="user-password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="6">
					</div>
				</div>
				<div class="flex flex-col md:flex-row justify-end space-y-3 md:space-y-0 md:space-x-3 mt-6">
					<button type="button" onclick="hideUserModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
						Batal
					</button>
					<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
						<span class="loading hidden mr-2"></span>
						Simpan
					</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		window.PHP_DATA = { csrfToken: '<?php echo generate_csrf_token(); ?>' };
	</script>
	<script src="js/config.js"></script>
	<script src="js/utils.js"></script>
	<script src="js/users_api.js"></script>
	<script src="js/mobile-menu.js"></script>
</body>
</html>