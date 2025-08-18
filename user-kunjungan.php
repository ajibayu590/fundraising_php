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
    $dateStart = $_GET['date_start'] ?? date('Y-m-01'); // First day of current month
    $dateEnd = $_GET['date_end'] ?? date('Y-m-d'); // Today
    $statusFilter = $_GET['status'] ?? '';
    
    // Build query with filters - ONLY user's own kunjungan
    $whereConditions = ["k.fundraiser_id = ?"]; // Only user's own data
    $params = [$user_id];
    
    if (!empty($dateStart) && !empty($dateEnd)) {
        $whereConditions[] = "DATE(k.created_at) BETWEEN ? AND ?";
        $params[] = $dateStart;
        $params[] = $dateEnd;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "k.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get kunjungan data with filters - ONLY user's own data
    $stmt = $pdo->prepare("
        SELECT k.*, d.nama as donatur_name, d.hp as donatur_hp
        FROM kunjungan k 
        LEFT JOIN donatur d ON k.donatur_id = d.id 
        WHERE $whereClause
        ORDER BY k.created_at DESC
    ");
    $stmt->execute($params);
    $kunjunganData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all donatur for suggestions (including those not yet visited by this user)
    $stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur ORDER BY nama");
    $stmt->execute();
    $donaturList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's stats
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, $today]);
    $kunjungan_hari_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $berhasil_hari_ini = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE fundraiser_id = ? AND DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$user_id, $today]);
    $total_donasi_hari_ini = $stmt->fetchColumn();
    
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
    <title>Kunjungan Saya - Fundraising System</title>
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
        
        /* Responsive fixes */
        @media (max-width: 640px) {
            .main-content {
                margin-left: 0 !important;
                padding: 0.5rem !important;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
            }
            
            .overflow-x-auto {
                overflow-x: auto !important;
            }
            
            table {
                min-width: 600px !important;
            }
            
            .modal {
                width: 95% !important;
                margin: 0 auto !important;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            .sidebar.open {
                transform: translateX(0);
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
            <h1 class="text-2xl font-bold text-gray-900">Kunjungan Saya</h1>
            <p class="text-gray-600">Kelola dan pantau kunjungan fundraising Anda</p>
        </div>
        
        <!-- Today's Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Kunjungan Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $kunjungan_hari_ini; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Berhasil Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $berhasil_hari_ini; ?></p>
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
                        <p class="text-sm font-medium text-gray-600">Total Donasi Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_donasi_hari_ini, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Data Kunjungan</h3>
                    <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">
                        <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Kunjungan
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
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="date_start" value="<?php echo htmlspecialchars($dateStart); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                        <input type="date" name="date_end" value="<?php echo htmlspecialchars($dateEnd); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="berhasil" <?php echo $statusFilter === 'berhasil' ? 'selected' : ''; ?>>Berhasil</option>
                            <option value="tidak-berhasil" <?php echo $statusFilter === 'tidak-berhasil' ? 'selected' : ''; ?>>Tidak Berhasil</option>
                            <option value="follow-up" <?php echo $statusFilter === 'follow-up' ? 'selected' : ''; ?>>Follow Up</option>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donatur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($kunjunganData)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data kunjungan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kunjunganData as $kunjungan): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($kunjungan['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($kunjungan['donatur_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($kunjungan['donatur_hp']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($kunjungan['alamat']); ?>">
                                            <?php echo htmlspecialchars($kunjungan['alamat']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'berhasil' => 'bg-green-100 text-green-800',
                                            'tidak-berhasil' => 'bg-red-100 text-red-800',
                                            'follow-up' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $statusColor = $statusColors[$kunjungan['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $kunjungan['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($kunjungan['status'] === 'berhasil' && $kunjungan['nominal'] > 0): ?>
                                            Rp <?php echo number_format($kunjungan['nominal'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editKunjungan(<?php echo $kunjungan['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button onclick="deleteKunjungan(<?php echo $kunjungan['id']; ?>)" class="text-red-600 hover:text-red-900">Hapus</button>
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
    <div id="kunjunganModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Kunjungan Baru</h3>
                <form id="kunjunganForm" class="space-y-4">
                    <input type="hidden" id="kunjungan_id" name="id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Donatur</label>
                        <select id="donatur_id" name="donatur_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Donatur</option>
                            <?php foreach ($donaturList as $donatur): ?>
                                <option value="<?php echo $donatur['id']; ?>"><?php echo htmlspecialchars($donatur['nama']); ?> (<?php echo htmlspecialchars($donatur['hp']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Kunjungan</label>
                        <textarea id="alamat" name="alamat" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan alamat kunjungan"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Status</option>
                            <option value="berhasil">Berhasil</option>
                            <option value="tidak-berhasil">Tidak Berhasil</option>
                            <option value="follow-up">Follow Up</option>
                        </select>
                    </div>
                    
                    <div id="nominal-field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Donasi</label>
                        <input type="number" id="nominal" name="nominal" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan nominal donasi">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea id="catatan" name="catatan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Catatan kunjungan"></textarea>
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
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kunjungan Baru';
            document.getElementById('kunjunganForm').reset();
            document.getElementById('kunjungan_id').value = '';
            document.getElementById('nominal-field').classList.add('hidden');
            document.getElementById('kunjunganModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('kunjunganModal').classList.add('hidden');
        }
        
        function editKunjungan(id) {
            // Fetch kunjungan data and populate form
            fetch(`api/kunjungan.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const kunjungan = data.data;
                        document.getElementById('modalTitle').textContent = 'Edit Kunjungan';
                        document.getElementById('kunjungan_id').value = kunjungan.id;
                        document.getElementById('donatur_id').value = kunjungan.donatur_id;
                        document.getElementById('alamat').value = kunjungan.alamat;
                        document.getElementById('status').value = kunjungan.status;
                        document.getElementById('nominal').value = kunjungan.nominal || '';
                        document.getElementById('catatan').value = kunjungan.catatan || '';
                        
                        if (kunjungan.status === 'berhasil') {
                            document.getElementById('nominal-field').classList.remove('hidden');
                        }
                        
                        document.getElementById('kunjunganModal').classList.remove('hidden');
                    }
                });
        }
        
        function deleteKunjungan(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kunjungan ini?')) {
                fetch(`api/kunjungan.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus kunjungan: ' + data.message);
                    }
                });
            }
        }
        
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            window.open(`user-kunjungan.php?${params.toString()}&export=xlsx`, '_blank');
        }
        
        // Form submission
        document.getElementById('kunjunganForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            const method = data.id ? 'PUT' : 'POST';
            const url = data.id ? `api/kunjungan.php?id=${data.id}` : 'api/kunjungan.php';
            
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
                    alert('Gagal menyimpan kunjungan: ' + data.message);
                }
            });
        });
        
        // Status change handler
        document.getElementById('status').addEventListener('change', function() {
            const nominalField = document.getElementById('nominal-field');
            if (this.value === 'berhasil') {
                nominalField.classList.remove('hidden');
                document.getElementById('nominal').required = true;
            } else {
                nominalField.classList.add('hidden');
                document.getElementById('nominal').required = false;
            }
        });
    </script>
</body>
</html>