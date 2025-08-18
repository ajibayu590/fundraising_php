<?php
$page_title = "Fundraiser Management - Fundraising System";
include 'layout-header.php';

// Check admin access
if (!in_array($user_role, ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

// HYBRID APPROACH: Load fundraiser data (users with role 'user')
try {
    $searchQuery = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    $where = ["u.role = 'user'"];
    $params = [];

    if (!empty($searchQuery)) {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.hp LIKE ?)";
        $like = "%$searchQuery%";
        $params[] = $like;
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
            u.role,
            u.status,
            u.target,
            u.kunjungan_hari_ini,
            u.total_kunjungan_bulan,
            u.total_donasi_bulan,
            u.created_at,
            u.last_active,
            COALESCE(COUNT(k.id), 0) as total_kunjungan_actual,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi_actual,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini_actual
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE $whereClause
        GROUP BY u.id, u.name, u.email, u.hp, u.role, u.status, u.target, u.kunjungan_hari_ini, u.total_kunjungan_bulan, u.total_donasi_bulan, u.created_at, u.last_active
        ORDER BY u.name
    ");
    $stmt->execute($params);
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats for fundraisers
    $totalFundraisers = count($fundraisers);
    $aktiveFundraisers = count(array_filter($fundraisers, fn($f) => $f['status'] === 'aktif'));
    $totalTargetHarian = array_sum(array_column($fundraisers, 'target'));
    $totalKunjunganHariIni = array_sum(array_column($fundraisers, 'kunjungan_hari_ini_actual'));

} catch (Exception $e) {
    $fundraisers = [];
    $error_message = "Error loading data: " . $e->getMessage();
    $totalFundraisers = 0;
    $aktiveFundraisers = 0;
    $totalTargetHarian = 0;
    $totalKunjunganHariIni = 0;
}

// CSRF token
echo get_csrf_token_meta();
?>

<!-- Fundraiser Management Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fundraiser Management</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola data fundraiser dan target kunjungan</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <?php if ($user_role === 'admin'): ?>
            <button onclick="showAddFundraiserModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Fundraiser
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Fundraiser</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalFundraisers; ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fundraiser Aktif</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $aktiveFundraisers; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Target Harian Total</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $totalTargetHarian; ?></p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Kunjungan Hari Ini</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo $totalKunjunganHariIni; ?></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Cari Fundraiser</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Nama, email, atau HP..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?php echo $statusFilter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?php echo $statusFilter === 'nonaktif' ? 'selected' : ''; ?>>Non-aktif</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Fundraiser Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Data Fundraiser (<?php echo count($fundraisers); ?>)</h2>
        </div>
        
        <?php if (empty($fundraisers)): ?>
        <div class="px-6 py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data fundraiser</h3>
            <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan fundraiser baru.</p>
        </div>
        <?php else: ?>
        
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fundraiser</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Harian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress Hari Ini</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performa Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <?php if ($user_role === 'admin'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($fundraisers as $f): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $f['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($f['email']); ?></div>
                            <?php if (!empty($f['hp'])): ?>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($f['hp']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $f['target']; ?> kunjungan</div>
                            <div class="text-xs text-gray-500">per hari</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $f['kunjungan_hari_ini_actual']; ?> / <?php echo $f['target']; ?></div>
                            <?php 
                                $percent = $f['target'] > 0 ? min(100, round(($f['kunjungan_hari_ini_actual'] / $f['target']) * 100)) : 0;
                                $progressColor = $percent >= 100 ? 'bg-green-600' : ($percent >= 75 ? 'bg-yellow-500' : 'bg-blue-600');
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="h-2 rounded-full <?php echo $progressColor; ?>" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1"><?php echo $percent; ?>% target</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $f['total_kunjungan_actual']; ?> kunjungan</div>
                            <div class="text-sm text-gray-500">Rp <?php echo number_format($f['total_donasi_actual'], 0, ',', '.'); ?></div>
                            <?php if ($f['last_active']): ?>
                            <div class="text-xs text-gray-400">Terakhir aktif: <?php echo date('d/m/Y', strtotime($f['last_active'])); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($f['status']); ?>
                            </span>
                        </td>
                        <?php if ($user_role === 'admin'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick="editFundraiser(<?php echo $f['id']; ?>)" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button onclick="setTarget(<?php echo $f['id']; ?>, <?php echo $f['target']; ?>)" class="text-green-600 hover:text-green-900">Target</button>
                            <?php if ($f['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteFundraiser(<?php echo $f['id']; ?>)" class="text-red-600 hover:text-red-900">Hapus</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden">
            <?php foreach ($fundraisers as $f): ?>
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <span class="text-xs font-medium text-blue-600"><?php echo strtoupper(substr($f['name'], 0, 2)); ?></span>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></h3>
                                <p class="text-xs text-gray-500">ID: <?php echo $f['id']; ?></p>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="text-gray-600"><?php echo htmlspecialchars($f['email']); ?></div>
                            <?php if (!empty($f['hp'])): ?>
                            <div class="text-gray-600"><?php echo htmlspecialchars($f['hp']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Target Harian</div>
                                <div class="font-medium"><?php echo $f['target']; ?> kunjungan</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Progress Hari Ini</div>
                                <div class="font-medium"><?php echo $f['kunjungan_hari_ini_actual']; ?> / <?php echo $f['target']; ?></div>
                                <?php 
                                    $percent = $f['target'] > 0 ? min(100, round(($f['kunjungan_hari_ini_actual'] / $f['target']) * 100)) : 0;
                                    $progressColor = $percent >= 100 ? 'bg-green-600' : ($percent >= 75 ? 'bg-yellow-500' : 'bg-blue-600');
                                ?>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full <?php echo $progressColor; ?>" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center space-x-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($f['status']); ?>
                            </span>
                            <span class="text-xs text-gray-500"><?php echo $f['total_kunjungan_actual']; ?> total • Rp <?php echo number_format($f['total_donasi_actual'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    <?php if ($user_role === 'admin'): ?>
                    <div class="ml-4 flex-shrink-0">
                        <div class="flex flex-col space-y-1">
                            <button onclick="editFundraiser(<?php echo $f['id']; ?>)" class="text-blue-600 hover:text-blue-900 text-xs">Edit</button>
                            <button onclick="setTarget(<?php echo $f['id']; ?>, <?php echo $f['target']; ?>)" class="text-green-600 hover:text-green-900 text-xs">Target</button>
                            <?php if ($f['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteFundraiser(<?php echo $f['id']; ?>)" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($user_role === 'admin'): ?>
    <!-- Bulk Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bulk Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="bulkUpdateTarget()" class="inline-flex items-center justify-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Update Target Massal
            </button>
            <button onclick="exportFundraisers()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Data
            </button>
            <button onclick="resetAllTargets()" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset Target Harian
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modals and JavaScript -->
<?php if ($user_role === 'admin'): ?>
<!-- Add/Edit Fundraiser Modal -->
<div id="fundraiserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah Fundraiser</h3>
            </div>
            <form id="fundraiserForm" class="px-6 py-4 space-y-4">
                <input type="hidden" id="fundraiserId" name="id">
                <div>
                    <label for="fundraiserName" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" id="fundraiserName" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="fundraiserEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="fundraiserEmail" name="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="fundraiserHp" class="block text-sm font-medium text-gray-700 mb-2">No. HP</label>
                    <input type="text" id="fundraiserHp" name="hp" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="fundraiserTarget" class="block text-sm font-medium text-gray-700 mb-2">Target Harian</label>
                    <input type="number" id="fundraiserTarget" name="target" min="1" value="8" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Jumlah kunjungan target per hari</p>
                </div>
                <div>
                    <label for="fundraiserStatus" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="fundraiserStatus" name="status" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-aktif</option>
                    </select>
                </div>
                <div id="passwordSection">
                    <label for="fundraiserPassword" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="fundraiserPassword" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                </div>
                <input type="hidden" name="role" value="user">
            </form>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                <button onclick="closeFundraiserModal()" type="button" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">Batal</button>
                <button onclick="saveFundraiser()" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Set Target Modal -->
<div id="targetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Set Target Kunjungan</h3>
            </div>
            <div class="px-6 py-4">
                <input type="hidden" id="targetUserId">
                <div>
                    <label for="newTarget" class="block text-sm font-medium text-gray-700 mb-2">Target Harian</label>
                    <input type="number" id="newTarget" min="1" max="50" value="8" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Jumlah kunjungan yang harus dicapai per hari</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                <button onclick="closeTargetModal()" type="button" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">Batal</button>
                <button onclick="saveTarget()" type="button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Update Target</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Fundraiser management functions
function refreshData() {
    window.location.reload();
}

<?php if ($user_role === 'admin'): ?>
function showAddFundraiserModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Fundraiser';
    document.getElementById('fundraiserForm').reset();
    document.getElementById('fundraiserId').value = '';
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('fundraiserPassword').required = true;
    document.getElementById('fundraiserModal').classList.remove('hidden');
}

function editFundraiser(id) {
    document.getElementById('modalTitle').textContent = 'Edit Fundraiser';
    document.getElementById('fundraiserId').value = id;
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('fundraiserPassword').required = false;
    document.getElementById('fundraiserModal').classList.remove('hidden');
    
    // TODO: Load fundraiser data via AJAX
    showNotification('Loading fundraiser data...', 'info');
}

function closeFundraiserModal() {
    document.getElementById('fundraiserModal').classList.add('hidden');
}

function setTarget(userId, currentTarget) {
    document.getElementById('targetUserId').value = userId;
    document.getElementById('newTarget').value = currentTarget;
    document.getElementById('targetModal').classList.remove('hidden');
}

function closeTargetModal() {
    document.getElementById('targetModal').classList.add('hidden');
}

async function saveTarget() {
    const userId = document.getElementById('targetUserId').value;
    const newTarget = document.getElementById('newTarget').value;
    
    if (!newTarget || newTarget < 1) {
        showNotification('Target harus minimal 1 kunjungan', 'error');
        return;
    }
    
    try {
        const response = await fetch(`api/users_crud.php?id=${userId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCSRFToken()
            },
            body: JSON.stringify({
                target: parseInt(newTarget)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Target berhasil diupdate!', 'success');
            closeTargetModal();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal update target', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

async function saveFundraiser() {
    const form = document.getElementById('fundraiserForm');
    const formData = new FormData(form);
    const fundraiserId = document.getElementById('fundraiserId').value;
    
    try {
        const url = fundraiserId ? `api/users_crud.php?id=${fundraiserId}` : 'api/users_crud.php';
        const method = fundraiserId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-Token': getCSRFToken()
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(fundraiserId ? 'Fundraiser berhasil diupdate!' : 'Fundraiser berhasil ditambahkan!', 'success');
            closeFundraiserModal();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menyimpan fundraiser', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

async function deleteFundraiser(id) {
    if (!confirm('Yakin ingin menghapus fundraiser ini?')) return;
    
    try {
        const response = await fetch(`api/users_crud.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': getCSRFToken()
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Fundraiser berhasil dihapus!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menghapus fundraiser', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

function bulkUpdateTarget() {
    const newTarget = prompt('Masukkan target harian baru untuk semua fundraiser:', '8');
    if (newTarget && parseInt(newTarget) > 0) {
        // TODO: Implement bulk update
        showNotification('Bulk update target - implement API call', 'info');
    }
}

function exportFundraisers() {
    window.open('users.php?export=csv&role=user', '_blank');
}

function resetAllTargets() {
    if (confirm('Reset semua target fundraiser ke 8 kunjungan per hari?')) {
        // TODO: Implement reset targets
        showNotification('Reset targets - implement API call', 'info');
    }
}
<?php endif; ?>

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const modals = ['fundraiserModal', 'targetModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'layout-footer.php'; ?>