// Main Application

// Global variables
let currentPage = 1;
let itemsPerPage = 10;
let currentSort = { field: null, direction: 'asc' };
let currentFilters = {};
let realTimeInterval = null;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Refresh all data and update UI
async function refreshAllData() {
    try {
        Utils.showNotification('Memuat data terbaru...', 'info');
        
        // Load all data from database
        await DataManager.loadData();
        
        // Update dashboard stats
        await updateDashboardStats();
        
        // Update all tables if they exist
        if (typeof renderKunjunganTable === 'function') {
            renderKunjunganTable();
        }
        
        if (typeof renderDonaturTable === 'function') {
            renderDonaturTable();
        }
        
        if (typeof renderUsersTable === 'function') {
            renderUsersTable();
        }
        
        // Update charts if they exist
        if (typeof initializeCharts === 'function') {
            initializeCharts();
        }
        
        Utils.showNotification('Data berhasil diperbarui!', 'success', 3000);
        
    } catch (error) {
        console.error('Error refreshing data:', error);
        Utils.showNotification('Gagal memperbarui data', 'error');
    }
}

// Initialize app with proper data loading
async function initializeApp() {
    try {
        // Load data from backend API
        await DataManager.loadData();
        
        // Initialize UI
        showSection('dashboard');
        populateSelects();
        setupEventListeners();
        updateLastUpdated();
        
        // Update dashboard stats immediately
        await updateDashboardStats();
        
        // Start real-time updates
        setInterval(updateLastUpdated, 60000); // Update every minute
        
        // Initialize charts
        initializeCharts();
        
        // Show welcome message
        Utils.showNotification('Selamat datang di Sistem Fundraising!', 'success', 3000);
        
        // Log data status
        console.log('Data loaded:', {
            users: DataManager.getUsers().length,
            donatur: DataManager.getDonatur().length,
            kunjungan: DataManager.getKunjungan().length
        });
        
    } catch (error) {
        console.error('Error initializing app:', error);
        Utils.showNotification('Gagal menginisialisasi aplikasi', 'error');
    }
}

function setupEventListeners() {
    // Status change handler for kunjungan form
    document.addEventListener('change', function(e) {
        if (e.target.id === 'kunjungan-status') {
            const nominalField = document.getElementById('nominal-field');
            const followUpField = document.getElementById('follow-up-field');
            
            if (e.target.value === 'berhasil') {
                nominalField.classList.remove('hidden');
                followUpField.classList.add('hidden');
                document.getElementById('kunjungan-nominal').required = true;
                document.getElementById('kunjungan-follow-up').required = false;
            } else if (e.target.value === 'follow-up') {
                nominalField.classList.add('hidden');
                followUpField.classList.remove('hidden');
                document.getElementById('kunjungan-nominal').required = false;
                document.getElementById('kunjungan-follow-up').required = true;
            } else {
                nominalField.classList.add('hidden');
                followUpField.classList.add('hidden');
                document.getElementById('kunjungan-nominal').required = false;
                document.getElementById('kunjungan-follow-up').required = false;
            }
        }
    });

    // Photo preview
    document.addEventListener('change', function(e) {
        if (e.target.id === 'kunjungan-foto') {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    Utils.showNotification('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('photo-preview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }
    });

    // Search functionality
    document.addEventListener('input', Utils.debounce(function(e) {
        if (e.target.id === 'search-donatur') {
            filterDonatur();
        }
    }, 300));

    // Form submissions
    document.getElementById('kunjungan-form').addEventListener('submit', handleKunjunganSubmit);
    document.getElementById('donatur-form').addEventListener('submit', handleDonaturSubmit);
    document.getElementById('user-form').addEventListener('submit', handleUserSubmit);

    // Mobile sidebar toggle
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#sidebar') && !e.target.closest('.mobile-menu-btn')) {
            document.getElementById('sidebar').classList.remove('mobile-open');
        }
    });

    // Phone number validation
    document.addEventListener('input', function(e) {
        if (e.target.pattern && e.target.pattern.includes('[0-9]')) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        }
    });
}

function toggleMobileSidebar() {
    document.getElementById('sidebar').classList.toggle('mobile-open');
}

function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remove active class from all nav links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('sidebar-active', 'text-white');
    });
    
    // Show selected section
    const section = document.getElementById(sectionName + '-section');
    if (section) {
        section.classList.remove('hidden');
        section.classList.add('fade-in');
    }
    
    // Add active class to selected nav
    const navElement = document.getElementById('nav-' + sectionName);
    if (navElement) {
        navElement.classList.add('sidebar-active', 'text-white');
    }

    // Close mobile sidebar
    document.getElementById('sidebar').classList.remove('mobile-open');

    // Update section-specific data
    switch (sectionName) {
        case 'dashboard':
            updateDashboardStats();
            updateProgressBars();
            updateRecentActivities();
            updatePerformanceChart();
            break;
        case 'donatur':
            updateDonaturStats();
            renderDonaturTable();
            break;
        case 'target':
            updateTargetStats();
            break;
        case 'analytics':
            setTimeout(() => {
                initializeAnalyticsCharts();
            }, 100);
            break;
        case 'kunjungan':
            renderKunjunganTable();
            break;
        case 'users':
            renderUsersTable();
            break;
    }
}

// Populate select dropdowns with data from API
function populateSelects() {
    // Populate fundraiser select
    const fundraiserSelect = document.getElementById('fundraiser');
    const editFundraiserSelect = document.getElementById('edit-fundraiser');
    
    if (fundraiserSelect) {
        fundraiserSelect.innerHTML = '<option value="">Pilih Fundraiser</option>';
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.nama || user.name;
            fundraiserSelect.appendChild(option);
        });
    }
    
    if (editFundraiserSelect) {
        editFundraiserSelect.innerHTML = '<option value="">Pilih Fundraiser</option>';
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.nama || user.name;
            editFundraiserSelect.appendChild(option);
        });
    }

    // Populate donatur select
    const donaturSelect = document.getElementById('donatur');
    const editDonaturSelect = document.getElementById('edit-donatur');
    
    if (donaturSelect) {
        donaturSelect.innerHTML = '<option value="">Pilih Donatur</option>';
        donaturData.forEach(donatur => {
            const option = document.createElement('option');
            option.value = donatur.id;
            option.textContent = `${donatur.nama} (${donatur.hp})`;
            donaturSelect.appendChild(option);
        });
    }
    
    if (editDonaturSelect) {
        editDonaturSelect.innerHTML = '<option value="">Pilih Donatur</option>';
        donaturData.forEach(donatur => {
            const option = document.createElement('option');
            option.value = donatur.id;
            option.textContent = `${donatur.nama} (${donatur.hp})`;
            editDonaturSelect.appendChild(option);
        });
    }

    // Populate user role select
    const roleSelects = document.querySelectorAll('select[name="role"]');
    roleSelects.forEach(select => {
        if (select) {
            select.innerHTML = `
                <option value="">Pilih Role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
                <option value="monitor">Monitor</option>
            `;
        }
    });
}

// Update dashboard statistics
function updateDashboardStats() {
    // Calculate today's statistics
    const today = new Date().toDateString();
    const todayKunjungan = kunjunganData.filter(k => {
        const kunjunganDate = new Date(k.timestamp || k.waktu).toDateString();
        return kunjunganDate === today;
    });
    
    const totalKunjunganHariIni = todayKunjungan.length;
    const berhasilKunjungan = todayKunjungan.filter(k => k.status === 'berhasil').length;
    const totalDonasiHariIni = todayKunjungan
        .filter(k => k.status === 'berhasil')
        .reduce((sum, k) => sum + (parseInt(k.nominal) || 0), 0);
    
    // Calculate this month's statistics
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const thisMonthKunjungan = kunjunganData.filter(k => {
        const kunjunganDate = new Date(k.timestamp || k.waktu);
        return kunjunganDate.getMonth() === currentMonth && kunjunganDate.getFullYear() === currentYear;
    });
    
    const totalKunjunganBulanIni = thisMonthKunjungan.length;
    const totalDonasiBulanIni = thisMonthKunjungan
        .filter(k => k.status === 'berhasil')
        .reduce((sum, k) => sum + (parseInt(k.nominal) || 0), 0);
    
    // Calculate fundraiser stats
    const fundraiserAktif = users.filter(u => u.status === 'aktif').length;
    const totalFundraiser = users.length;
    
    // Update dashboard elements
    const elements = {
        'total-kunjungan': totalKunjunganHariIni,
        'donasi-berhasil': berhasilKunjungan,
        'total-donasi-hari-ini': Utils.formatCurrency(totalDonasiHariIni),
        'fundraiser-aktif': fundraiserAktif,
        'total-fundraiser': totalFundraiser
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id];
        }
    });
    
    // Update progress bars
    updateProgressBars();
    
    // Update recent activities
    updateRecentActivities();
}

// Update progress bars
function updateProgressBars() {
    const progressContainer = document.getElementById('progress-container');
    if (!progressContainer) return;
    
    progressContainer.innerHTML = '';
    
    users.forEach(user => {
        const todayKunjungan = kunjunganData.filter(k => {
            const kunjunganDate = new Date(k.timestamp || k.waktu);
            const today = new Date();
            const fundraiserId = k.fundraiserId || k.fundraiser;
            return kunjunganDate.toDateString() === today.toDateString() && fundraiserId == user.id;
        }).length;
        
        const progress = Math.min((todayKunjungan / user.target) * 100, 100);
        const progressColor = progress >= 100 ? 'bg-green-600' : progress >= 75 ? 'bg-blue-600' : progress >= 50 ? 'bg-yellow-600' : 'bg-red-600';
        
        const progressItem = document.createElement('div');
        progressItem.className = 'mb-4';
        progressItem.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">${user.nama}</span>
                <span class="text-sm text-gray-500">${todayKunjungan}/${user.target}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="${progressColor} h-2 rounded-full transition-all duration-500" style="width: ${Math.min(progress, 100)}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">${Math.round(progress)}% tercapai</div>
        `;
        progressContainer.appendChild(progressItem);
    });
}

// Update recent activities
function updateRecentActivities() {
    const recentActivities = kunjunganData
        .sort((a, b) => new Date(b.timestamp || b.waktu) - new Date(a.timestamp || a.waktu))
        .slice(0, 8);
    
    const activitiesContainer = document.getElementById('recent-activities');
    if (!activitiesContainer) return;
    
    if (recentActivities.length === 0) {
        activitiesContainer.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p>Belum ada aktivitas kunjungan</p>
            </div>
        `;
        return;
    }
    
    activitiesContainer.innerHTML = recentActivities.map(activity => {
        const fundraiser = users.find(u => u.id == (activity.fundraiserId || activity.fundraiser));
        const statusColor = Utils.getStatusColor(activity.status);
        const statusText = Utils.getStatusText(activity.status);
        const donaturName = activity.donaturNama || activity.donatur || 'Tidak diketahui';
        
        return `
            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg mb-2">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-${statusColor}-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-${statusColor}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        ${fundraiser?.nama || 'Tidak diketahui'} → ${donaturName}
                    </p>
                    <p class="text-sm text-gray-500">
                        ${Utils.getTimeAgo(activity.timestamp || activity.waktu)} • ${statusText}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${statusColor}-100 text-${statusColor}-800">
                        ${activity.nominal ? Utils.formatCurrency(activity.nominal) : '-'}
                    </span>
                </div>
            </div>
        `;
    }).join('');
}

// Update statistics functions
function updateDonaturStats() {
    const totalDonatur = donaturData.length;
    const donaturAktif = donaturData.filter(d => d.status === 'aktif').length;
    const donaturBaruBulanIni = donaturData.filter(d => {
        const createdDate = new Date(d.timestamp);
        const now = new Date();
        return createdDate.getMonth() === now.getMonth() && createdDate.getFullYear() === now.getFullYear();
    }).length;
    const rataRataDonasi = donaturData.length > 0 ? 
        donaturData.reduce((sum, d) => sum + (parseInt(d.totalDonasi) || 0), 0) / donaturData.length : 0;
    
    // Update display
    const elements = {
        'total-donatur-count': totalDonatur,
        'donatur-aktif-count': donaturAktif,
        'donatur-baru-bulan-ini': donaturBaruBulanIni,
        'rata-rata-donasi': Utils.formatCurrency(rataRataDonasi)
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id];
        }
    });
}

function updateTargetStats() {
    const targetGlobal = settings.targetGlobal || 8;
    const targetDonasi = settings.targetDonasi || 1000000;
    const targetDonaturBaru = settings.targetDonaturBaru || 50;
    
    // Calculate current progress
    const totalKunjunganHariIni = kunjunganData.filter(k => {
        const kunjunganDate = new Date(k.timestamp);
        const today = new Date();
        return kunjunganDate.toDateString() === today.toDateString();
    }).length;
    
    const totalDonasiHariIni = kunjunganData.filter(k => {
        const kunjunganDate = new Date(k.timestamp);
        const today = new Date();
        return kunjunganDate.toDateString() === today.toDateString() && k.status === 'berhasil';
    }).reduce((sum, k) => sum + (parseInt(k.nominal) || 0), 0);
    
    const donaturBaruBulanIni = donaturData.filter(d => {
        const createdDate = new Date(d.timestamp);
        const now = new Date();
        return createdDate.getMonth() === now.getMonth() && createdDate.getFullYear() === now.getFullYear();
    }).length;
    
    // Update progress bars
    const kunjunganProgress = Math.min((totalKunjunganHariIni / targetGlobal) * 100, 100);
    const donasiProgress = Math.min((totalDonasiHariIni / targetDonasi) * 100, 100);
    const donaturProgress = Math.min((donaturBaruBulanIni / targetDonaturBaru) * 100, 100);
    
    // Update display elements
    const elements = {
        'target-tercapai': `${totalKunjunganHariIni}/${targetGlobal}`,
        'dalam-progress': `${Math.floor(kunjunganProgress)}%`,
        'perlu-perhatian': `${Math.floor(100 - kunjunganProgress)}%`,
        'rata-rata-pencapaian': `${Math.floor((kunjunganProgress + donasiProgress + donaturProgress) / 3)}%`
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id];
        }
    });
}

// Modal Functions
function showKunjunganModal() {
    const modal = document.getElementById('kunjungan-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    populateSelects();
    setTodayDate();
}

function hideKunjunganModal() {
    const modal = document.getElementById('kunjungan-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('kunjungan-form').reset();
    document.getElementById('photo-preview').classList.add('hidden');
}

function showDonaturModal() {
    const modal = document.getElementById('donatur-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideDonaturModal() {
    const modal = document.getElementById('donatur-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('donatur-form').reset();
}

function showUserModal() {
    const modal = document.getElementById('user-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideUserModal() {
    const modal = document.getElementById('user-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('user-form').reset();
}

// Form Submission Handlers
async function handleKunjunganSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const kunjunganData = {
        fundraiser_id: parseInt(formData.get('fundraiser')),
        donatur_id: parseInt(formData.get('donatur')),
        alamat: formData.get('alamat'),
        lokasi: formData.get('lokasi'),
        nominal: parseInt(formData.get('nominal')) || 0,
        status: formData.get('status'),
        foto: formData.get('foto').name || '',
        catatan: formData.get('catatan'),
        follow_up_date: formData.get('follow-up-date') || null
    };

    try {
        await DataManager.addKunjungan(kunjunganData);
        e.target.reset();
        document.getElementById('kunjungan-modal').classList.add('hidden');
        Utils.showNotification('Kunjungan berhasil ditambahkan!', 'success');
        
        // Refresh dashboard
        refreshDashboard();
    } catch (error) {
        console.error('Error submitting kunjungan:', error);
    }
}

async function handleDonaturSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const donaturData = {
        nama: formData.get('nama'),
        hp: formData.get('hp'),
        email: formData.get('email'),
        alamat: formData.get('alamat'),
        kategori: formData.get('kategori')
    };

    try {
        await DataManager.addDonatur(donaturData);
        e.target.reset();
        document.getElementById('donatur-modal').classList.add('hidden');
        Utils.showNotification('Donatur berhasil ditambahkan!', 'success');
        
        // Refresh dashboard
        refreshDashboard();
    } catch (error) {
        console.error('Error submitting donatur:', error);
    }
}

async function handleUserSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role')
    };

    try {
        await DataManager.addUser(userData);
        e.target.reset();
        document.getElementById('user-modal').classList.add('hidden');
        Utils.showNotification('User berhasil ditambahkan!', 'success');
        
        // Refresh dashboard
        refreshDashboard();
    } catch (error) {
        console.error('Error submitting user:', error);
    }
}

// Action functions
function exportData() {
    const currentSection = Utils.getCurrentSection();
    let data = [];
    let filename = '';
    
    switch (currentSection) {
        case 'kunjungan':
            data = kunjunganData;
            filename = 'data-kunjungan';
            break;
        case 'donatur':
            data = donaturData;
            filename = 'data-donatur';
            break;
        case 'users':
            data = users;
            filename = 'data-fundraiser';
            break;
        default:
            data = {
                kunjungan: kunjunganData,
                donatur: donaturData,
                users: users,
                settings: settings
            };
            filename = 'data-fundraising-lengkap';
    }
    
    Utils.exportData(data, filename, 'json');
}

// Refresh dashboard data
async function refreshDashboard() {
    try {
        Utils.showNotification('Memperbarui data...', 'info');
        
        // Refresh data from API
        await DataManager.refreshData();
        
        // Update UI components
        if (typeof renderKunjunganTable === 'function') renderKunjunganTable();
        if (typeof renderDonaturTable === 'function') renderDonaturTable();
        if (typeof renderUsersTable === 'function') renderUsersTable();
        if (typeof updateDashboardStats === 'function') updateDashboardStats();
        if (typeof updateDonaturStats === 'function') updateDonaturStats();
        if (typeof populateSelects === 'function') populateSelects();
        
        // Update charts if they exist
        if (typeof updateCharts === 'function') updateCharts();
        
        Utils.showNotification('Dashboard berhasil diperbarui!', 'success');
    } catch (error) {
        console.error('Error refreshing dashboard:', error);
        Utils.showNotification('Gagal memperbarui dashboard', 'error');
    }
}

// Simulate real-time updates
function simulateRealTime() {
    // Simulate new kunjungan data
    const newKunjungan = {
        id: DataManager.getNextId('kunjungan'),
        fundraiser: users[Math.floor(Math.random() * users.length)].id,
        donatur: `Donatur ${Math.floor(Math.random() * 1000)}`,
        hp: `08${Math.floor(Math.random() * 900000000) + 100000000}`,
        alamat: `Alamat ${Math.floor(Math.random() * 100)}`,
        status: ['berhasil', 'tidak-berhasil', 'follow-up'][Math.floor(Math.random() * 3)],
        nominal: Math.floor(Math.random() * 5000000) + 100000,
        lokasi: `${Math.random() * 180 - 90}, ${Math.random() * 360 - 180}`,
        foto: 'sample-photo.jpg',
        catatan: 'Simulasi data real-time',
        timestamp: new Date().toISOString()
    };
    
    // Add to data
    kunjunganData.unshift(newKunjungan);
    DataManager.saveData();
    
    // Update UI
    renderKunjunganTable();
    updateDashboardStats();
    
    Utils.showNotification('Data real-time berhasil ditambahkan', 'success');
}

// Change chart period
function changeChartPeriod(period) {
    // Update active period button
    document.querySelectorAll('.chart-period-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });
    
    const activeBtn = document.querySelector(`[data-period="${period}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('bg-gray-200', 'text-gray-700');
        activeBtn.classList.add('bg-blue-600', 'text-white');
    }
    
    // Update chart data based on period
    updatePerformanceChart(period);
    
    Utils.showNotification(`Chart diperbarui ke periode ${period}`, 'info');
}

// Export functions to global scope
window.showSection = showSection;
window.toggleMobileSidebar = toggleMobileSidebar;
window.showKunjunganModal = showKunjunganModal;
window.hideKunjunganModal = hideKunjunganModal;
window.showDonaturModal = showDonaturModal;
window.hideDonaturModal = hideDonaturModal;
window.showUserModal = showUserModal;
window.hideUserModal = hideUserModal;
window.exportData = exportData;
window.refreshDashboard = refreshDashboard;
window.simulateRealTime = simulateRealTime;
window.updateLastUpdated = Utils.updateLastUpdated;
window.setTodayDate = Utils.setTodayDate; 

// =============================
// FUNGSI INSERT DATA DUMMY KE DATABASE (ADMIN ONLY)
// Akan mengirim seluruh data dummy ke backend via API
// =============================
function insertAllDummyToDatabase() {
    if (!confirm('Yakin ingin meng-insert seluruh data dummy ke database? Data ini hanya untuk demo/testing!')) return;
    let successCount = 0, failCount = 0;
    let total = users.length + donaturData.length + kunjunganData.length;
    let done = 0;
    // Insert users
    users.forEach(user => {
        fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': generateCSRFToken() },
            body: JSON.stringify({
                name: user.nama || user.name,
                email: user.email,
                password: 'password', // default dummy
                role: user.role || 'user'
            })
        }).then(r => r.json()).then(res => {
            if (res.success) successCount++; else failCount++;
            if (++done === total) showDummyInsertResult(successCount, failCount);
        }).catch(() => { failCount++; if (++done === total) showDummyInsertResult(successCount, failCount); });
    });
    // Insert donatur
    donaturData.forEach(donatur => {
        fetch('api/donatur.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': generateCSRFToken() },
            body: JSON.stringify({
                nama: donatur.nama,
                hp: donatur.hp,
                email: donatur.email,
                alamat: donatur.alamat,
                kategori: donatur.kategori
            })
        }).then(r => r.json()).then(res => {
            if (res.success) successCount++; else failCount++;
            if (++done === total) showDummyInsertResult(successCount, failCount);
        }).catch(() => { failCount++; if (++done === total) showDummyInsertResult(successCount, failCount); });
    });
    // Insert kunjungan
    kunjunganData.forEach(kunjungan => {
        fetch('api/kunjungan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': generateCSRFToken() },
            body: JSON.stringify({
                fundraiser_id: kunjungan.fundraiserId || kunjungan.fundraiser,
                donatur_id: kunjungan.donaturId || kunjungan.donatur,
                alamat: kunjungan.alamat,
                lokasi: kunjungan.lokasi,
                nominal: kunjungan.nominal,
                status: kunjungan.status,
                foto: kunjungan.foto,
                catatan: kunjungan.catatan,
                follow_up_date: kunjungan.followUpDate
            })
        }).then(r => r.json()).then(res => {
            if (res.success) successCount++; else failCount++;
            if (++done === total) showDummyInsertResult(successCount, failCount);
        }).catch(() => { failCount++; if (++done === total) showDummyInsertResult(successCount, failCount); });
    });
}
function showDummyInsertResult(success, fail) {
    Utils.showNotification(`Insert data dummy selesai. Sukses: ${success}, Gagal: ${fail}`, fail === 0 ? 'success' : 'warning', 7000);
}
// =============================
// END FUNGSI INSERT DUMMY
// ============================= 

// =============================
// FUNGSI HAPUS DATA DUMMY DARI DATABASE (ADMIN ONLY)
// Akan menghapus seluruh data dummy dari backend via API
// =============================
function deleteAllDummyFromDatabase() {
    if (!confirm('Yakin ingin menghapus seluruh data dummy dari database? Data ini hanya untuk demo/testing!')) return;
    let successCount = 0, failCount = 0;
    let total = users.length + donaturData.length + kunjunganData.length;
    let done = 0;
    // Hapus kunjungan dummy
    kunjunganData.forEach(kunjungan => {
        fetch('api/kunjungan.php?id=' + (kunjungan.id), { method: 'DELETE', headers: { 'X-CSRF-Token': generateCSRFToken() } })
            .then(r => r.json()).then(res => {
                if (res.success) successCount++; else failCount++;
                if (++done === total) showDummyDeleteResult(successCount, failCount);
            }).catch(() => { failCount++; if (++done === total) showDummyDeleteResult(successCount, failCount); });
    });
    // Hapus donatur dummy
    donaturData.forEach(donatur => {
        fetch('api/donatur.php?id=' + (donatur.id), { method: 'DELETE', headers: { 'X-CSRF-Token': generateCSRFToken() } })
            .then(r => r.json()).then(res => {
                if (res.success) successCount++; else failCount++;
                if (++done === total) showDummyDeleteResult(successCount, failCount);
            }).catch(() => { failCount++; if (++done === total) showDummyDeleteResult(successCount, failCount); });
    });
    // Hapus user dummy (kecuali admin/monitor)
    users.forEach(user => {
        if (user.role === 'admin' || user.role === 'monitor') { done++; if (done === total) showDummyDeleteResult(successCount, failCount); return; }
        fetch('api/users.php?id=' + (user.id), { method: 'DELETE', headers: { 'X-CSRF-Token': generateCSRFToken() } })
            .then(r => r.json()).then(res => {
                if (res.success) successCount++; else failCount++;
                if (++done === total) showDummyDeleteResult(successCount, failCount);
            }).catch(() => { failCount++; if (++done === total) showDummyDeleteResult(successCount, failCount); });
    });
}
function showDummyDeleteResult(success, fail) {
    Utils.showNotification(`Hapus data dummy selesai. Sukses: ${success}, Gagal: ${fail}`, fail === 0 ? 'success' : 'warning', 7000);
}
// =============================
// END FUNGSI HAPUS DUMMY
// ============================= 

// Delete functions
async function deleteKunjungan(id) {
    if (confirm('Yakin ingin menghapus kunjungan ini?')) {
        try {
            await DataManager.deleteKunjungan(id);
            Utils.showNotification('Kunjungan berhasil dihapus', 'success');
            refreshDashboard();
        } catch (error) {
            console.error('Error deleting kunjungan:', error);
        }
    }
}

async function deleteDonatur(id) {
    if (confirm('Yakin ingin menghapus donatur ini?')) {
        try {
            await DataManager.deleteDonatur(id);
            Utils.showNotification('Donatur berhasil dihapus', 'success');
            refreshDashboard();
        } catch (error) {
            console.error('Error deleting donatur:', error);
        }
    }
}

async function deleteUser(id) {
    if (confirm('Yakin ingin menghapus user ini?')) {
        try {
            await DataManager.deleteUser(id);
            Utils.showNotification('User berhasil dihapus', 'success');
            refreshDashboard();
        } catch (error) {
            console.error('Error deleting user:', error);
        }
    }
}

// Edit functions
async function editKunjungan(id) {
    const kunjungan = kunjunganData.find(k => k.id == id);
    if (!kunjungan) {
        Utils.showNotification('Kunjungan tidak ditemukan', 'error');
        return;
    }

    // Populate form with existing data
    document.getElementById('edit-kunjungan-id').value = kunjungan.id;
    document.getElementById('edit-fundraiser').value = kunjungan.fundraiserId || kunjungan.fundraiser;
    document.getElementById('edit-donatur').value = kunjungan.donaturId || kunjungan.donatur;
    document.getElementById('edit-alamat').value = kunjungan.alamat;
    document.getElementById('edit-lokasi').value = kunjungan.lokasi;
    document.getElementById('edit-nominal').value = kunjungan.nominal;
    document.getElementById('edit-status').value = kunjungan.status;
    document.getElementById('edit-catatan').value = kunjungan.catatan;
    document.getElementById('edit-follow-up-date').value = kunjungan.followUpDate || '';

    // Show edit modal
    document.getElementById('edit-kunjungan-modal').classList.remove('hidden');
}

async function editDonatur(id) {
    const donatur = donaturData.find(d => d.id == id);
    if (!donatur) {
        Utils.showNotification('Donatur tidak ditemukan', 'error');
        return;
    }

    // Populate form with existing data
    document.getElementById('edit-donatur-id').value = donatur.id;
    document.getElementById('edit-donatur-nama').value = donatur.nama;
    document.getElementById('edit-donatur-hp').value = donatur.hp;
    document.getElementById('edit-donatur-email').value = donatur.email;
    document.getElementById('edit-donatur-alamat').value = donatur.alamat;
    document.getElementById('edit-donatur-kategori').value = donatur.kategori;

    // Show edit modal
    document.getElementById('edit-donatur-modal').classList.remove('hidden');
}

async function editUser(id) {
    const user = users.find(u => u.id == id);
    if (!user) {
        Utils.showNotification('User tidak ditemukan', 'error');
        return;
    }

    // Populate form with existing data
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-user-name').value = user.nama || user.name;
    document.getElementById('edit-user-email').value = user.email;
    document.getElementById('edit-user-role').value = user.role;

    // Show edit modal
    document.getElementById('edit-user-modal').classList.remove('hidden');
}

// Update functions
async function updateKunjungan(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const id = parseInt(formData.get('id'));
    const updateData = {
        fundraiser_id: parseInt(formData.get('fundraiser')),
        donatur_id: parseInt(formData.get('donatur')),
        alamat: formData.get('alamat'),
        lokasi: formData.get('lokasi'),
        nominal: parseInt(formData.get('nominal')) || 0,
        status: formData.get('status'),
        catatan: formData.get('catatan'),
        follow_up_date: formData.get('follow-up-date') || null
    };

    try {
        await DataManager.updateKunjungan(id, updateData);
        e.target.reset();
        document.getElementById('edit-kunjungan-modal').classList.add('hidden');
        Utils.showNotification('Kunjungan berhasil diupdate!', 'success');
        refreshDashboard();
    } catch (error) {
        console.error('Error updating kunjungan:', error);
    }
}

async function updateDonatur(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const id = parseInt(formData.get('id'));
    const updateData = {
        nama: formData.get('nama'),
        hp: formData.get('hp'),
        email: formData.get('email'),
        alamat: formData.get('alamat'),
        kategori: formData.get('kategori')
    };

    try {
        await DataManager.updateDonatur(id, updateData);
        e.target.reset();
        document.getElementById('edit-donatur-modal').classList.add('hidden');
        Utils.showNotification('Donatur berhasil diupdate!', 'success');
        refreshDashboard();
    } catch (error) {
        console.error('Error updating donatur:', error);
    }
}

async function updateUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const id = parseInt(formData.get('id'));
    const updateData = {
        name: formData.get('name'),
        email: formData.get('email'),
        role: formData.get('role')
    };

    // Only update password if provided
    if (formData.get('password')) {
        updateData.password = formData.get('password');
    }

    try {
        await DataManager.updateUser(id, updateData);
        e.target.reset();
        document.getElementById('edit-user-modal').classList.add('hidden');
        Utils.showNotification('User berhasil diupdate!', 'success');
        refreshDashboard();
    } catch (error) {
        console.error('Error updating user:', error);
    }
} 

// Admin Functions for Dummy Data Management
async function insertDummyDataToDatabase() {
    try {
        Utils.showNotification('Memasukkan data dummy ke database...', 'info');
        
        const response = await fetch('api/dummy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'insert_dummy_data'
            })
        });

        const result = await response.json();
        
        if (result.success) {
            // Show detailed success message
            const details = result.details;
            Utils.showNotification(
                `✅ Data dummy berhasil dimasukkan!\n` +
                `• ${details.users_added} Fundraiser\n` +
                `• ${details.donatur_added} Donatur\n` +
                `• ${details.kunjungan_added} Kunjungan\n` +
                `\nData ini ditandai dengan [DUMMY] dan akan terlihat di dashboard.`, 
                'success', 
                8000
            );
            
            // Reload data and refresh dashboard
            await DataManager.loadData();
            await updateDashboardStats();
            if (typeof initializeCharts === 'function') {
                initializeCharts();
            }
        } else {
            Utils.showNotification(result.message || 'Gagal memasukkan data dummy', 'error');
        }
    } catch (error) {
        console.error('Error inserting dummy data:', error);
        Utils.showNotification('Terjadi kesalahan saat memasukkan data dummy', 'error');
    }
}

async function deleteAllDummyFromDatabase() {
    if (!confirm('⚠️ PERHATIAN!\n\nApakah Anda yakin ingin menghapus SEMUA data dummy dari database?\n\nData yang akan dihapus:\n• Semua fundraiser dengan email @dummy.com\n• Semua donatur dengan email @dummy\n• Semua kunjungan dengan catatan [DUMMY]\n\nTindakan ini TIDAK DAPAT DIBATALKAN!')) {
        return;
    }

    try {
        Utils.showNotification('Menghapus data dummy dari database...', 'info');
        
        const response = await fetch('api/dummy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'delete_dummy_data'
            })
        });

        const result = await response.json();
        
        if (result.success) {
            // Show detailed success message
            const details = result.details;
            Utils.showNotification(
                `✅ Data dummy berhasil dihapus!\n` +
                `• ${details.users_deleted} Fundraiser dihapus\n` +
                `• ${details.donatur_deleted} Donatur dihapus\n` +
                `• ${details.kunjungan_deleted} Kunjungan dihapus\n` +
                `\nDatabase sekarang bersih dari data dummy.`, 
                'success', 
                8000
            );
            
            // Reload data and refresh dashboard
            await DataManager.loadData();
            await updateDashboardStats();
            if (typeof initializeCharts === 'function') {
                initializeCharts();
            }
            
            // Remove warning if it exists
            const warning = document.getElementById('dummy-data-warning');
            if (warning) {
                warning.remove();
            }
        } else {
            Utils.showNotification(result.message || 'Gagal menghapus data dummy', 'error');
        }
    } catch (error) {
        console.error('Error deleting dummy data:', error);
        Utils.showNotification('Terjadi kesalahan saat menghapus data dummy', 'error');
    }
}

// Dashboard Stats Update Function
async function updateDashboardStats() {
    try {
        const response = await fetch('api/dashboard.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            // Update dashboard statistics
            document.getElementById('total-kunjungan').textContent = data.stats.total_kunjungan_hari_ini || 0;
            document.getElementById('donasi-berhasil').textContent = data.stats.donasi_berhasil_hari_ini || 0;
            document.getElementById('total-donasi-hari-ini').textContent = `Rp ${Utils.formatNumber(data.stats.total_donasi_hari_ini || 0)}`;
            document.getElementById('fundraiser-aktif').textContent = data.stats.fundraiser_aktif || 0;
            
            // Update progress bars if they exist
            if (data.progress && document.getElementById('progress-container')) {
                updateProgressBars(data.progress);
            }
            
            // Update recent activities if they exist
            if (data.recent_activities && document.getElementById('recent-activities')) {
                updateRecentActivities(data.recent_activities);
            }
            
            // Show dummy data warning if exists
            if (data.dummy_data_info && data.dummy_data_info.has_dummy_data) {
                showDummyDataWarning(data.dummy_data_info);
            }
        }
    } catch (error) {
        console.error('Error updating dashboard stats:', error);
    }
}

function updateProgressBars(progressData) {
    const container = document.getElementById('progress-container');
    if (!container) return;

    container.innerHTML = '';
    
    progressData.forEach(item => {
        const isDummy = item.is_dummy || item.name.includes('[DUMMY]');
        const progressHtml = `
            <div class="mb-4 ${isDummy ? 'border-l-4 border-orange-500 pl-4 bg-orange-50' : ''}">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium ${isDummy ? 'text-orange-700' : 'text-gray-700'}">${item.name}</span>
                    <span class="text-sm ${isDummy ? 'text-orange-600' : 'text-gray-500'}">${item.current}/${item.target}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="${isDummy ? 'bg-orange-500' : 'bg-blue-600'} h-2 rounded-full" style="width: ${Math.min((item.current / item.target) * 100, 100)}%"></div>
                </div>
                ${isDummy ? '<p class="text-xs text-orange-600 mt-1">⚠️ Data dummy - akan dihapus saat production</p>' : ''}
            </div>
        `;
        container.innerHTML += progressHtml;
    });
}

function updateRecentActivities(activities) {
    const container = document.getElementById('recent-activities');
    if (!container) return;

    container.innerHTML = '';
    
    activities.forEach(activity => {
        const isDummy = activity.is_dummy || activity.description.includes('[DUMMY]');
        const activityHtml = `
            <div class="flex items-center space-x-3 py-2 border-b border-gray-100 last:border-b-0 ${isDummy ? 'bg-orange-50 border-orange-200' : ''}">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 ${isDummy ? 'bg-orange-100' : 'bg-blue-100'} rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 ${isDummy ? 'text-orange-600' : 'text-blue-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium ${isDummy ? 'text-orange-800' : 'text-gray-900'}">${activity.description}</p>
                    <p class="text-xs ${isDummy ? 'text-orange-600' : 'text-gray-500'}">${activity.time}</p>
                    ${isDummy && activity.catatan ? `<p class="text-xs text-orange-600 mt-1">${activity.catatan}</p>` : ''}
                </div>
            </div>
        `;
        container.innerHTML += activityHtml;
    });
}

function showDummyDataWarning(dummyDataInfo) {
    // Check if warning already exists
    const existingWarning = document.getElementById('dummy-data-warning');
    if (existingWarning) {
        existingWarning.remove();
    }
    
    const warningHtml = `
        <div id="dummy-data-warning" class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-orange-800">
                        Data Dummy Terdeteksi
                    </h3>
                    <div class="mt-2 text-sm text-orange-700">
                        <p>${dummyDataInfo.warning_message}</p>
                        <ul class="mt-2 list-disc list-inside">
                            <li>Fundraiser Dummy: ${dummyDataInfo.dummy_users_count}</li>
                            <li>Donatur Dummy: ${dummyDataInfo.dummy_donatur_count}</li>
                            <li>Kunjungan Dummy: ${dummyDataInfo.dummy_kunjungan_count}</li>
                        </ul>
                        <p class="mt-2 font-medium">Gunakan tombol "Hapus Data Dummy" untuk membersihkan data ini.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insert warning after dashboard header
    const dashboardHeader = document.querySelector('.main-content .mb-6');
    if (dashboardHeader) {
        dashboardHeader.insertAdjacentHTML('afterend', warningHtml);
    }
}

// Make functions globally available
window.insertDummyDataToDatabase = insertDummyDataToDatabase;
window.deleteAllDummyFromDatabase = deleteAllDummyFromDatabase;
window.updateDashboardStats = updateDashboardStats;
window.refreshAllData = refreshAllData;

// Function to ensure data is loaded in all sections
function ensureDataLoaded() {
    // Check if data is loaded
    const users = DataManager.getUsers();
    const donatur = DataManager.getDonatur();
    const kunjungan = DataManager.getKunjungan();
    
    console.log('Data status:', {
        users: users.length,
        donatur: donatur.length,
        kunjungan: kunjungan.length
    });
    
    // If no data, try to load it
    if (users.length === 0 || donatur.length === 0) {
        console.log('No data found, loading from database...');
        DataManager.loadData().then(() => {
            console.log('Data loaded successfully');
            // Update UI after data is loaded
            updateAllSections();
        }).catch(error => {
            console.error('Failed to load data:', error);
        });
    } else {
        // Data exists, update UI
        updateAllSections();
    }
}

// Function to update all sections
function updateAllSections() {
    // Update dashboard
    updateDashboardStats();
    
    // Update tables if they exist
    if (typeof renderKunjunganTable === 'function') {
        renderKunjunganTable();
    }
    
    if (typeof renderDonaturTable === 'function') {
        renderDonaturTable();
    }
    
    if (typeof renderUsersTable === 'function') {
        renderUsersTable();
    }
    
    // Update charts if they exist
    if (typeof initializeCharts === 'function') {
        initializeCharts();
    }
    
    // Update selects
    populateSelects();
}

// Function to populate selects with data
function populateSelects() {
    const users = DataManager.getUsers();
    const donatur = DataManager.getDonatur();
    
    // Populate fundraiser select
    const fundraiserSelect = document.getElementById('fundraiser-select');
    if (fundraiserSelect) {
        fundraiserSelect.innerHTML = '<option value="">Pilih Fundraiser</option>';
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.nama;
            fundraiserSelect.appendChild(option);
        });
    }
    
    // Populate donatur select
    const donaturSelect = document.getElementById('donatur-select');
    if (donaturSelect) {
        donaturSelect.innerHTML = '<option value="">Pilih Donatur</option>';
        donatur.forEach(d => {
            const option = document.createElement('option');
            option.value = d.id;
            option.textContent = d.nama;
            donaturSelect.appendChild(option);
        });
    }
}

// Auto-refresh data every 30 seconds
setInterval(() => {
    if (document.visibilityState === 'visible') {
        DataManager.loadData().catch(error => {
            console.error('Auto-refresh failed:', error);
        });
    }
}, 30000);

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize app
    initializeApp();
    
    // Ensure data is loaded
    ensureDataLoaded();
    
    // Add refresh button event listener
    const refreshBtn = document.querySelector('[onclick="refreshAllData()"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshAllData);
    }
}); 