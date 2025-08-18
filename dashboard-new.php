<?php
$page_title = "Dashboard - Fundraising System";
include 'layout-header.php';

// Get dashboard data directly from database
require_once 'config.php';

try {
    $today = date('Y-m-d');
    
    // Total kunjungan hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $total_kunjungan = $stmt->fetchColumn();
    
    // Donasi berhasil hari ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $donasi_berhasil = $stmt->fetchColumn();
    
    // Total donasi hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $total_donasi = $stmt->fetchColumn();
    
    // Fundraiser aktif
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'aktif'");
    $stmt->execute();
    $fundraiser_aktif = $stmt->fetchColumn();
    
    // Target setting
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'target_harian'");
    $stmt->execute();
    $target_harian = $stmt->fetchColumn() ?: 10000000;
    
    // Progress percentage
    $progress_percentage = $target_harian > 0 ? min(($total_donasi / $target_harian) * 100, 100) : 0;
    
} catch (Exception $e) {
    $total_kunjungan = 0;
    $donasi_berhasil = 0;
    $total_donasi = 0;
    $fundraiser_aktif = 0;
    $target_harian = 10000000;
    $progress_percentage = 0;
}

// CSRF token meta tag for JavaScript
echo get_csrf_token_meta();
?>

<!-- Dashboard Content -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Selamat datang di sistem fundraising</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button onclick="refreshAllData()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Data
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Kunjungan -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Kunjungan Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_kunjungan); ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Donasi Berhasil -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Donasi Berhasil</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo number_format($donasi_berhasil); ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Donasi -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Donasi Hari Ini</p>
                    <p class="text-3xl font-bold text-yellow-600">Rp <?php echo number_format($total_donasi, 0, ',', '.'); ?></p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Fundraiser Aktif -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Fundraiser Aktif</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo number_format($fundraiser_aktif); ?></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Target -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Progress Target Harian</h2>
            <span class="text-sm text-gray-600"><?php echo number_format($progress_percentage, 1); ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: <?php echo $progress_percentage; ?>%"></div>
        </div>
        <div class="flex justify-between text-sm text-gray-600">
            <span>Rp <?php echo number_format($total_donasi, 0, ',', '.'); ?></span>
            <span>Target: Rp <?php echo number_format($target_harian, 0, ',', '.'); ?></span>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Performance Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Performa 7 Hari Terakhir</h2>
            <div class="relative h-64">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Status Distribution Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Status Kunjungan</h2>
            <div class="relative h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <?php if ($user_role === 'admin'): ?>
    <!-- Admin Tools -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Tools</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="insertDummyData()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Insert Data Dummy
            </button>
            <button onclick="deleteDummyData()" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus Data Dummy
            </button>
            <button onclick="exportData()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Data
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Include JavaScript files -->
<script src="js/utils.js"></script>
<script src="js/charts.js"></script>
<script src="js/app.js"></script>

<script>
// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Auto refresh every 5 minutes
    setInterval(refreshAllData, 300000);
});

// Refresh all dashboard data
async function refreshAllData() {
    try {
        showNotification('Memuat data terbaru...', 'info', 2000);
        
        // Refresh the page to get latest data
        window.location.reload();
        
    } catch (error) {
        console.error('Error refreshing data:', error);
        showNotification('Gagal memperbarui data', 'error');
    }
}

// Admin functions
<?php if ($user_role === 'admin'): ?>
async function insertDummyData() {
    if (!confirm('Yakin ingin menambahkan data dummy ke database?')) return;
    
    try {
        const response = await fetch('api/dummy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCSRFToken()
            },
            body: JSON.stringify({ action: 'insert_dummy_data' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Data dummy berhasil ditambahkan!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menambahkan data dummy', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

async function deleteDummyData() {
    if (!confirm('Yakin ingin menghapus semua data dummy dari database?')) return;
    
    try {
        const response = await fetch('api/dummy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCSRFToken()
            },
            body: JSON.stringify({ action: 'delete_dummy_data' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Data dummy berhasil dihapus!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(result.message || 'Gagal menghapus data dummy', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    }
}

function exportData() {
    window.open('export.php', '_blank');
}
<?php endif; ?>

// Initialize charts with current data
function initializeCharts() {
    // Daily performance chart
    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: ['6 hari lalu', '5 hari lalu', '4 hari lalu', '3 hari lalu', '2 hari lalu', 'Kemarin', 'Hari ini'],
                datasets: [{
                    label: 'Donasi (Rp)',
                    data: [0, 0, 0, 0, 0, 0, <?php echo $total_donasi; ?>],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Donasi: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Status distribution chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Berhasil', 'Tidak Berhasil', 'Follow Up'],
                datasets: [{
                    data: [<?php echo $donasi_berhasil; ?>, <?php echo $total_kunjungan - $donasi_berhasil; ?>, 0],
                    backgroundColor: [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                        'rgb(251, 191, 36)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}
</script>

<?php include 'layout-footer.php'; ?>