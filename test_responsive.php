<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Test Responsive - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="mobile-menu-btn">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">Test Responsive Layout</h1>
                </div>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <span class="text-xs md:text-sm text-gray-700 hidden sm:block">Test User</span>
                    <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Admin</span>
                    <a href="#" class="text-xs md:text-sm text-red-600 hover:text-red-800 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Test Sidebar -->
        <aside id="sidebar" class="sidebar bg-white shadow-lg min-h-screen">
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <a href="#" class="sidebar-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="#" class="sidebar-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        Kunjungan
                    </a>
                    <a href="#" class="sidebar-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"></path>
                        </svg>
                        Donatur
                    </a>
                    <a href="#" class="sidebar-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"></path>
                        </svg>
                        Users
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content flex-1 p-4 md:p-8">
            <div class="mb-6 md:mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Responsive Layout Test</h2>
                <p class="text-gray-600">Testing header and sidebar responsiveness across different screen sizes</p>
            </div>

            <!-- Test Cards -->
            <div class="stats-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Mobile (≤768px)</h3>
                    <p class="text-sm text-gray-600">Header fixed, sidebar slides from left, mobile menu button visible</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tablet (769-1024px)</h3>
                    <p class="text-sm text-gray-600">Header relative, sidebar fixed, smaller sidebar width (14rem)</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Desktop (≥1025px)</h3>
                    <p class="text-sm text-gray-600">Header relative, sidebar fixed, full width (16rem)</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Current Screen</h3>
                    <p class="text-sm text-gray-600">Width: <span id="screen-width">-</span>px</p>
                    <p class="text-sm text-gray-600">Height: <span id="screen-height">-</span>px</p>
                </div>
            </div>

            <!-- Test Instructions -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Test Instructions</h3>
                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                    <li>Resize browser window to test different breakpoints</li>
                    <li>On mobile (≤768px): Header should be fixed, mobile menu button should appear</li>
                    <li>Click mobile menu button to open/close sidebar</li>
                    <li>Sidebar should slide in from left with overlay</li>
                    <li>Click overlay or sidebar link to close sidebar</li>
                    <li>On tablet/desktop: Sidebar should be always visible, no mobile menu button</li>
                    <li><strong>CRITICAL: Header should NEVER be covered by sidebar</strong></li>
                    <li>Test swipe gestures on mobile devices</li>
                </ol>
            </div>

            <!-- Z-Index Debug Info -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-yellow-800 mb-4">Z-Index Debug Info</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>Header Z-Index:</strong> <span id="header-zindex">-</span><br>
                        <strong>Sidebar Z-Index:</strong> <span id="sidebar-zindex">-</span><br>
                        <strong>Mobile Menu Z-Index:</strong> <span id="menu-zindex">-</span>
                    </div>
                    <div>
                        <strong>Header Position:</strong> <span id="header-position">-</span><br>
                        <strong>Sidebar Position:</strong> <span id="sidebar-position">-</span><br>
                        <strong>Screen Size:</strong> <span id="screen-category">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update screen dimensions and debug info
        function updateScreenInfo() {
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            document.getElementById('screen-width').textContent = width;
            document.getElementById('screen-height').textContent = height;
            
            // Screen category
            let category = '';
            if (width <= 768) category = 'Mobile';
            else if (width <= 1024) category = 'Tablet';
            else category = 'Desktop';
            document.getElementById('screen-category').textContent = category;
            
            // Get computed styles
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const mobileBtn = document.querySelector('.mobile-menu-btn');
            
            if (header) {
                const headerStyle = window.getComputedStyle(header);
                document.getElementById('header-zindex').textContent = headerStyle.zIndex;
                document.getElementById('header-position').textContent = headerStyle.position;
            }
            
            if (sidebar) {
                const sidebarStyle = window.getComputedStyle(sidebar);
                document.getElementById('sidebar-zindex').textContent = sidebarStyle.zIndex;
                document.getElementById('sidebar-position').textContent = sidebarStyle.position;
            }
            
            if (mobileBtn) {
                const menuStyle = window.getComputedStyle(mobileBtn);
                document.getElementById('menu-zindex').textContent = menuStyle.zIndex;
            }
        }
        
        updateScreenInfo();
        window.addEventListener('resize', updateScreenInfo);
        
        // Update every second to catch any changes
        setInterval(updateScreenInfo, 1000);
    </script>
    <script src="js/mobile-menu.js"></script>
</body>
</html>