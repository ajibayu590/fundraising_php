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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Insights</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/main.css">
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
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Analytics & Insights</h2>
            <p class="text-gray-600 mt-2">Analisis mendalam performa fundraising dan tren donasi</p>
        </div>
        
        <!-- Analytics Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Tren Donasi Bulanan</h3>
                <div class="chart-container">
                    <canvas id="donation-trend-chart"></canvas>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Performa Fundraiser</h3>
                <div class="chart-container">
                    <canvas id="fundraiser-performance-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Insights & Rekomendasi</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">📈 Performa Terbaik</h4>
                    <p class="text-sm text-blue-700">Budi Santoso mencapai 105% target bulan ini dengan rata-rata donasi Rp 1.67M per kunjungan.</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Perlu Perhatian</h4>
                    <p class="text-sm text-yellow-700">Dewi Sartika hanya mencapai 62% target. Pertimbangkan training tambahan atau penyesuaian area.</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-2">💡 Rekomendasi</h4>
                    <p class="text-sm text-green-700">Waktu optimal kunjungan: 10:00-12:00 dan 14:00-16:00 dengan conversion rate tertinggi.</p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <h4 class="font-semibold text-purple-800 mb-2">🎯 Target Prediksi</h4>
                    <p class="text-sm text-purple-700">Berdasarkan tren, target bulan depan dapat ditingkatkan 15% menjadi 9-10 kunjungan/hari.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/config.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/data.js"></script>
    <script src="js/ui.js"></script>
    <script src="js/charts.js"></script>
    <script src="js/app.js"></script>
</body>
</html>