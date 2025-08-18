// =============================
// DATA MANAGEMENT - LOAD FROM DATABASE
// Semua data sekarang diambil dari database MySQL melalui API PHP
// =============================

// Global data storage
let users = [];
let donaturData = [];
let kunjunganData = [];
let currentPage = 1;
let itemsPerPage = 10;
let totalRecords = 0;

// Data Manager Class
const DataManager = {
    // Load all data from database
    async loadData() {
        try {
            await Promise.all([
                this.loadUsers(),
                this.loadDonatur(),
                this.loadKunjungan()
            ]);
            console.log('All data loaded successfully');
        } catch (error) {
            console.error('Error loading data:', error);
            Utils.showNotification('Gagal memuat data dari database', 'error');
        }
    },

    // Load users from database
    async loadUsers() {
        try {
            const response = await fetch('api/users.php', {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
                const result = await response.json();
                if (result.success) {
                users = result.data || [];
                console.log('Users loaded:', users.length);
            } else {
                console.error('Failed to load users:', result.message);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    },

    // Load donatur from database
    async loadDonatur() {
        try {
            const response = await fetch('api/donatur.php', {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
                const result = await response.json();
                if (result.success) {
                donaturData = result.data || [];
                console.log('Donatur loaded:', donaturData.length);
            } else {
                console.error('Failed to load donatur:', result.message);
            }
        } catch (error) {
            console.error('Error loading donatur:', error);
        }
    },

    // Load kunjungan from database
    async loadKunjungan() {
        try {
            const response = await fetch('api/kunjungan.php', {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
                const result = await response.json();
                if (result.success) {
                kunjunganData = result.data || [];
                console.log('Kunjungan loaded:', kunjunganData.length);
            } else {
                console.error('Failed to load kunjungan:', result.message);
            }
        } catch (error) {
            console.error('Error loading kunjungan:', error);
        }
    },

    // Get users
    getUsers() {
        return users;
    },

    // Get donatur
    getDonatur() {
        return donaturData;
    },

    // Get kunjungan
    getKunjungan() {
        return kunjunganData;
    },

    // Get user by ID
    getUserById(id) {
        return users.find(user => user.id == id);
    },

    // Get donatur by ID
    getDonaturById(id) {
        return donaturData.find(donatur => donatur.id == id);
    },

    // Get kunjungan by ID
    getKunjunganById(id) {
        return kunjunganData.find(kunjungan => kunjungan.id == id);
    },

    // Filter users
    filterUsers(filters = {}) {
        let filtered = [...users];
        
        if (filters.search) {
            const search = filters.search.toLowerCase();
            filtered = filtered.filter(user => 
                user.nama.toLowerCase().includes(search) ||
                user.email.toLowerCase().includes(search) ||
                user.hp.includes(search)
            );
        }
        
        if (filters.status) {
            filtered = filtered.filter(user => user.status === filters.status);
        }
        
        return filtered;
    },

    // Filter donatur
    filterDonatur(filters = {}) {
        let filtered = [...donaturData];
        
        if (filters.search) {
            const search = filters.search.toLowerCase();
            filtered = filtered.filter(donatur => 
                donatur.nama.toLowerCase().includes(search) ||
                donatur.email.toLowerCase().includes(search) ||
                donatur.hp.includes(search)
            );
        }
        
        if (filters.kategori) {
            filtered = filtered.filter(donatur => donatur.kategori === filters.kategori);
        }
        
        return filtered;
    },

    // Filter kunjungan
    filterKunjungan(filters = {}) {
        let filtered = [...kunjunganData];
        
        if (filters.dateStart) {
            filtered = filtered.filter(kunjungan => 
                new Date(kunjungan.created_at) >= new Date(filters.dateStart)
            );
        }
        
        if (filters.dateEnd) {
            filtered = filtered.filter(kunjungan => 
                new Date(kunjungan.created_at) <= new Date(filters.dateEnd)
            );
        }
        
        if (filters.status) {
            filtered = filtered.filter(kunjungan => kunjungan.status === filters.status);
        }
        
        if (filters.fundraiser) {
            filtered = filtered.filter(kunjungan => kunjungan.user_id == filters.fundraiser);
        }
        
        if (filters.donatur) {
            filtered = filtered.filter(kunjungan => kunjungan.donatur_id == filters.donatur);
        }
        
        return filtered;
    }
};

// Legacy functions for backward compatibility
function getUsers() {
    return DataManager.getUsers();
}

function getDonatur() {
    return DataManager.getDonatur();
}

function getKunjungan() {
    return DataManager.getKunjungan();
}

function getUserById(id) {
    return DataManager.getUserById(id);
}

function getDonaturById(id) {
    return DataManager.getDonaturById(id);
}

function getKunjunganById(id) {
    return DataManager.getKunjunganById(id);
}