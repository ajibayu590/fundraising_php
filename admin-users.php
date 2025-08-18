<?php
$page_title = "User Management - Fundraising System";
include 'layout-header.php';

// Check admin access
if (!in_array($user_role, ['admin', 'monitor'])) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

// HYBRID APPROACH: Load admin and monitor users data
try {
    $searchQuery = $_GET['search'] ?? '';
    $roleFilter = $_GET['role'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    $where = ["u.role IN ('admin', 'monitor')"];
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
            u.created_at,
            u.last_active,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE $whereClause
        GROUP BY u.id, u.name, u.email, u.hp, u.role, u.status, u.created_at, u.last_active
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute($params);
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalAdminUsers = count($adminUsers);
    $totalAdmins = count(array_filter($adminUsers, fn($u) => $u['role'] === 'admin'));
    $totalMonitors = count(array_filter($adminUsers, fn($u) => $u['role'] === 'monitor'));
    $activeUsers = count(array_filter($adminUsers, fn($u) => $u['status'] === 'aktif'));

} catch (Exception $e) {
    $adminUsers = [];
    $error_message = "Error loading data: " . $e->getMessage();
    $totalAdminUsers = 0;
    $totalAdmins = 0;
    $totalMonitors = 0;
    $activeUsers = 0;
}

// CSRF token
echo get_csrf_token_meta();
?>

<!-- Admin Users Management Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola data admin dan monitor sistem</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <?php if ($user_role === 'admin'): ?>
            <button onclick="showAddUserModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah User
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $totalAdminUsers; ?></p>
                </div>
                <div class="p-3 bg-gray-100 rounded-full">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Admin</p>
                    <p class="text-3xl font-bold text-red-600"><?php echo $totalAdmins; ?></p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0121 12a11.955 11.955 0 01-1.382 5.618m0 0l-2.503-2.503M21 12H3"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Monitor</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $totalMonitors; ?></p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">User Aktif</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $activeUsers; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <option value="monitor" <?php echo $roleFilter === 'monitor' ? 'selected' : ''; ?>>Monitor</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Admin Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Data Admin & Monitor (<?php echo count($adminUsers); ?>)</h2>
        </div>
        
        <?php if (empty($adminUsers)): ?>
        <div class="px-6 py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data user admin/monitor</h3>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Access Level</th>
                        <?php if ($user_role === 'admin'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($adminUsers as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full <?php echo $u['role'] === 'admin' ? 'bg-red-100' : 'bg-yellow-100'; ?> flex items-center justify-center">
                                        <span class="text-sm font-medium <?php echo $u['role'] === 'admin' ? 'text-red-600' : 'text-yellow-600'; ?>"><?php echo strtoupper(substr($u['name'], 0, 2)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['email']); ?></div>
                                    <?php if (!empty($u['hp'])): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['hp']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $u['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; 
                            ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $u['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($u['last_active']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($u['last_active'])); ?>
                            <?php else: ?>
                                <span class="text-gray-400">Belum pernah login</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="text-red-600 font-medium">Full Access</span>
                                    <div class="text-xs text-gray-500">Create, Read, Update, Delete</div>
                                <?php else: ?>
                                    <span class="text-yellow-600 font-medium">Read Only</span>
                                    <div class="text-xs text-gray-500">View & Export data</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php if ($user_role === 'admin'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick="editUser(<?php echo $u['id']; ?>)" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="text-red-600 hover:text-red-900">Hapus</button>
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
            <?php foreach ($adminUsers as $u): ?>
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 rounded-full <?php echo $u['role'] === 'admin' ? 'bg-red-100' : 'bg-yellow-100'; ?> flex items-center justify-center mr-3">
                                <span class="text-xs font-medium <?php echo $u['role'] === 'admin' ? 'text-red-600' : 'text-yellow-600'; ?>"><?php echo strtoupper(substr($u['name'], 0, 2)); ?></span>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></h3>
                                <p class="text-xs text-gray-500">ID: <?php echo $u['id']; ?></p>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="text-gray-600"><?php echo htmlspecialchars($u['email']); ?></div>
                            <?php if (!empty($u['hp'])): ?>
                            <div class="text-gray-600"><?php echo htmlspecialchars($u['hp']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 flex items-center space-x-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $u['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; 
                            ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                echo $u['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            <?php if ($u['last_active']): ?>
                                Last active: <?php echo date('d/m/Y H:i', strtotime($u['last_active'])); ?>
                            <?php else: ?>
                                Belum pernah login
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($user_role === 'admin'): ?>
                    <div class="ml-4 flex-shrink-0">
                        <div class="flex flex-col space-y-1">
                            <button onclick="editUser(<?php echo $u['id']; ?>)" class="text-blue-600 hover:text-blue-900 text-xs">Edit</button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
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
</div>

<!-- Add/Edit User Modal -->
<?php if ($user_role === 'admin'): ?>
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
                        <option value="admin">Admin</option>
                        <option value="monitor">Monitor</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Admin: Full access | Monitor: Read-only access</p>
                </div>
                <div>
                    <label for="userStatus" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="userStatus" name="status" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-aktif</option>
                    </select>
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
<?php endif; ?>

<script>
// User management functions
function refreshData() {
    window.location.reload();
}

<?php if ($user_role === 'admin'): ?>
function showAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Tambah User Admin/Monitor';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(id) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = id;
    document.getElementById('passwordSection').style.display = 'block';
    document.getElementById('userPassword').required = false;
    document.getElementById('userModal').classList.remove('hidden');
    
    // TODO: Load user data via AJAX
    showNotification('Loading user data...', 'info');
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
<?php endif; ?>

// Close modal when clicking outside
document.getElementById('userModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>

<?php include 'layout-footer.php'; ?>