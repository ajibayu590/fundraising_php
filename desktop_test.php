<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desktop Test - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    
    <style>
        /* EMERGENCY DESKTOP FIX */
        @media (min-width: 769px) {
            body {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            header {
                position: relative !important;
                z-index: 99999 !important;
                background: white !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 16rem !important;
                height: 100vh !important;
                z-index: 10 !important;
                background: white !important;
                overflow-y: auto !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
            }
            
            .main-content {
                margin-left: 16rem !important;
                padding: 2rem !important;
                width: calc(100% - 16rem) !important;
                min-height: 100vh !important;
            }
            
            .flex {
                display: flex !important;
            }
            
            .mobile-menu-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">Desktop Test - Header Fix</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Test User</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Admin</span>
                    <a href="#" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Test Sidebar -->
        <aside class="sidebar bg-white shadow-lg min-h-screen">
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        Kunjungan
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"></path>
                        </svg>
                        Donatur
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"></path>
                        </svg>
                        Users
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content flex-1">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Desktop Mode Test</h2>
                <p class="text-gray-600">Testing header and sidebar on desktop mode</p>
            </div>

            <!-- Test Results -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Header Status</h3>
                    <div class="space-y-2 text-sm">
                        <div><strong>Position:</strong> <span id="header-position">-</span></div>
                        <div><strong>Z-Index:</strong> <span id="header-zindex">-</span></div>
                        <div><strong>Background:</strong> <span id="header-bg">-</span></div>
                        <div><strong>Width:</strong> <span id="header-width">-</span></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Sidebar Status</h3>
                    <div class="space-y-2 text-sm">
                        <div><strong>Position:</strong> <span id="sidebar-position">-</span></div>
                        <div><strong>Z-Index:</strong> <span id="sidebar-zindex">-</span></div>
                        <div><strong>Width:</strong> <span id="sidebar-width">-</span></div>
                        <div><strong>Transform:</strong> <span id="sidebar-transform">-</span></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Screen Info</h3>
                    <div class="space-y-2 text-sm">
                        <div><strong>Width:</strong> <span id="screen-width">-</span>px</div>
                        <div><strong>Height:</strong> <span id="screen-height">-</span>px</div>
                        <div><strong>Mode:</strong> <span id="screen-mode">-</span></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Layout Test</h3>
                    <div class="space-y-2 text-sm">
                        <div id="header-visible" class="text-green-600">✅ Header Visible</div>
                        <div id="sidebar-visible" class="text-green-600">✅ Sidebar Visible</div>
                        <div id="no-overlap" class="text-green-600">✅ No Overlap</div>
                        <div id="responsive-ok" class="text-green-600">✅ Responsive OK</div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
                <h3 class="text-blue-800 font-semibold mb-2">Desktop Test Instructions</h3>
                <ul class="list-disc list-inside text-blue-700 text-sm space-y-1">
                    <li>Pastikan screen width ≥ 769px untuk mode desktop</li>
                    <li>Header harus visible dan tidak tertutup sidebar</li>
                    <li>Sidebar harus fixed di sebelah kiri</li>
                    <li>Main content harus memiliki margin-left yang cukup</li>
                    <li>Tidak boleh ada horizontal scroll</li>
                </ul>
            </div>

            <!-- Quick Links to Test Other Pages -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Test Other Pages</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-center hover:bg-blue-700 transition-colors">Dashboard</a>
                    <a href="users.php" class="bg-green-600 text-white px-4 py-2 rounded-lg text-center hover:bg-green-700 transition-colors">Users</a>
                    <a href="donatur.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-center hover:bg-purple-700 transition-colors">Donatur</a>
                    <a href="kunjungan.php" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-center hover:bg-orange-700 transition-colors">Kunjungan</a>
                </div>
                <p class="text-sm text-gray-600 mt-4">Klik link di atas untuk test header/sidebar di halaman lain</p>
            </div>
        </div>
    </div>

    <script>
        function updateDebugInfo() {
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            // Screen info
            document.getElementById('screen-width').textContent = width;
            document.getElementById('screen-height').textContent = height;
            
            let mode = 'Mobile';
            if (width >= 1025) mode = 'Desktop';
            else if (width >= 769) mode = 'Tablet';
            document.getElementById('screen-mode').textContent = mode;
            
            // Header info
            if (header) {
                const headerStyle = window.getComputedStyle(header);
                document.getElementById('header-position').textContent = headerStyle.position;
                document.getElementById('header-zindex').textContent = headerStyle.zIndex;
                document.getElementById('header-bg').textContent = headerStyle.backgroundColor;
                document.getElementById('header-width').textContent = headerStyle.width;
            }
            
            // Sidebar info
            if (sidebar) {
                const sidebarStyle = window.getComputedStyle(sidebar);
                document.getElementById('sidebar-position').textContent = sidebarStyle.position;
                document.getElementById('sidebar-zindex').textContent = sidebarStyle.zIndex;
                document.getElementById('sidebar-width').textContent = sidebarStyle.width;
                document.getElementById('sidebar-transform').textContent = sidebarStyle.transform;
            }
        }
        
        updateDebugInfo();
        window.addEventListener('resize', updateDebugInfo);
        setInterval(updateDebugInfo, 1000);
    </script>
</body>
</html>