<?php
// Layout Header - Template untuk memulai halaman dengan fixed header dan sidebar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Unknown User';
$user_role = $_SESSION['user_role'] ?? 'user';
$page_title = $page_title ?? 'Fundraising System';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/main.css">
    
    <style>
        /* Reset and base styles */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Fixed Header */
        .fixed-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000 !important;
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            height: 64px !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        /* Sidebar positioning */
        .sidebar {
            position: fixed !important;
            top: 64px !important; /* Below header */
            left: 0 !important;
            width: 16rem !important;
            height: calc(100vh - 64px) !important;
            z-index: 500 !important;
            background: white !important;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1) !important;
            overflow-y: auto !important;
            transition: transform 0.3s ease-in-out !important;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 16rem !important; /* Sidebar width */
            margin-top: 64px !important; /* Header height */
            padding: 2rem !important;
            min-height: calc(100vh - 64px) !important;
            width: calc(100% - 16rem) !important;
            background-color: #f9fafb !important;
        }
        
        /* Header content */
        .header-content {
            height: 64px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 1rem !important;
            max-width: 100% !important;
        }
        
        .header-title {
            font-size: 1.25rem !important;
            font-weight: bold !important;
            color: #1f2937 !important;
            margin-left: 3rem !important; /* Space for mobile menu */
        }
        
        .header-user-info {
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
        }
        
        .user-role-badge {
            display: inline-flex !important;
            align-items: center !important;
            padding: 0.25rem 0.75rem !important;
            border-radius: 9999px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }
        
        .logout-link {
            font-size: 0.875rem !important;
            color: #dc2626 !important;
            text-decoration: none !important;
            transition: color 0.2s !important;
        }
        
        .logout-link:hover {
            color: #991b1b !important;
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            position: fixed !important;
            top: 12px !important;
            left: 12px !important;
            z-index: 1100 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            display: none !important;
            cursor: pointer !important;
        }
        
        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 400 !important;
            display: none !important;
        }
        
        .sidebar-overlay.active {
            display: block !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex !important;
            }
            
            .header-title {
                margin-left: 3rem !important;
                font-size: 1.125rem !important;
            }
            
            .sidebar {
                transform: translateX(-100%) !important;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem !important;
            }
            
            .header-user-info {
                gap: 0.5rem !important;
            }
            
            .welcome-text {
                display: none !important;
            }
            
            .user-role-badge {
                font-size: 0.625rem !important;
                padding: 0.125rem 0.5rem !important;
            }
            
            .logout-link {
                font-size: 0.75rem !important;
            }
        }
        
        /* Desktop */
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
            }
            
            .header-title {
                margin-left: 0 !important;
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }
        
        /* Sidebar navigation styles */
        .sidebar-link {
            display: flex !important;
            align-items: center !important;
            padding: 0.75rem 1rem !important;
            margin: 0.25rem 0.5rem !important;
            color: #374151 !important;
            text-decoration: none !important;
            border-radius: 0.5rem !important;
            transition: all 0.2s !important;
        }
        
        .sidebar-link:hover {
            background-color: #f3f4f6 !important;
            color: #1f2937 !important;
        }
        
        .sidebar-link.active {
            background-color: #3b82f6 !important;
            color: white !important;
        }
        
        .sidebar-link svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            margin-right: 0.75rem !important;
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

    <!-- Fixed Header -->
    <header class="fixed-header bg-white">
        <div class="header-content">
            <div class="header-title">
                Fundraising System
            </div>
            <div class="header-user-info">
                <span class="welcome-text text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <span class="user-role-badge">
                    <?php echo ucfirst($user_role); ?>
                </span>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    </header>

    <!-- Include appropriate sidebar based on user role -->
    <?php
    if ($user_role === 'admin') {
        include 'sidebar-admin.php';
    } else {
        include 'sidebar-user.php';
    }
    ?>

    <!-- Main Content Area -->
    <main class="main-content">