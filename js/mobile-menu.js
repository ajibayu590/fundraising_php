/**
 * Mobile Menu Handler for Fundraising System
 * Handles responsive navigation and sidebar behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    // Mobile menu toggle function
    function toggleMobileMenu() {
        if (sidebar && sidebarOverlay) {
            const isOpen = sidebar.classList.contains('mobile-open');
            
            if (isOpen) {
                // Close menu
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Update aria attributes
                mobileMenuBtn?.setAttribute('aria-expanded', 'false');
                sidebar.setAttribute('aria-hidden', 'true');
            } else {
                // Open menu
                sidebar.classList.add('mobile-open');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Update aria attributes
                mobileMenuBtn?.setAttribute('aria-expanded', 'true');
                sidebar.setAttribute('aria-hidden', 'false');
            }
        }
    }
    
    // Mobile menu button click
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        // Set initial aria attributes
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        mobileMenuBtn.setAttribute('aria-label', 'Toggle mobile menu');
    }
    
    // Overlay click to close menu
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleMobileMenu);
    }
    
    // Close menu on window resize to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 769) {
            if (sidebar && sidebarOverlay) {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Reset aria attributes
                mobileMenuBtn?.setAttribute('aria-expanded', 'false');
                sidebar.setAttribute('aria-hidden', 'true');
            }
        }
    });
    
    // Close menu when clicking sidebar links on mobile
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                setTimeout(toggleMobileMenu, 150); // Small delay for better UX
            }
        });
    });
    
    // Handle escape key to close menu
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('mobile-open')) {
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
        const swipeThreshold = 100;
        const swipeDistance = touchEndX - touchStartX;
        
        // Only handle swipes on mobile
        if (window.innerWidth <= 768) {
            // Swipe right from left edge to open menu
            if (touchStartX < 50 && swipeDistance > swipeThreshold) {
                if (sidebar && !sidebar.classList.contains('mobile-open')) {
                    toggleMobileMenu();
                }
            }
            // Swipe left to close menu when it's open
            else if (swipeDistance < -swipeThreshold && sidebar && sidebar.classList.contains('mobile-open')) {
                toggleMobileMenu();
            }
        }
    }
});