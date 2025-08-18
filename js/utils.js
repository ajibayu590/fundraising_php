// Utility Functions

// Format currency
function formatCurrency(amount, currency = 'IDR') {
    if (!amount) return 'Rp 0';
    
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    return formatter.format(amount);
}

// Format number
function formatNumber(number) {
    if (!number) return '0';
    
    return new Intl.NumberFormat('id-ID').format(number);
}

// Format date
function formatDate(date, format = 'DD/MM/YYYY') {
    if (!date) return '';
    
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    
    switch (format) {
        case 'DD/MM/YYYY':
            return `${day}/${month}/${year}`;
        case 'YYYY-MM-DD':
            return `${year}-${month}-${day}`;
        case 'DD/MM/YYYY HH:mm':
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        case 'HH:mm':
            return `${hours}:${minutes}`;
        default:
            return d.toLocaleDateString('id-ID');
    }
}

// Get time ago
function getTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffInSeconds = Math.floor((now - past) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Baru saja';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} menit yang lalu`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} jam yang lalu`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} hari yang lalu`;
    } else {
        return formatDate(timestamp);
    }
}

// Debounce function
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Memory management functions
function cleanupIntervals() {
    if (window.dashboardInterval) {
        clearInterval(window.dashboardInterval);
        window.dashboardInterval = null;
    }
    if (window.realTimeInterval) {
        clearInterval(window.realTimeInterval);
        window.realTimeInterval = null;
    }
}

// Setup proper cleanup
if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', cleanupIntervals);
    window.addEventListener('pagehide', cleanupIntervals);
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Show notification
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('Notification container not found');
        return;
    }
    
    const notification = document.createElement('div');
    
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto hide
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, duration);
    }
}

// Validate form
function validateForm(formData, rules) {
    const errors = {};
    
    for (const [field, rule] of Object.entries(rules)) {
        const value = formData[field];
        
        if (rule.required && (!value || value.trim() === '')) {
            errors[field] = rule.message || `${field} wajib diisi`;
            continue;
        }
        
        if (rule.pattern && value && !rule.pattern.test(value)) {
            errors[field] = rule.message || `${field} tidak valid`;
            continue;
        }
        
        if (rule.minLength && value && value.length < rule.minLength) {
            errors[field] = rule.message || `${field} minimal ${rule.minLength} karakter`;
            continue;
        }
        
        if (rule.maxLength && value && value.length > rule.maxLength) {
            errors[field] = rule.message || `${field} maksimal ${rule.maxLength} karakter`;
            continue;
        }
        
        if (rule.min && value && parseFloat(value) < rule.min) {
            errors[field] = rule.message || `${field} minimal ${rule.min}`;
            continue;
        }
        
        if (rule.max && value && parseFloat(value) > rule.max) {
            errors[field] = rule.message || `${field} maksimal ${rule.max}`;
            continue;
        }
    }
    
    return errors;
}

// Generate random ID
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Local storage helpers
const Storage = {
    get(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return null;
        }
    },
    
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Error writing to localStorage:', error);
            return false;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Error removing from localStorage:', error);
            return false;
        }
    },
    
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Error clearing localStorage:', error);
            return false;
        }
    }
};

// Export data
function exportData(data, filename, format = 'csv') {
    let content = '';
    let mimeType = '';
    
    switch (format.toLowerCase()) {
        case 'csv':
            content = convertToCSV(data);
            mimeType = 'text/csv';
            break;
        case 'json':
            content = JSON.stringify(data, null, 2);
            mimeType = 'application/json';
            break;
        case 'xlsx':
            // For XLSX, you would need a library like SheetJS
            showNotification('Export XLSX memerlukan library tambahan', 'warning');
            return;
        default:
            showNotification('Format export tidak didukung', 'error');
            return;
    }
    
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${filename}.${format}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showNotification(`Data berhasil diexport ke ${filename}.${format}`, 'success');
}

// Convert data to CSV
function convertToCSV(data) {
    if (!data || data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => 
            headers.map(header => {
                const value = row[header];
                // Escape commas and quotes
                if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                    return `"${value.replace(/"/g, '""')}"`;
                }
                return value || '';
            }).join(',')
        )
    ].join('\n');
    
    return csvContent;
}

// Status utilities
function getStatusText(status) {
    const statusMap = {
        'berhasil': 'Berhasil',
        'tidak-berhasil': 'Tidak Berhasil',
        'follow-up': 'Follow Up',
        'aktif': 'Aktif',
        'nonaktif': 'Nonaktif',
        'prospek': 'Prospek',
        'cuti': 'Cuti'
    };
    return statusMap[status] || status;
}

function getStatusColor(status) {
    const colorMap = {
        'berhasil': 'green',
        'tidak-berhasil': 'red',
        'follow-up': 'yellow',
        'aktif': 'green',
        'nonaktif': 'red',
        'prospek': 'yellow',
        'cuti': 'blue'
    };
    return colorMap[status] || 'gray';
}

// Update last updated time
function updateLastUpdated() {
    const now = new Date();
    const formattedTime = now.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const element = document.getElementById('last-updated');
    if (element) {
        element.textContent = formattedTime;
    }
}

// Date utilities
function setTodayDate() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
}

// Check if device is mobile
function isMobile() {
    return window.innerWidth <= 768;
}

// Get current section
function getCurrentSection() {
    const sections = document.querySelectorAll('.section');
    for (const section of sections) {
        if (!section.classList.contains('hidden')) {
            return section.id.replace('-section', '');
        }
    }
    return 'dashboard';
}

// Export functions for use in other modules
window.Utils = {
    formatCurrency,
    formatNumber,
    formatDate,
    getTimeAgo,
    debounce,
    throttle,
    showNotification,
    validateForm,
    generateId,
    Storage,
    exportData,
    convertToCSV,
    getStatusText,
    getStatusColor,
    updateLastUpdated,
    setTodayDate,
    isMobile,
    getCurrentSection
}; 

// =============================
// CSRF Protection Utility (Frontend)
// =============================
// Generate CSRF token dan simpan di sessionStorage
function generateCSRFToken() {
    let token = sessionStorage.getItem('csrf_token');
    if (!token) {
        token = Math.random().toString(36).substring(2) + Date.now().toString(36);
        sessionStorage.setItem('csrf_token', token);
    }
    return token;
}
// Attach CSRF token ke form (hidden input)
function attachCSRFToken(form) {
    let token = generateCSRFToken();
    let input = form.querySelector('input[name="csrf_token"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        form.appendChild(input);
    }
    input.value = token;
}
// Untuk fetch, tambahkan header: {'X-CSRF-Token': generateCSRFToken()}
// =============================
// END CSRF Utility
// =============================

// Make showNotification available globally for backward compatibility
window.showNotification = showNotification; 