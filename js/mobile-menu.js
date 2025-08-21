/**
 * Mobile Menu Handler for Fundraising System
 * Handles responsive navigation and sidebar behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    console.log('Mobile menu elements:', {
        mobileMenuBtn: !!mobileMenuBtn,
        sidebar: !!sidebar,
        sidebarOverlay: !!sidebarOverlay
    });
    
    // Mobile menu toggle function
    function toggleMobileMenu() {
        console.log('Toggle mobile menu called');
        
        if (sidebar && sidebarOverlay) {
            const isOpen = sidebar.classList.contains('mobile-open') || sidebar.classList.contains('show');
            
            if (isOpen) {
                // Close menu
                sidebar.classList.remove('mobile-open', 'show');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Update aria attributes
                if (mobileMenuBtn) {
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
                sidebar.setAttribute('aria-hidden', 'true');
                
                console.log('Mobile menu closed');
            } else {
                // Open menu
                sidebar.classList.add('mobile-open', 'show');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Update aria attributes
                if (mobileMenuBtn) {
                    mobileMenuBtn.setAttribute('aria-expanded', 'true');
                }
                sidebar.setAttribute('aria-hidden', 'false');
                
                console.log('Mobile menu opened');
            }
        } else {
            console.error('Sidebar or overlay not found');
        }
    }
    
    // Mobile menu button click
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Mobile menu button clicked');
            toggleMobileMenu();
        });
        
        // Set initial aria attributes
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        mobileMenuBtn.setAttribute('aria-label', 'Toggle mobile menu');
        mobileMenuBtn.setAttribute('type', 'button');
    } else {
        console.error('Mobile menu button not found');
    }
    
    // Overlay click to close menu
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Overlay clicked');
            toggleMobileMenu();
        });
    } else {
        console.error('Sidebar overlay not found');
    }
    
    // Close menu on window resize to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 769) {
            if (sidebar && sidebarOverlay) {
                sidebar.classList.remove('mobile-open', 'show');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Reset aria attributes
                if (mobileMenuBtn) {
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
                sidebar.setAttribute('aria-hidden', 'true');
            }
        }
    });
    
    // Close menu when clicking sidebar links on mobile
    const sidebarLinks = document.querySelectorAll('.sidebar a, .sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                console.log('Sidebar link clicked on mobile');
                setTimeout(toggleMobileMenu, 150); // Small delay for better UX
            }
        });
    });
    
    // Handle escape key to close menu
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && (sidebar.classList.contains('mobile-open') || sidebar.classList.contains('show'))) {
            console.log('Escape key pressed');
            toggleMobileMenu();
        }
    });
    
    // Set initial sidebar aria-hidden attribute
    if (sidebar) {
        sidebar.setAttribute('aria-hidden', 'true');
    }
    
    // Touch gesture support for mobile menu
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipeGesture();
    });
    
    function handleSwipeGesture() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;
        
        if (Math.abs(swipeDistance) > swipeThreshold) {
            if (swipeDistance > 0 && touchStartX < 50) {
                // Swipe right from left edge - open menu
                if (sidebar && !sidebar.classList.contains('mobile-open') && !sidebar.classList.contains('show')) {
                    console.log('Swipe right detected - opening menu');
                    toggleMobileMenu();
                }
            } else if (swipeDistance < 0 && sidebar && (sidebar.classList.contains('mobile-open') || sidebar.classList.contains('show'))) {
                // Swipe left - close menu
                console.log('Swipe left detected - closing menu');
                toggleMobileMenu();
            }
        }
    }
    
    // Prevent body scroll when menu is open
    function preventBodyScroll() {
        if (sidebar && (sidebar.classList.contains('mobile-open') || sidebar.classList.contains('show'))) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    // Initial setup
    preventBodyScroll();
    
    console.log('Mobile menu handler initialized');
});