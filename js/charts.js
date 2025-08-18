// Charts Configuration

let performanceChart = null;
let donationTrendChart = null;
let fundraiserPerformanceChart = null;

// Initialize charts
function initializeCharts() {
    updatePerformanceChart();
}

// Update performance chart
function updatePerformanceChart() {
    const ctx = document.getElementById('performance-chart');
    if (!ctx) return;
    
    if (performanceChart) {
        performanceChart.destroy();
    }
    
    // Generate sample data for the last 7 days
    const labels = [];
    const kunjunganData = [];
    const donasiData = [];
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(Utils.formatDate(date, 'DD/MM'));
        
        // Generate random data for demo
        kunjunganData.push(Math.floor(Math.random() * 20) + 10);
        donasiData.push(Math.floor(Math.random() * 5000000) + 1000000);
    }
    
    // Check if mobile device
    const isMobile = window.innerWidth <= 768;
    
    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Kunjungan',
                    data: kunjunganData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y',
                    borderWidth: isMobile ? 2 : 3,
                    pointRadius: isMobile ? 3 : 4,
                    pointHoverRadius: isMobile ? 5 : 6
                },
                {
                    label: 'Donasi (Juta)',
                    data: donasiData.map(d => d / 1000000),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1',
                    borderWidth: isMobile ? 2 : 3,
                    pointRadius: isMobile ? 3 : 4,
                    pointHoverRadius: isMobile ? 5 : 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: !isMobile,
                        text: 'Tanggal'
                    },
                    ticks: {
                        maxRotation: isMobile ? 45 : 0,
                        minRotation: isMobile ? 45 : 0,
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: !isMobile,
                        text: 'Jumlah Kunjungan'
                    },
                    ticks: {
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: !isMobile,
                        text: 'Donasi (Juta Rupiah)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: isMobile ? 'bottom' : 'top',
                    labels: {
                        padding: isMobile ? 10 : 20,
                        font: {
                            size: isMobile ? 11 : 12
                        },
                        usePointStyle: true
                    }
                },
                title: {
                    display: true,
                    text: 'Performa Mingguan',
                    font: {
                        size: isMobile ? 14 : 16
                    }
                },
                tooltip: {
                    titleFont: {
                        size: isMobile ? 12 : 14
                    },
                    bodyFont: {
                        size: isMobile ? 11 : 12
                    }
                }
            }
        }
    });
}

// Change chart period
function changeChartPeriod(period) {
    const weekBtn = document.getElementById('chart-week-btn');
    const monthBtn = document.getElementById('chart-month-btn');
    
    if (period === 'week') {
        weekBtn.className = 'px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg';
        monthBtn.className = 'px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-lg';
    } else {
        weekBtn.className = 'px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-lg';
        monthBtn.className = 'px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg';
    }
    
    updatePerformanceChart();
}

// Initialize analytics charts
function initializeAnalyticsCharts() {
    initializeDonationTrendChart();
    initializeFundraiserPerformanceChart();
}

// Initialize donation trend chart
function initializeDonationTrendChart() {
    const ctx = document.getElementById('donation-trend-chart');
    if (!ctx) return;
    
    if (donationTrendChart) {
        donationTrendChart.destroy();
    }
    
    // Generate sample data for the last 6 months
    const labels = ['Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    const data = [15000000, 18000000, 22000000, 25000000, 28000000, 32000000];
    
    // Check if mobile device
    const isMobile = window.innerWidth <= 768;
    
    donationTrendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Donasi Bulanan',
                data: data.map(d => d / 1000000),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(6, 182, 212, 0.8)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(6, 182, 212, 1)'
                ],
                borderWidth: 1,
                borderRadius: isMobile ? 2 : 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: !isMobile,
                        text: 'Donasi (Juta Rupiah)'
                    },
                    ticks: {
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Tren Donasi Bulanan',
                    font: {
                        size: isMobile ? 14 : 16
                    }
                },
                tooltip: {
                    titleFont: {
                        size: isMobile ? 12 : 14
                    },
                    bodyFont: {
                        size: isMobile ? 11 : 12
                    }
                }
            }
        }
    });
}

// Initialize fundraiser performance chart
function initializeFundraiserPerformanceChart() {
    const ctx = document.getElementById('fundraiser-performance-chart');
    if (!ctx) return;
    
    if (fundraiserPerformanceChart) {
        fundraiserPerformanceChart.destroy();
    }
    
    // Use actual user data
    const labels = users.map(u => u.nama.split(' ')[0]);
    const kunjunganData = users.map(u => u.totalKunjunganBulan);
    const donasiData = users.map(u => u.totalDonasiBulan / 1000000);
    
    // Check if mobile device
    const isMobile = window.innerWidth <= 768;
    
    fundraiserPerformanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: donasiData,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(6, 182, 212, 0.8)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(6, 182, 212, 1)'
                ],
                borderWidth: isMobile ? 1 : 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: isMobile ? 'bottom' : 'bottom',
                    labels: {
                        padding: isMobile ? 10 : 20,
                        usePointStyle: true,
                        font: {
                            size: isMobile ? 10 : 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Performa Fundraiser (Donasi Bulanan)',
                    font: {
                        size: isMobile ? 14 : 16
                    }
                },
                tooltip: {
                    titleFont: {
                        size: isMobile ? 12 : 14
                    },
                    bodyFont: {
                        size: isMobile ? 11 : 12
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            return `${label}: ${Utils.formatCurrency(value * 1000000)}`;
                        }
                    }
                }
            }
        }
    });
}

// Handle chart resize for mobile
function handleChartResize() {
    const isMobile = window.innerWidth <= 768;
    
    if (performanceChart) {
        performanceChart.resize();
    }
    if (donationTrendChart) {
        donationTrendChart.resize();
    }
    if (fundraiserPerformanceChart) {
        fundraiserPerformanceChart.resize();
    }
}

// Add resize listener
window.addEventListener('resize', handleChartResize);

// Refresh target progress
function refreshTargetProgress() {
    updateProgressBars();
    Utils.showNotification('Progress target berhasil diperbarui', 'success');
}

// Target management
async function updateTargetGlobal() {
    const targetGlobal = parseInt(document.getElementById('target-global').value);
    const targetDonasi = parseInt(document.getElementById('target-donasi').value);
    const targetDonaturBaru = parseInt(document.getElementById('target-donatur-baru').value);
    
    if (!targetGlobal || !targetDonasi || !targetDonaturBaru) {
        Utils.showNotification('Semua target harus diisi', 'error');
        return;
    }
    
    try {
        const settings = {
            targetGlobal: targetGlobal,
            targetDonasi: targetDonasi,
            targetDonaturBaru: targetDonaturBaru
        };
        
        await DataManager.updateSettings(settings);
        updateTargetStats();
        Utils.showNotification('Target global berhasil diupdate', 'success');
    } catch (error) {
        console.error('Error updating target global:', error);
        Utils.showNotification('Gagal mengupdate target global', 'error');
    }
}

// Report functions
function generateLaporan() {
    const bulan = document.getElementById('laporan-bulan').value;
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;
    
    // Calculate month difference
    const monthDiff = currentMonth - parseInt(bulan);
    const targetDate = new Date(currentYear, currentMonth - monthDiff - 1, 1);
    
    // Filter data for the selected month
    const monthData = kunjunganData.filter(item => {
        const itemDate = new Date(item.timestamp);
        return itemDate.getMonth() === targetDate.getMonth() && 
               itemDate.getFullYear() === targetDate.getFullYear();
    });
    
    // Calculate statistics
    const totalKunjungan = monthData.length;
    const totalDonasi = monthData.reduce((sum, item) => sum + (parseInt(item.nominal) || 0), 0);
    const berhasilKunjungan = monthData.filter(item => item.status === 'berhasil').length;
    const conversionRate = totalKunjungan > 0 ? Math.round((berhasilKunjungan / totalKunjungan) * 100) : 0;
    
    // Update display
    document.getElementById('laporan-total-kunjungan').textContent = totalKunjungan;
    document.getElementById('laporan-total-donasi').textContent = Utils.formatCurrency(totalDonasi);
    document.getElementById('laporan-conversion-rate').textContent = conversionRate + '%';
    
    Utils.showNotification('Laporan berhasil dibuat', 'success');
}

function exportLaporan() {
    const bulan = document.getElementById('laporan-bulan').value;
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;
    
    // Calculate month difference
    const monthDiff = currentMonth - parseInt(bulan);
    const targetDate = new Date(currentYear, currentMonth - monthDiff - 1, 1);
    
    // Filter data for the selected month
    const monthData = kunjunganData.filter(item => {
        const itemDate = new Date(item.timestamp);
        return itemDate.getMonth() === targetDate.getMonth() && 
               itemDate.getFullYear() === targetDate.getFullYear();
    });
    
    // Create report data
    const reportData = {
        periode: targetDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
        totalKunjungan: monthData.length,
        totalDonasi: monthData.reduce((sum, item) => sum + (parseInt(item.nominal) || 0), 0),
        berhasilKunjungan: monthData.filter(item => item.status === 'berhasil').length,
        tidakBerhasilKunjungan: monthData.filter(item => item.status === 'tidak-berhasil').length,
        followUpKunjungan: monthData.filter(item => item.status === 'follow-up').length,
        dataKunjungan: monthData
    };
    
    // Export as JSON
    const blob = new Blob([JSON.stringify(reportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `laporan-fundraising-${targetDate.getFullYear()}-${String(targetDate.getMonth() + 1).padStart(2, '0')}.json`;
    a.click();
    URL.revokeObjectURL(url);
    
    Utils.showNotification('Laporan berhasil diexport', 'success');
}

// Settings and system functions
function saveSettings() {
    const settings = {
        orgName: document.getElementById('org-name').value,
        timezone: document.getElementById('timezone').value,
        currencyFormat: document.getElementById('currency-format').value
    };
    
    DataManager.updateSettings(settings);
    Utils.showNotification('Pengaturan berhasil disimpan', 'success');
}

function backupData() {
    const data = {
        users: users,
        donaturData: donaturData,
        kunjunganData: kunjunganData,
        settings: settings,
        timestamp: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `fundraising-backup-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
    
    Utils.showNotification('Backup data berhasil dibuat', 'success');
}

function restoreData() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    if (confirm('Data akan diganti dengan backup. Lanjutkan?')) {
                        users = data.users || users;
                        donaturData = data.donaturData || donaturData;
                        kunjunganData = data.kunjunganData || kunjunganData;
                        settings = data.settings || settings;
                        
                        DataManager.saveData();
                        location.reload();
                    }
                } catch (error) {
                    Utils.showNotification('File backup tidak valid', 'error');
                }
            };
            reader.readAsText(file);
        }
    };
    input.click();
}

function resetSystem() {
    if (confirm('Semua data akan dihapus. Tindakan ini tidak dapat dibatalkan. Lanjutkan?')) {
        DataManager.resetData();
        location.reload();
    }
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                document.getElementById('kunjungan-lokasi').value = `${lat}, ${lng}`;
                Utils.showNotification('Lokasi berhasil diambil', 'success');
            },
            function(error) {
                Utils.showNotification('Gagal mengambil lokasi: ' + error.message, 'error');
            }
        );
    } else {
        Utils.showNotification('Geolocation tidak didukung di browser ini', 'error');
    }
}

function openCamera() {
    const input = document.getElementById('kunjungan-foto');
    input.click();
}

function importDonatur() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv,.xlsx';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            Utils.showNotification('Fitur import akan segera tersedia', 'info');
        }
    };
    input.click();
}

// Export functions to global scope
window.changeChartPeriod = changeChartPeriod;
window.refreshTargetProgress = refreshTargetProgress;
window.updateTargetGlobal = updateTargetGlobal;
window.generateLaporan = generateLaporan;
window.exportLaporan = exportLaporan;
window.saveSettings = saveSettings;
window.backupData = backupData;
window.restoreData = restoreData;
window.resetSystem = resetSystem;
window.getLocation = getLocation;
window.openCamera = openCamera;
window.importDonatur = importDonatur; 