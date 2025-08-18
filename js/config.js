// Application Configuration
const CONFIG = {
    // Application settings
    APP_NAME: 'Sistem Absensi Fundraising Berbasis Kunjungan',
    VERSION: '2.1.0',
    AUTHOR: 'Admin Fundraising',
    
    // API endpoints
    API_BASE_URL: '',
    ENDPOINTS: {
        KUNJUNGAN: 'api/kunjungan.php',
        DONATUR: 'api/donatur.php',
        USERS: 'api/users.php',
        SETTINGS: 'api/settings.php',
        DUMMY: 'api/dummy.php'
    },
    
    // Local storage keys
    STORAGE_KEYS: {
        USERS: 'fundraising_users',
        DONATUR: 'fundraising_donatur',
        KUNJUNGAN: 'fundraising_kunjungan',
        SETTINGS: 'fundraising_settings',
        SESSION: 'fundraising_session'
    },
    
    // Default settings
    DEFAULT_SETTINGS: {
        targetGlobal: 8,
        targetDonasi: 1000000,
        targetDonaturBaru: 50,
        orgName: 'Yayasan Fundraising Indonesia',
        timezone: 'WIB',
        currencyFormat: 'IDR',
        itemsPerPage: 10,
        autoRefresh: true,
        notifications: {
            email: true,
            target: true,
            alert: false
        }
    },
    
    // Status options
    STATUS_OPTIONS: {
        KUNJUNGAN: [
            { value: 'berhasil', label: 'Berhasil (Ada Donasi)', color: 'success' },
            { value: 'tidak-berhasil', label: 'Tidak Berhasil', color: 'error' },
            { value: 'follow-up', label: 'Follow Up', color: 'warning' }
        ],
        DONATUR: [
            { value: 'aktif', label: 'Aktif', color: 'success' },
            { value: 'nonaktif', label: 'Nonaktif', color: 'error' },
            { value: 'prospek', label: 'Prospek', color: 'warning' }
        ],
        USER: [
            { value: 'aktif', label: 'Aktif', color: 'success' },
            { value: 'nonaktif', label: 'Nonaktif', color: 'error' },
            { value: 'cuti', label: 'Cuti', color: 'warning' }
        ]
    },
    
    // Donatur categories
    DONATUR_CATEGORIES: [
        { value: 'individu', label: 'Individu' },
        { value: 'perusahaan', label: 'Perusahaan' },
        { value: 'organisasi', label: 'Organisasi' }
    ],
    
    // Chart colors
    CHART_COLORS: {
        primary: '#3b82f6',
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#06b6d4',
        purple: '#8b5cf6',
        pink: '#ec4899',
        gray: '#6b7280'
    },
    
    // Validation rules
    VALIDATION: {
        PHONE: {
            pattern: /^[0-9]{10,13}$/,
            message: 'Nomor HP harus 10-13 digit angka'
        },
        EMAIL: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Format email tidak valid'
        },
        PASSWORD: {
            minLength: 6,
            message: 'Password minimal 6 karakter'
        },
        FILE_SIZE: {
            maxSize: 5 * 1024 * 1024, // 5MB
            message: 'Ukuran file maksimal 5MB'
        }
    },
    
    // Date formats
    DATE_FORMATS: {
        DISPLAY: 'DD/MM/YYYY',
        INPUT: 'YYYY-MM-DD',
        DATETIME: 'DD/MM/YYYY HH:mm',
        TIME: 'HH:mm'
    },
    
    // Currency formatting
    CURRENCY: {
        IDR: {
            symbol: 'Rp',
            position: 'before',
            decimal: 0,
            separator: '.',
            thousand: '.'
        },
        USD: {
            symbol: '$',
            position: 'before',
            decimal: 2,
            separator: '.',
            thousand: ','
        }
    },
    
    // Pagination
    PAGINATION: {
        DEFAULT_PAGE_SIZE: 10,
        PAGE_SIZE_OPTIONS: [5, 10, 25, 50, 100],
        MAX_PAGE_BUTTONS: 5
    },
    
    // Real-time settings
    REALTIME: {
        UPDATE_INTERVAL: 30000, // 30 seconds
        ENABLED: true,
        SIMULATION: false
    },
    
    // Export options
    EXPORT: {
        FORMATS: ['csv', 'xlsx', 'pdf'],
        DEFAULT_FORMAT: 'xlsx',
        MAX_RECORDS: 10000
    },
    
    // Notification settings
    NOTIFICATIONS: {
        AUTO_HIDE: true,
        HIDE_DELAY: 5000, // 5 seconds
        MAX_VISIBLE: 3,
        POSITION: 'top-right'
    },
    
    // Mobile breakpoints
    BREAKPOINTS: {
        SM: 640,
        MD: 768,
        LG: 1024,
        XL: 1280,
        XXL: 1536
    },
    
    // Performance settings
    PERFORMANCE: {
        DEBOUNCE_DELAY: 300,
        THROTTLE_DELAY: 100,
        LAZY_LOAD_OFFSET: 100,
        CACHE_DURATION: 5 * 60 * 1000 // 5 minutes
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
} 