<?php
// Header Template - Fixed positioning untuk semua halaman
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
?>

<style>
    /* Fixed Header Styles */
    .fixed-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1000 !important;
        background: white !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        height: 64px !important;
    }
    
    /* Sidebar adjustments for fixed header */
    .sidebar {
        position: fixed !important;
        top: 64px !important; /* Header height */
        left: 0 !important;
        width: 16rem !important;
        height: calc(100vh - 64px) !important; /* Full height minus header */
        z-index: 500 !important;
        background: white !important;
        box-shadow: 2px 0 4px rgba(0,0,0,0.1) !important;
        overflow-y: auto !important;
    }
    
    /* Main content adjustments */
    .main-content {
        margin-left: 16rem !important; /* Sidebar width */
        margin-top: 64px !important; /* Header height */
        padding: 2rem !important;
        min-height: calc(100vh - 64px) !important;
        width: calc(100% - 16rem) !important;
    }
    
    /* Mobile Menu Button */
    .mobile-menu-btn {
        position: fixed !important;
        top: 12px !important;
        left: 12px !important;
        z-index: 1100 !important; /* Above header */
        background: white !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
        display: none !important;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: flex !important;
        }
        
        .sidebar {
            transform: translateX(-100%) !important;
            transition: transform 0.3s ease-in-out !important;
        }
        
        .sidebar.mobile-open {
            transform: translateX(0) !important;
        }
        
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 1rem !important;
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
    }
    
    /* Desktop - hide mobile menu */
    @media (min-width: 769px) {
        .mobile-menu-btn {
            display: none !important;
        }
        
        .sidebar-overlay {
            display: none !important;
        }
    }
    
    /* Header content styling */
    .header-content {
        height: 64px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 0 1rem !important;
    }
    
    .header-title {
        font-size: 1.25rem !important;
        font-weight: bold !important;
        color: #1f2937 !important;
        margin-left: 3rem !important; /* Space for mobile menu button */
    }
    
    @media (min-width: 769px) {
        .header-title {
            margin-left: 0 !important;
        }
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
    
    @media (max-width: 640px) {
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
</style>

<!-- Mobile Menu Button -->
<button id="mobile-menu-btn" class="mobile-menu-btn">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Sidebar Overlay for Mobile -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- Fixed Header -->
<header class="fixed-header bg-white border-b">
    <div class="header-content max-w-full mx-auto">
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

<script>
// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('active');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Close sidebar when clicking on main content on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isClickOnMenuBtn = mobileMenuBtn && mobileMenuBtn.contains(e.target);
            
            if (!isClickInsideSidebar && !isClickOnMenuBtn && sidebar && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        }
    });
});
</script>