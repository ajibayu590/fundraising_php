<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is trying to access admin pages
if ($_SESSION['user_role'] === 'user') {
    // Log the unauthorized access attempt
    error_log("Unauthorized access attempt: User ID " . $_SESSION['user_id'] . " tried to access admin page: " . $_SERVER['REQUEST_URI']);
    
    // Set 404 status
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-content {
            margin-top: 64px !important;
            padding: 1rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px !important;
                margin-top: 64px !important;
                width: calc(100% - 250px) !important;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        
        * {
            box-sizing: border-box !important;
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
        <div class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <!-- 404 Icon -->
                <div class="mb-8">
                    <svg class="mx-auto h-32 w-32 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                
                <!-- Error Message -->
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Halaman Tidak Ditemukan</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Maaf, halaman yang Anda cari tidak ditemukan atau Anda tidak memiliki akses ke halaman tersebut.
                </p>
                
                <!-- Action Buttons -->
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="user-dashboard.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Kembali ke Dashboard
                    </a>
                    
                    <a href="user-kunjungan.php" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Kunjungan Saya
                    </a>
                </div>
                
                <!-- Additional Info -->
                <div class="mt-12 p-6 bg-yellow-50 border border-yellow-200 rounded-lg max-w-md mx-auto">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Informasi Akses
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    Sebagai Fundraiser, Anda hanya dapat mengakses halaman yang terkait dengan aktivitas fundraising Anda sendiri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'layout-footer.php'; ?>
</body>
</html>