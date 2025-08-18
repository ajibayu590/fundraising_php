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

// Load fundraiser data - SIMPLE VERSION
try {
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
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
            COALESCE(COUNT(CASE WHEN DATE(k.created_at) = CURDATE() THEN 1 END), 0) as kunjungan_hari_ini
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.email, u.hp, u.role, u.status, u.target, u.created_at
        ORDER BY u.status DESC, u.name ASC
    ");
    $stmt->execute();
    $fundraisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalFundraisers = count($fundraisers);
    $aktiveFundraisers = count(array_filter($fundraisers, fn($f) => $f['status'] === 'aktif'));
    
} catch (Exception $e) {
    $fundraisers = [];
    $totalFundraisers = 0;
    $aktiveFundraisers = 0;
    $error_message = $e->getMessage();
}

echo get_csrf_token_meta();
?>

<style>
/* Ensure table is visible */
.fundraiser-table {
    display: block !important;
    visibility: visible !important;
}

.table-container {
    display: block !important;
    overflow-x: auto !important;
}

table {
    display: table !important;
    width: 100% !important;
}

tbody tr {
    display: table-row !important;
}
</style>

<!-- Fundraiser Management Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📊 Fundraiser Management</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola data fundraiser dan target kunjungan</p>
                
                <!-- Debug Info -->
                <div class="mt-3 p-3 bg-blue-50 rounded border">
                    <p class="text-sm text-blue-800">
                        🔍 <strong>Status:</strong> 
                        <?php if (isset($error_message)): ?>
                            <span class="text-red-600">Error: <?php echo htmlspecialchars($error_message); ?></span>
                        <?php else: ?>
                            <span class="text-green-600">✅ Data berhasil dimuat</span> - 
                            Menampilkan <strong><?php echo $totalFundraisers; ?> fundraiser</strong> 
                            (<?php echo $aktiveFundraisers; ?> aktif)
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="mt-4 sm:mt-0">
                <button onclick="window.location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    🔄 Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600"><?php echo $totalFundraisers; ?></p>
                <p class="text-sm text-gray-600">Total Fundraiser</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600"><?php echo $aktiveFundraisers; ?></p>
                <p class="text-sm text-gray-600">Fundraiser Aktif</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-yellow-600"><?php echo array_sum(array_column($fundraisers, 'target')); ?></p>
                <p class="text-sm text-gray-600">Total Target Harian</p>
            </div>
        </div>
    </div>

    <!-- SIMPLE TABLE - ALWAYS VISIBLE -->
    <div class="bg-white rounded-lg shadow fundraiser-table">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">📋 Data Fundraiser - SEMUA LANGSUNG TAMPIL</h2>
            <p class="text-sm text-gray-600">Total: <?php echo count($fundraisers); ?> fundraiser</p>
        </div>
        
        <div class="table-container p-6">
            <?php if (empty($fundraisers)): ?>
                <!-- No Data State -->
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Data Fundraiser</h3>
                    <p class="text-gray-600 mb-4">Tambahkan data dummy atau buat fundraiser baru</p>
                    <?php if ($user_role === 'admin'): ?>
                    <a href="dashboard.php" class="inline-block px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        🔧 Insert Data Dummy
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fundraiser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target/Hari</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress Hari Ini</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Donasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <?php if ($user_role === 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($fundraisers as $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $f['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <span class="text-xs font-medium text-blue-600">
                                                <?php echo strtoupper(substr($f['name'], 0, 2)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($f['name']); ?></div>
                                            <div class="text-xs text-gray-500">Bergabung: <?php echo date('d/m/Y', strtotime($f['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($f['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($f['hp'] ?: '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-blue-600"><?php echo $f['target']; ?></div>
                                        <div class="text-xs text-gray-500">kunjungan</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="text-sm font-medium"><?php echo $f['kunjungan_hari_ini']; ?> / <?php echo $f['target']; ?></div>
                                        <?php 
                                            $percent = $f['target'] > 0 ? min(100, round(($f['kunjungan_hari_ini'] / $f['target']) * 100)) : 0;
                                            $color = $percent >= 100 ? 'bg-green-500' : ($percent >= 75 ? 'bg-yellow-500' : 'bg-blue-500');
                                        ?>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div class="h-2 rounded-full <?php echo $color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $percent; ?>%</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-green-600">Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $f['total_kunjungan']; ?> kunjungan</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; 
                                    ?>">
                                        <?php echo ucfirst($f['status']); ?>
                                    </span>
                                </td>
                                <?php if ($user_role === 'admin'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="editFundraiser(<?php echo $f['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900 transition-colors">
                                            Edit
                                        </button>
                                        <button onclick="setTarget(<?php echo $f['id']; ?>, <?php echo $f['target']; ?>)" 
                                                class="text-green-600 hover:text-green-900 transition-colors">
                                            Target
                                        </button>
                                        <?php if ($f['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteFundraiser(<?php echo $f['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900 transition-colors">
                                            Hapus
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="md:hidden mt-6">
                    <h3 class="font-semibold mb-4">📱 Mobile View:</h3>
                    <?php foreach ($fundraisers as $f): ?>
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium"><?php echo htmlspecialchars($f['name']); ?></h4>
                            <span class="text-xs px-2 py-1 rounded <?php echo $f['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100'; ?>">
                                <?php echo $f['status']; ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>📧 <?php echo htmlspecialchars($f['email']); ?></div>
                            <div>📞 <?php echo htmlspecialchars($f['hp'] ?: '-'); ?></div>
                            <div>🎯 Target: <?php echo $f['target']; ?>/hari</div>
                            <div>📊 Hari ini: <?php echo $f['kunjungan_hari_ini']; ?>/<?php echo $f['target']; ?></div>
                        </div>
                        <div class="mt-2">
                            <div class="text-sm">💰 Total: Rp <?php echo number_format($f['total_donasi'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user_role === 'admin' && $totalFundraisers > 0): ?>
    <!-- Admin Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="bulkUpdateTarget()" class="inline-flex items-center justify-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Update Target Massal
            </button>
            <button onclick="window.open('users.php?export=csv&role=user', '_blank')" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Data
            </button>
            <button onclick="window.location.href='users.php?action=add&role=user'" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Fundraiser
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Target Update Modal -->
<div id="targetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">🎯 Update Target Kunjungan</h3>
            <input type="hidden" id="targetUserId">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Target Harian Baru:</label>
                <input type="number" id="newTarget" min="1" max="50" value="8" 
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex space-x-2">
                <button onclick="closeTargetModal()" class="flex-1 px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Batal
                </button>
                <button onclick="saveTarget()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    💾 Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Ensure table is visible on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Fundraiser page loaded');
    
    // Force show table
    const table = document.querySelector('.fundraiser-table');
    if (table) {
        table.style.display = 'block';
        table.style.visibility = 'visible';
        console.log('✅ Table forced to display');
    }
    
    // Show data count
    const fundraiserCount = <?php echo count($fundraisers); ?>;
    console.log(`📊 Fundraiser count: ${fundraiserCount}`);
    
    // Data loaded silently - no popup alerts
});

// Target management functions
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
    
    if (newTarget < 1) {
        alert('Target minimal 1 kunjungan per hari');
        return;
    }
    
    try {
        const response = await fetch(`api/users_crud.php?id=${userId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                target: parseInt(newTarget)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Target berhasil diupdate!');
            closeTargetModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('❌ ' + (result.message || 'Gagal update target'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Terjadi kesalahan sistem');
    }
}

async function editFundraiser(id) {
    // Redirect to users.php with edit mode
    window.location.href = `users.php?action=edit&id=${id}`;
}

async function deleteFundraiser(id) {
    if (!confirm('Yakin ingin menghapus fundraiser ini?')) return;
    
    try {
        const response = await fetch(`api/users_crud.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Fundraiser berhasil dihapus!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('❌ ' + (result.message || 'Gagal menghapus fundraiser'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Terjadi kesalahan sistem');
    }
}

async function bulkUpdateTarget() {
    const newTarget = prompt('Target harian baru untuk semua fundraiser:', '8');
    if (!newTarget || parseInt(newTarget) < 1) return;
    
    if (!confirm(`Update semua target menjadi ${newTarget} kunjungan per hari?`)) return;
    
    try {
        // Get all fundraiser IDs
        const fundraiserIds = <?php echo json_encode(array_column($fundraisers, 'id')); ?>;
        let successCount = 0;
        
        for (const id of fundraiserIds) {
            const response = await fetch(`api/users_crud.php?id=${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    target: parseInt(newTarget)
                })
            });
            
            const result = await response.json();
            if (result.success) successCount++;
        }
        
        alert(`✅ Berhasil update ${successCount} dari ${fundraiserIds.length} fundraiser`);
        setTimeout(() => window.location.reload(), 1000);
        
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Terjadi kesalahan sistem');
    }
}

function showAddFundraiserModal() {
    alert('Add new fundraiser\nImplement add fundraiser modal or redirect');
}
</script>

<?php include 'layout-footer.php'; ?>