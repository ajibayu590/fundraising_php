<?php
// Prevent any output before headers
ob_start();
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target & Laporan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    <?php echo get_csrf_token_meta(); ?>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn fixed top-4 left-4 z-50 bg-blue-600 text-white p-2 rounded-lg shadow-lg md:hidden" onclick="toggleMobileSidebar()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <?php include $sidebarFile; ?>
    
    <div class="main-content p-4 md:p-8 md:ml-64">
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Target & Laporan</h2>
            <p class="text-gray-600 mt-2">Atur target dan pantau laporan performa fundraising</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Pengaturan Target Global</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Kunjungan Harian</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo getSettingValue('target_global', 8); ?>" id="target-global" min="1" max="20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Donasi Harian (Rp)</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo getSettingValue('target_donasi', 1000000); ?>" id="target-donasi" min="100000" step="100000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Donatur Baru per Bulan</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo getSettingValue('target_donatur_baru', 50); ?>" id="target-donatur-baru" min="10">
                    </div>
                    <button onclick="updateTargetGlobal()" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        Update Target Global
                    </button>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Ringkasan Performa Hari Ini</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-sm font-medium">Target Tercapai</span>
                        <span class="text-lg font-bold text-green-600" id="target-tercapai">0/0</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                        <span class="text-sm font-medium">Dalam Progress (>50%)</span>
                        <span class="text-lg font-bold text-yellow-600" id="dalam-progress">0/0</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                        <span class="text-sm font-medium">Perlu Perhatian (<50%)</span>
                        <span class="text-lg font-bold text-red-600" id="perlu-perhatian">0/0</span>
                    </div>
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600" id="rata-rata-pencapaian">0%</div>
                            <div class="text-sm text-gray-600">Rata-rata Pencapaian Target</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laporan Bulanan -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h3 class="text-xl font-semibold">Laporan Bulanan</h3>
                <div class="flex flex-wrap gap-3">
                    <select class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" id="laporan-bulan">
                        <option value="12">Desember 2024</option>
                        <option value="11">November 2024</option>
                        <option value="10">Oktober 2024</option>
                    </select>
                    <button onclick="generateLaporan()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                        Generate Laporan
                    </button>
                    <button onclick="exportLaporan()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        Export PDF
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800" id="laporan-total-kunjungan">0</div>
                    <div class="text-sm text-gray-600">Total Kunjungan</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-xl md:text-2xl font-bold text-gray-800" id="laporan-total-donasi">Rp 0</div>
                    <div class="text-sm text-gray-600">Total Donasi</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800" id="laporan-conversion-rate">0%</div>
                    <div class="text-sm text-gray-600">Conversion Rate</div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/config.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/data.js"></script>
    <script src="js/ui.js"></script>
    <script src="js/app.js"></script>
    <script src="js/charts.js"></script>
</body>
</html>