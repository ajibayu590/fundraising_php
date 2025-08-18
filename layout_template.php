<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?php echo $page_title ?? 'Fundraising System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        /* Critical CSS to prevent header being covered */
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Mobile layout */
        @media (max-width: 768px) {
            body {
                display: block !important;
            }
            
            header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 9999 !important;
                background: white !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }
            
            .page-container {
                padding-top: 5rem !important;
            }
            
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                width: 16rem !important;
                z-index: 8000 !important;
                transform: translateX(-100%) !important;
                background: white !important;
                padding-top: 5rem !important;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
                width: 100% !important;
            }
        }
        
        /* Desktop layout */
        @media (min-width: 769px) {
            body {
                display: grid !important;
                grid-template-columns: 16rem 1fr !important;
                grid-template-rows: auto 1fr !important;
                grid-template-areas: 
                    "sidebar header"
                    "sidebar main" !important;
                min-height: 100vh !important;
            }
            
            header {
                grid-area: header !important;
                position: relative !important;
                z-index: 9999 !important;
                background: white !important;
            }
            
            .sidebar {
                grid-area: sidebar !important;
                position: relative !important;
                z-index: 20 !important;
                height: 100vh !important;
                width: 16rem !important;
                background: white !important;
                overflow-y: auto !important;
            }
            
            .main-content {
                grid-area: main !important;
                margin-left: 0 !important;
                padding: 2rem !important;
                width: 100% !important;
            }
            
            .page-container {
                display: contents !important;
            }
            
            .flex {
                display: contents !important;
            }
        }
    </style>
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

    <!-- Page Container -->
    <div class="page-container">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 ml-12 md:ml-0">
                            <?php echo $page_title ?? 'Fundraising System'; ?>
                        </h1>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <span class="text-xs md:text-sm text-gray-700 hidden sm:block">
                            Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </span>
                        <span class="inline-flex items-center px-2 py-1 md:px-2.5 md:py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo ucfirst($_SESSION['user_role'] ?? 'user'); ?>
                        </span>
                        <a href="logout.php" class="text-xs md:text-sm text-red-600 hover:text-red-800 transition-colors">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <?php 
        $user_role = $_SESSION['user_role'] ?? 'user';
        include $user_role === 'admin' ? 'sidebar-admin.php' : 'sidebar-user.php'; 
        ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php echo $page_content ?? ''; ?>
        </div>
    </div>

    <script src="js/mobile-menu.js"></script>
</body>
</html>