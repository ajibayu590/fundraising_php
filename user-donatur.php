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

// HYBRID APPROACH: Load data directly with PHP for table display
try {
    // Get filter parameters
    $searchQuery = $_GET['search'] ?? '';
    $kategoriFilter = $_GET['kategori'] ?? '';
    
    // Build query with filters - ONLY donatur yang pernah dikunjungi oleh user ini
    $whereConditions = ["d.id IN (SELECT DISTINCT donatur_id FROM kunjungan WHERE fundraiser_id = ?)"];
    $params = [$user_id];
    
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
    
    // Get donatur data with filters and aggregated stats - ONLY user's donatur
    $stmt = $pdo->prepare("
        SELECT d.*, 
               COUNT(k.id) as jumlah_kunjungan,
               COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
               COALESCE(AVG(CASE WHEN k.status = 'berhasil' THEN k.nominal END), 0) as rata_rata_donasi,
               MIN(k.created_at) as first_donation,
               MAX(k.created_at) as last_donation
        FROM donatur d 
        LEFT JOIN kunjungan k ON d.id = k.donatur_id AND k.fundraiser_id = ?
        WHERE $whereClause
        GROUP BY d.id, d.nama, d.hp, d.email, d.alamat, d.kategori, d.created_at
        ORDER BY d.nama ASC
    ");
    $stmt->execute($params);
    $donaturData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's donatur stats
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT d.id) as total_donatur,
               COUNT(k.id) as total_kunjungan,
               COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
        FROM donatur d 
        LEFT JOIN kunjungan k ON d.id = k.donatur_id AND k.fundraiser_id = ?
        WHERE d.id IN (SELECT DISTINCT donatur_id FROM kunjungan WHERE fundraiser_id = ?)
    ");
    $stmt->execute([$user_id, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If export requested, stream CSV or Excel
    if (!empty($_GET['export'])) {
        $type = strtolower($_GET['export']);
        if (in_array($type, ['xls','excel','xlsx'], true)) {
            $filename = 'donatur_saya_' . date('Ymd_His') . '.xls';
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
            $filename = 'donatur_saya_' . date('Ymd_His') . '.csv';
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
                    $row['alamat'],
                    $row['total_donasi'],
                    $row['jumlah_kunjungan'],
                    $row['last_donation'] ? date('Y-m-d H:i:s', strtotime($row['last_donation'])) : ''
                ]);
            }
            fclose($out);
            exit;
        }
    }
    
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
    <title>Donatur Saya - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
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
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        .main-content {
            margin-top: 64px !important;
            padding: 1rem !important;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px !important;
                margin-top: 64px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'layout-header.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'sidebar-user.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header Section -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Donatur Saya</h1>
            <p class="text-gray-600">Kelola dan pantau donatur yang Anda kunjungi</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Donatur</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_donatur'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Kunjungan</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_kunjungan'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Donasi</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($stats['total_donasi'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Data Donatur</h3>
                    <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">
                        <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Donatur
                        </button>
                        <button onclick="exportData()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama, HP, atau Email">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="kategori" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Kategori</option>
                            <option value="individu" <?php echo $kategoriFilter === 'individu' ? 'selected' : ''; ?>>Individu</option>
                            <option value="korporasi" <?php echo $kategoriFilter === 'korporasi' ? 'selected' : ''; ?>>Korporasi</option>
                            <option value="yayasan" <?php echo $kategoriFilter === 'yayasan' ? 'selected' : ''; ?>>Yayasan</option>
                            <option value="organisasi" <?php echo $kategoriFilter === 'organisasi' ? 'selected' : ''; ?>>Organisasi</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Donasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunjungan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($donaturData)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">Tidak ada data donatur</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($donaturData as $donatur): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($donatur['nama']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $donatur['id']; ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($donatur['hp']); ?></div>
                                            <?php if (!empty($donatur['email'])): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($donatur['email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $kategoriColors = [
                                            'individu' => 'bg-blue-100 text-blue-800',
                                            'korporasi' => 'bg-green-100 text-green-800',
                                            'yayasan' => 'bg-purple-100 text-purple-800',
                                            'organisasi' => 'bg-orange-100 text-orange-800'
                                        ];
                                        $kategoriColor = $kategoriColors[$donatur['kategori']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $kategoriColor; ?>">
                                            <?php echo ucfirst($donatur['kategori']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($donatur['alamat']); ?>">
                                            <?php echo htmlspecialchars($donatur['alamat']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Rp <?php echo number_format($donatur['total_donasi'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $donatur['jumlah_kunjungan']; ?> kali
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($donatur['last_donation']): ?>
                                            <?php echo date('d/m/Y', strtotime($donatur['last_donation'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewDonatur(<?php echo $donatur['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Detail</button>
                                        <button onclick="editDonatur(<?php echo $donatur['id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="donaturModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Donatur Baru</h3>
                <form id="donaturForm" class="space-y-4">
                    <input type="hidden" id="donatur_id" name="id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" id="nama" name="nama" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nama donatur">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                        <input type="text" id="hp" name="hp" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nomor HP">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Email (opsional)">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select id="kategori" name="kategori" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Kategori</option>
                            <option value="individu">Individu</option>
                            <option value="korporasi">Korporasi</option>
                            <option value="yayasan">Yayasan</option>
                            <option value="organisasi">Organisasi</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea id="alamat" name="alamat" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Alamat lengkap"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea id="catatan" name="catatan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Catatan tambahan"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'layout-footer.php'; ?>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Donatur Baru';
            document.getElementById('donaturForm').reset();
            document.getElementById('donatur_id').value = '';
            document.getElementById('donaturModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('donaturModal').classList.add('hidden');
        }
        
        function editDonatur(id) {
            // Fetch donatur data and populate form
            fetch(`api/donatur.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const donatur = data.data;
                        document.getElementById('modalTitle').textContent = 'Edit Donatur';
                        document.getElementById('donatur_id').value = donatur.id;
                        document.getElementById('nama').value = donatur.nama;
                        document.getElementById('hp').value = donatur.hp;
                        document.getElementById('email').value = donatur.email || '';
                        document.getElementById('kategori').value = donatur.kategori;
                        document.getElementById('alamat').value = donatur.alamat;
                        document.getElementById('catatan').value = donatur.catatan || '';
                        
                        document.getElementById('donaturModal').classList.remove('hidden');
                    }
                });
        }
        
        function viewDonatur(id) {
            // Redirect to donatur detail page or show in modal
            window.open(`donatur-detail.php?id=${id}`, '_blank');
        }
        
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            window.open(`user-donatur.php?${params.toString()}&export=xlsx`, '_blank');
        }
        
        // Form submission
        document.getElementById('donaturForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            const method = data.id ? 'PUT' : 'POST';
            const url = data.id ? `api/donatur.php?id=${data.id}` : 'api/donatur.php';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert('Gagal menyimpan donatur: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>