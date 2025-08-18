<?php
$page_title = "Users Management - Fundraising System";
include 'layout-header.php';

// Check admin access
if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

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

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.hp,
            u.role,
            u.status,
            u.target,
            u.created_at,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE $whereClause
        GROUP BY u.id, u.name, u.email, u.hp, u.role, u.status, u.target, u.created_at
        ORDER BY u.name
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $users = [];
    $error_message = "Error loading data: " . $e->getMessage();
}

// CSRF token
echo get_csrf_token_meta();
?>

<!-- Users Management Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Users Management</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola data user dan fundraiser</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <button onclick="showAddUserModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah User
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Cari User</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Nama, email, atau HP..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="monitor" <?php echo $roleFilter === 'monitor' ? 'selected' : ''; ?>>Monitor</option>
                </select>
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

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Data Users (<?php echo count($users); ?>)</h2>
        </div>
        
        <?php if (empty($users)): ?>
        <div class="px-6 py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data user</h3>
            <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan user baru.</p>
        </div>
        <?php else: ?>
        
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user_data): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['email']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['hp']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $user_data['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                    ($user_data['role'] === 'monitor' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'); 
                            ?>">
                                <?php echo ucfirst($user_data['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $user_data['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($user_data['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($user_data['target']); ?> / hari
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($user_data['total_kunjungan']); ?> kunjungan</div>
                            <div class="text-sm text-gray-500">Rp <?php echo number_format($user_data['total_donasi'], 0, ',', '.'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick="editUser(<?php echo $user_data['id']; ?>)" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $user_data['id']; ?>)" class="text-red-600 hover:text-red-900">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden">
            <?php foreach ($users as $user_data): ?>
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['email']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['hp']); ?></p>
                        <div class="mt-2 flex space-x-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $user_data['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                    ($user_data['role'] === 'monitor' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'); 
                            ?>">
                                <?php echo ucfirst($user_data['role']); ?>
                            </span>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $user_data['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($user_data['status']); ?>
                            </span>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            <div>Target: <?php echo number_format($user_data['target']); ?>/hari</div>
                            <div><?php echo number_format($user_data['total_kunjungan']); ?> kunjungan • Rp <?php echo number_format($user_data['total_donasi'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <div class="flex space-x-2">
                            <button onclick="editUser(<?php echo $user_data['id']; ?>)" class="text-blue-600 hover:text-blue-900 text-sm">Edit</button>
                            <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $user_data['id']; ?>)" class="text-red-600 hover:text-red-900 text-sm">Hapus</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah User</h3>
            </div>
            <form id="userForm" class="px-6 py-4 space-y-4">
                <input type="hidden" id="userId" name="id">
                <div>
                    <label for="userName" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" id="userName" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="userEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="userEmail" name="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="userHp" class="block text-sm font-medium text-gray-700 mb-2">No. HP</label>
                    <input type="text" id="userHp" name="hp" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="userRole" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select id="userRole" name="role" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="monitor">Monitor</option>
                    </select>
                </div>
                <div>
                    <label for="userStatus" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="userStatus" name="status" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-aktif</option>
                    </select>
                </div>
                <div>
                    <label for="userTarget" class="block text-sm font-medium text-gray-700 mb-2">Target Harian</label>
                    <input type="number" id="userTarget" name="target" min="1" value="8" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div id="passwordSection">
                    <label for="userPassword" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="userPassword" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                </div>
            </form>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                <button onclick="closeUserModal()" type="button" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">Batal</button>
                <button onclick="saveUser()" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
// User management functions
function refreshData() {
    window.location.reload();
}

function showAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Tambah User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(id) {
    // In a real implementation, you would fetch user data via AJAX
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = id;
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('userPassword').required = false;
    document.getElementById('userModal').classList.remove('hidden');
    
    // You would populate the form with existing user data here
    showNotification('Edit user functionality - implement AJAX call to get user data', 'info');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

async function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    const userId = document.getElementById('userId').value;
    
    try {
        const url = userId ? `api/users_crud.php?id=${userId}` : 'api/users_crud.php';
        const method = userId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-Token': getCSRFToken()
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(userId ? 'User berhasil diupdate!' : 'User berhasil ditambahkan!', 'success');
            closeUserModal();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menyimpan user', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

async function deleteUser(id) {
    if (!confirm('Yakin ingin menghapus user ini?')) return;
    
    try {
        const response = await fetch(`api/users_crud.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': getCSRFToken()
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('User berhasil dihapus!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menghapus user', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

// Close modal when clicking outside
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>

<?php include 'layout-footer.php'; ?>