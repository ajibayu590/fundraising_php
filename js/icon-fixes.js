// ICON FIXES FOR HOSTING ENVIRONMENT
// This script dynamically fixes icon sizing issues

document.addEventListener('DOMContentLoaded', function() {
    fixIconSizes();
    
    // Fix icons after any dynamic content is loaded
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                fixIconSizes();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

function fixIconSizes() {
    // Fix all SVG elements
    const allSvgs = document.querySelectorAll('svg');
    
    allSvgs.forEach(function(svg) {
        // Remove any conflicting classes
        svg.classList.remove('w-4', 'h-4', 'w-5', 'h-5', 'w-6', 'h-6');
        
        // Add appropriate icon class based on context
        if (svg.closest('.btn-primary') || svg.closest('.btn-secondary')) {
            svg.classList.add('icon-sm');
        } else if (svg.closest('table')) {
            svg.classList.add('icon-xs');
        } else if (svg.closest('.bottom-nav-item')) {
            svg.classList.add('icon-md');
        } else if (svg.closest('.sidebar') || svg.closest('aside')) {
            svg.classList.add('icon-md');
        } else if (svg.closest('header')) {
            svg.classList.add('icon-md');
        } else if (svg.closest('.modal') || svg.closest('.modal-backdrop')) {
            svg.classList.add('icon-md');
        } else {
            svg.classList.add('icon-sm');
        }
        
        // Force icon size with inline styles as backup
        if (svg.classList.contains('icon-sm')) {
            svg.style.width = '1rem';
            svg.style.height = '1rem';
            svg.style.minWidth = '1rem';
            svg.style.minHeight = '1rem';
        } else if (svg.classList.contains('icon-md')) {
            svg.style.width = '1.25rem';
            svg.style.height = '1.25rem';
            svg.style.minWidth = '1.25rem';
            svg.style.minHeight = '1.25rem';
        } else if (svg.classList.contains('icon-xs')) {
            svg.style.width = '0.875rem';
            svg.style.height = '0.875rem';
            svg.style.minWidth = '0.875rem';
            svg.style.minHeight = '0.875rem';
        }
        
        // Ensure flex-shrink is set
        svg.style.flexShrink = '0';
    });
    
    // Fix specific kunjungan page icons
    const kunjunganPage = document.querySelector('.kunjungan-page');
    if (kunjunganPage) {
        const kunjunganSvgs = kunjunganPage.querySelectorAll('svg');
        kunjunganSvgs.forEach(function(svg) {
            if (svg.closest('.btn')) {
                svg.style.width = '1rem';
                svg.style.height = '1rem';
                svg.style.minWidth = '1rem';
                svg.style.minHeight = '1rem';
            } else if (svg.closest('table')) {
                svg.style.width = '0.875rem';
                svg.style.height = '0.875rem';
                svg.style.minWidth = '0.875rem';
                svg.style.minHeight = '0.875rem';
            }
            svg.style.flexShrink = '0';
        });
    }
}

// Function to fix icons after modal is shown
function fixModalIcons() {
    setTimeout(function() {
        const modalSvgs = document.querySelectorAll('.modal svg, .modal-backdrop svg');
        modalSvgs.forEach(function(svg) {
            svg.style.width = '1.25rem';
            svg.style.height = '1.25rem';
            svg.style.minWidth = '1.25rem';
            svg.style.minHeight = '1.25rem';
            svg.style.flexShrink = '0';
        });
    }, 100);
}

// Override showKunjunganModal to fix icons
if (typeof showKunjunganModal === 'function') {
    const originalShowKunjunganModal = showKunjunganModal;
    showKunjunganModal = function() {
        originalShowKunjunganModal();
        fixModalIcons();
    };
}

// Fix icons when page is resized
window.addEventListener('resize', function() {
    setTimeout(fixIconSizes, 100);
});

// Fix icons when content is loaded dynamically
document.addEventListener('click', function(e) {
    if (e.target.matches('button, .btn, a')) {
        setTimeout(fixIconSizes, 100);
    }
});

// Export function for manual fixing
window.fixIconSizes = fixIconSizes;
window.fixModalIcons = fixModalIcons;