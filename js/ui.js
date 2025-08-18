// UI Components

// Render kunjungan table
function renderKunjunganTable(data = null) {
    const tbody = document.getElementById('kunjungan-table');
    const mobileContainer = document.getElementById('kunjungan-mobile');
    if (!tbody) return;
    
    const displayData = data || kunjunganData;
    
    tbody.innerHTML = '';
    if (mobileContainer) mobileContainer.innerHTML = '';
    
    displayData.forEach(kunjungan => {
        const fundraiser = users.find(u => u.id == (kunjungan.fundraiserId || kunjungan.fundraiser));
        const statusColor = Utils.getStatusColor(kunjungan.status);
        
        // Desktop table row
        const row = document.createElement('tr');
        row.className = 'table-row hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="kunjungan-checkbox" data-type="kunjungan" value="${kunjungan.id}">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-blue-600 font-medium">${fundraiser?.nama?.charAt(0) || '?'}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">${fundraiser?.nama || 'Tidak diketahui'}</div>
                        <div class="text-sm text-gray-500">ID: ${kunjungan.fundraiserId || kunjungan.fundraiser}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${kunjungan.donaturNama || kunjungan.donatur}</div>
                <div class="text-sm text-gray-500">${kunjungan.donaturHp || kunjungan.hp}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${kunjungan.alamat ? kunjungan.alamat.substring(0, 50) + '...' : '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${kunjungan.nominal > 0 ? Utils.formatCurrency(kunjungan.nominal) : '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                    ${Utils.getStatusText(kunjungan.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${Utils.formatDate(kunjungan.timestamp || kunjungan.waktu, 'DD/MM/YYYY HH:mm')}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewKunjungan(${kunjungan.id})" class="text-blue-600 hover:text-blue-900 mr-3">Lihat</button>
                <button onclick="editKunjungan(${kunjungan.id})" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                <button onclick="deleteKunjungan(${kunjungan.id})" class="text-red-600 hover:text-red-900">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Mobile card
        if (mobileContainer) {
            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-lg shadow mb-4';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-medium text-gray-900">${fundraiser?.nama || 'Tidak diketahui'}</h4>
                        <p class="text-sm text-gray-600">${kunjungan.donaturNama || kunjungan.donatur}</p>
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                        ${Utils.getStatusText(kunjungan.status)}
                    </span>
                </div>
                <div class="space-y-2 text-sm">
                    <p><span class="font-medium">Nominal:</span> ${kunjungan.nominal > 0 ? Utils.formatCurrency(kunjungan.nominal) : '-'}</p>
                    <p><span class="font-medium">Waktu:</span> ${Utils.formatDate(kunjungan.timestamp || kunjungan.waktu, 'DD/MM/YYYY HH:mm')}</p>
                    <p><span class="font-medium">Alamat:</span> ${kunjungan.alamat ? kunjungan.alamat.substring(0, 40) + '...' : '-'}</p>
                </div>
                <div class="flex justify-end space-x-2 mt-3">
                    <button onclick="viewKunjungan(${kunjungan.id})" class="text-blue-600 hover:text-blue-900 text-sm">Lihat</button>
                    <button onclick="editKunjungan(${kunjungan.id})" class="text-green-600 hover:text-green-900 text-sm">Edit</button>
                    <button onclick="deleteKunjungan(${kunjungan.id})" class="text-red-600 hover:text-red-900 text-sm">Hapus</button>
                </div>
            `;
            mobileContainer.appendChild(card);
        }
    });
    
    updateKunjunganCount(displayData.length);
}

// Render donatur table
function renderDonaturTable(data = null) {
    const tbody = document.getElementById('donatur-table');
    const mobileContainer = document.getElementById('donatur-mobile');
    if (!tbody) return;
    
    const displayData = data || donaturData;
    
    tbody.innerHTML = '';
    if (mobileContainer) mobileContainer.innerHTML = '';
    
    displayData.forEach(donatur => {
        const statusColor = donatur.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        
        // Desktop table row
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="donatur-checkbox" data-type="donatur" value="${donatur.id}">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-purple-600 font-medium">${donatur.nama.charAt(0)}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">${donatur.nama}</div>
                        <div class="text-sm text-gray-500">${donatur.kategori}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${donatur.hp}</div>
                <div class="text-sm text-gray-500">${donatur.email || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${donatur.alamat ? donatur.alamat.substring(0, 50) + '...' : '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${Utils.formatCurrency(donatur.totalDonasi || 0)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${donatur.terakhirDonasi ? Utils.formatDate(donatur.terakhirDonasi) : '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                    ${Utils.getStatusText(donatur.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewDonatur(${donatur.id})" class="text-blue-600 hover:text-blue-900 mr-3">Lihat</button>
                <button onclick="editDonatur(${donatur.id})" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                <button onclick="deleteDonatur(${donatur.id})" class="text-red-600 hover:text-red-900">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Mobile card
        if (mobileContainer) {
            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-lg shadow mb-4';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-medium text-gray-900">${donatur.nama}</h4>
                        <p class="text-sm text-gray-600">${donatur.kategori}</p>
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                        ${Utils.getStatusText(donatur.status)}
                    </span>
                </div>
                <div class="space-y-2 text-sm">
                    <p><span class="font-medium">HP:</span> ${donatur.hp}</p>
                    <p><span class="font-medium">Email:</span> ${donatur.email || '-'}</p>
                    <p><span class="font-medium">Total Donasi:</span> ${Utils.formatCurrency(donatur.totalDonasi || 0)}</p>
                    <p><span class="font-medium">Terakhir Donasi:</span> ${donatur.terakhirDonasi ? Utils.formatDate(donatur.terakhirDonasi) : '-'}</p>
                </div>
                <div class="flex justify-end space-x-2 mt-3">
                    <button onclick="viewDonatur(${donatur.id})" class="text-blue-600 hover:text-blue-900 text-sm">Lihat</button>
                    <button onclick="editDonatur(${donatur.id})" class="text-green-600 hover:text-green-900 text-sm">Edit</button>
                    <button onclick="deleteDonatur(${donatur.id})" class="text-red-600 hover:text-red-900 text-sm">Hapus</button>
                </div>
            `;
            mobileContainer.appendChild(card);
        }
    });
}

// Render users table
function renderUsersTable(data = null) {
    const tbody = document.getElementById('users-table');
    const mobileContainer = document.getElementById('users-mobile');
    if (!tbody) return;
    
    const displayData = data || users;
    
    tbody.innerHTML = '';
    if (mobileContainer) mobileContainer.innerHTML = '';
    
    displayData.forEach(user => {
        const progress = Math.round((user.kunjunganHariIni / user.target) * 100);
        const progressColor = progress >= 100 ? 'bg-green-600' : progress >= 75 ? 'bg-blue-600' : progress >= 50 ? 'bg-yellow-600' : 'bg-red-600';
        const statusColor = user.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        
        // Desktop table row
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="user-checkbox" data-type="users" value="${user.id}">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-blue-600 font-medium">${user.nama.charAt(0)}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">${user.nama}</div>
                        <div class="text-sm text-gray-500">${user.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${user.hp}</div>
                <div class="text-sm text-gray-500">${user.email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${user.target} kunjungan/hari
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                        <div class="${progressColor} h-2 rounded-full transition-all duration-500" style="width: ${Math.min(progress, 100)}%"></div>
                    </div>
                    <span class="text-sm text-gray-600">${user.kunjunganHariIni || 0}/${user.target}</span>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${user.performaBulanIni || 0}%
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                    ${Utils.getStatusText(user.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewUser(${user.id})" class="text-blue-600 hover:text-blue-900 mr-3">Lihat</button>
                <button onclick="editUser(${user.id})" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-900">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Mobile card
        if (mobileContainer) {
            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-lg shadow mb-4';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-medium text-gray-900">${user.nama}</h4>
                        <p class="text-sm text-gray-600">${user.email}</p>
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                        ${Utils.getStatusText(user.status)}
                    </span>
                </div>
                <div class="space-y-2 text-sm">
                    <p><span class="font-medium">HP:</span> ${user.hp}</p>
                    <p><span class="font-medium">Target:</span> ${user.target} kunjungan/hari</p>
                    <p><span class="font-medium">Progress Hari Ini:</span> ${user.kunjunganHariIni || 0}/${user.target}</p>
                    <p><span class="font-medium">Performa Bulan Ini:</span> ${user.performaBulanIni || 0}%</p>
                </div>
                <div class="flex justify-end space-x-2 mt-3">
                    <button onclick="viewUser(${user.id})" class="text-blue-600 hover:text-blue-900 text-sm">Lihat</button>
                    <button onclick="editUser(${user.id})" class="text-green-600 hover:text-green-900 text-sm">Edit</button>
                    <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-900 text-sm">Hapus</button>
                </div>
            `;
            mobileContainer.appendChild(card);
        }
    });
}

// Update count functions
function updateKunjunganCount(count = null) {
    const totalCount = count !== null ? count : kunjunganData.length;
    const countElement = document.getElementById('total-kunjungan-count');
    if (countElement) {
        countElement.textContent = totalCount;
    }
}

// Filter functions
function filterKunjungan() {
    const dateStart = document.getElementById('filter-date-start').value;
    const dateEnd = document.getElementById('filter-date-end').value;
    const fundraiser = document.getElementById('filter-fundraiser').value;
    const status = document.getElementById('filter-status').value;
    const donatur = document.getElementById('filter-donatur').value;
    
    const filteredData = kunjunganData.filter(item => {
        const itemDate = new Date(item.timestamp || item.waktu).toISOString().split('T')[0];
        const dateMatch = (!dateStart || itemDate >= dateStart) && (!dateEnd || itemDate <= dateEnd);
        const fundraiserMatch = !fundraiser || (item.fundraiserId || item.fundraiser) == fundraiser;
        const statusMatch = !status || item.status === status;
        const donaturName = item.donaturNama || item.donatur || '';
        const donaturMatch = !donatur || donaturName.toLowerCase().includes(donatur.toLowerCase());
        
        return dateMatch && fundraiserMatch && statusMatch && donaturMatch;
    });
    
    renderKunjunganTable(filteredData);
    updateKunjunganCount(filteredData.length);
}

function resetFilter() {
    document.getElementById('filter-date-start').value = '';
    document.getElementById('filter-date-end').value = '';
    document.getElementById('filter-fundraiser').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-donatur').value = '';
    
    renderKunjunganTable();
    updateKunjunganCount(kunjunganData.length);
}

function filterDonatur() {
    const search = document.getElementById('search-donatur').value.toLowerCase();
    const kategori = document.getElementById('filter-kategori-donatur').value;
    
    const filteredData = donaturData.filter(item => {
        const searchMatch = !search || 
            item.nama.toLowerCase().includes(search) ||
            item.hp.includes(search) ||
            item.email?.toLowerCase().includes(search);
        const kategoriMatch = !kategori || item.kategori === kategori;
        
        return searchMatch && kategoriMatch;
    });
    
    renderDonaturTable(filteredData);
}

// Bulk action functions
function toggleSelectAll(type) {
    const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-type="${type}"]`);
    const selectAllCheckbox = document.getElementById(`select-all-${type}`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

function bulkAction() {
    const selectedKunjungan = document.querySelectorAll('input[type="checkbox"][data-type="kunjungan"]:checked');
    if (selectedKunjungan.length === 0) {
        Utils.showNotification('Pilih kunjungan terlebih dahulu', 'warning');
        return;
    }
    
    const action = prompt('Pilih aksi: delete, export, atau update-status');
    if (!action) return;
    
    const selectedIds = Array.from(selectedKunjungan).map(cb => cb.value);
    
    switch (action.toLowerCase()) {
        case 'delete':
            if (confirm(`Hapus ${selectedIds.length} kunjungan?`)) {
                selectedIds.forEach(id => {
                    const index = kunjunganData.findIndex(item => item.id == id);
                    if (index > -1) {
                        kunjunganData.splice(index, 1);
                    }
                });
                DataManager.saveData();
                renderKunjunganTable();
                updateDashboardStats();
                Utils.showNotification(`${selectedIds.length} kunjungan berhasil dihapus`, 'success');
            }
            break;
        case 'export':
            const selectedData = kunjunganData.filter(item => selectedIds.includes(item.id.toString()));
            Utils.exportData(selectedData, 'kunjungan-selected', 'csv');
            break;
        case 'update-status':
            const newStatus = prompt('Status baru (berhasil/tidak-berhasil/follow-up):');
            if (newStatus && ['berhasil', 'tidak-berhasil', 'follow-up'].includes(newStatus)) {
                selectedIds.forEach(id => {
                    const item = kunjunganData.find(item => item.id == id);
                    if (item) item.status = newStatus;
                });
                DataManager.saveData();
                renderKunjunganTable();
                Utils.showNotification(`Status ${selectedIds.length} kunjungan berhasil diupdate`, 'success');
            }
            break;
    }
}

function bulkUpdateTarget() {
    const selectedUsers = document.querySelectorAll('input[type="checkbox"][data-type="users"]:checked');
    if (selectedUsers.length === 0) {
        Utils.showNotification('Pilih fundraiser terlebih dahulu', 'warning');
        return;
    }
    
    const newTarget = prompt('Target kunjungan harian baru (1-20):');
    if (!newTarget || isNaN(newTarget) || newTarget < 1 || newTarget > 20) {
        Utils.showNotification('Target harus berupa angka 1-20', 'error');
        return;
    }
    
    const selectedIds = Array.from(selectedUsers).map(cb => cb.value);
    selectedIds.forEach(id => {
        const user = users.find(u => u.id == id);
        if (user) user.target = parseInt(newTarget);
    });
    
    DataManager.saveData();
    renderUsersTable();
    Utils.showNotification(`Target ${selectedIds.length} fundraiser berhasil diupdate`, 'success');
}

// View, Edit, Delete functions
function viewKunjungan(id) {
    const kunjungan = kunjunganData.find(k => k.id == id);
    if (!kunjungan) {
        Utils.showNotification('Data kunjungan tidak ditemukan', 'error');
        return;
    }
    
    const fundraiser = users.find(u => u.id == kunjungan.fundraiser);
    const message = `
Detail Kunjungan:
- Fundraiser: ${fundraiser?.nama || 'Tidak diketahui'}
- Donatur: ${kunjungan.donatur}
- HP: ${kunjungan.hp}
- Alamat: ${kunjungan.alamat}
- Status: ${Utils.getStatusText(kunjungan.status)}
- Nominal: ${kunjungan.nominal ? Utils.formatCurrency(kunjungan.nominal) : '-'}
- Lokasi: ${kunjungan.lokasi}
- Waktu: ${Utils.formatDate(kunjungan.timestamp)}
- Catatan: ${kunjungan.catatan || '-'}
    `;
    
    alert(message);
}

function editKunjungan(id) {
    const kunjungan = kunjunganData.find(k => k.id == id);
    if (!kunjungan) {
        Utils.showNotification('Data kunjungan tidak ditemukan', 'error');
        return;
    }

    document.getElementById('edit-kunjungan-id').value = kunjungan.id;
    document.getElementById('edit-kunjungan-fundraiser').value = kunjungan.fundraiserId;
    document.getElementById('edit-kunjungan-donatur').value = kunjungan.donaturNama;
    document.getElementById('edit-kunjungan-hp').value = kunjungan.donaturHp;
    document.getElementById('edit-kunjungan-status').value = kunjungan.status;
    document.getElementById('edit-kunjungan-nominal').value = kunjungan.nominal;
    document.getElementById('edit-kunjungan-follow-up').value = kunjungan.followUpDate;
    document.getElementById('edit-kunjungan-alamat').value = kunjungan.alamat;
    document.getElementById('edit-kunjungan-catatan').value = kunjungan.catatan;

    document.getElementById('edit-kunjungan-modal').classList.remove('hidden');
}

function deleteKunjungan(id) {
    if (confirm('Yakin ingin menghapus kunjungan ini?')) {
        const index = kunjunganData.findIndex(k => k.id == id);
        if (index > -1) {
            kunjunganData.splice(index, 1);
            DataManager.saveData();
            renderKunjunganTable();
            updateDashboardStats();
            Utils.showNotification('Kunjungan berhasil dihapus', 'success');
        }
    }
}

function viewDonatur(id) {
    const donatur = donaturData.find(d => d.id == id);
    if (!donatur) {
        Utils.showNotification('Data donatur tidak ditemukan', 'error');
        return;
    }
    
    const message = `
Detail Donatur:
- Nama: ${donatur.nama}
- HP: ${donatur.hp}
- Email: ${donatur.email || '-'}
- Alamat: ${donatur.alamat}
- Kategori: ${donatur.kategori}
- Status: ${Utils.getStatusText(donatur.status)}
- Total Donasi: ${Utils.formatCurrency(donatur.totalDonasi || 0)}
- Terakhir Donasi: ${donatur.terakhirDonasi ? Utils.formatDate(donatur.terakhirDonasi) : '-'}
- Bergabung: ${Utils.formatDate(donatur.timestamp)}
    `;
    
    alert(message);
}

function editDonatur(id) {
    const donatur = donaturData.find(d => d.id == id);
    if (!donatur) {
        Utils.showNotification('Data donatur tidak ditemukan', 'error');
        return;
    }

    document.getElementById('edit-donatur-id').value = donatur.id;
    document.getElementById('edit-donatur-nama').value = donatur.nama;
    document.getElementById('edit-donatur-hp').value = donatur.hp;
    document.getElementById('edit-donatur-email').value = donatur.email;
    document.getElementById('edit-donatur-alamat').value = donatur.alamat;
    document.getElementById('edit-donatur-kategori').value = donatur.kategori;

    document.getElementById('edit-donatur-modal').classList.remove('hidden');
}

function deleteDonatur(id) {
    if (confirm('Yakin ingin menghapus donatur ini?')) {
        const index = donaturData.findIndex(d => d.id == id);
        if (index > -1) {
            donaturData.splice(index, 1);
            DataManager.saveData();
            renderDonaturTable();
            updateDonaturStats();
            Utils.showNotification('Donatur berhasil dihapus', 'success');
        }
    }
}

function viewUser(id) {
    const user = users.find(u => u.id == id);
    if (!user) {
        Utils.showNotification('Data fundraiser tidak ditemukan', 'error');
        return;
    }
    
    const message = `
Detail Fundraiser:
- Nama: ${user.nama}
- Email: ${user.email}
- HP: ${user.hp}
- Status: ${Utils.getStatusText(user.status)}
- Target Harian: ${user.target} kunjungan
- Kunjungan Hari Ini: ${user.kunjunganHariIni || 0}
- Total Kunjungan: ${user.totalKunjungan || 0}
- Performa Bulan Ini: ${user.performaBulanIni || 0}%
- Bergabung: ${Utils.formatDate(user.timestamp)}
    `;
    
    alert(message);
}

function editUser(id) {
    const user = users.find(u => u.id == id);
    if (!user) {
        Utils.showNotification('Data fundraiser tidak ditemukan', 'error');
        return;
    }

    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-user-nama').value = user.nama;
    document.getElementById('edit-user-email').value = user.email;
    document.getElementById('edit-user-hp').value = user.hp;
    document.getElementById('edit-user-target').value = user.target;

    document.getElementById('edit-user-modal').classList.remove('hidden');
}

function deleteUser(id) {
    if (confirm('Yakin ingin menghapus fundraiser ini?')) {
        const index = users.findIndex(u => u.id == id);
        if (index > -1) {
            users.splice(index, 1);
            DataManager.saveData();
            renderUsersTable();
            populateSelects();
            Utils.showNotification('Fundraiser berhasil dihapus', 'success');
        }
    }
}

// Export functions to global scope
window.renderKunjunganTable = renderKunjunganTable;
window.renderDonaturTable = renderDonaturTable;
window.renderUsersTable = renderUsersTable;
window.filterDonatur = filterDonatur;
window.filterKunjungan = filterKunjungan;
window.resetFilter = resetFilter;
window.toggleSelectAll = toggleSelectAll;
window.bulkAction = bulkAction;
window.bulkUpdateTarget = bulkUpdateTarget;
window.viewKunjungan = viewKunjungan;
window.viewDonatur = viewDonatur;
window.viewUser = viewUser;
window.editKunjungan = editKunjungan;
window.editDonatur = editDonatur;
window.editUser = editUser;
window.deleteKunjungan = deleteKunjungan;
window.deleteDonatur = deleteDonatur;
window.deleteUser = deleteUser; 

// Create mobile card for kunjungan data
function createKunjunganMobileCard(item) {
    const statusColors = {
        'berhasil': 'bg-green-100 text-green-800',
        'tidak-berhasil': 'bg-red-100 text-red-800',
        'follow-up': 'bg-yellow-100 text-yellow-800'
    };
    
    const statusText = {
        'berhasil': 'Berhasil',
        'tidak-berhasil': 'Tidak Berhasil',
        'follow-up': 'Follow Up'
    };
    
    return `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 text-sm">${item.donatur}</h4>
                    <p class="text-xs text-gray-600">${item.fundraiser}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusColors[item.status]}">
                    ${statusText[item.status]}
                </span>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">No. HP:</span>
                    <span class="text-gray-900">${item.hp}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Nominal:</span>
                    <span class="text-gray-900 font-medium">${item.nominal ? Utils.formatCurrency(item.nominal) : '-'}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Waktu:</span>
                    <span class="text-gray-900">${Utils.formatDate(item.timestamp, 'DD/MM/YYYY HH:mm')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Lokasi:</span>
                    <span class="text-gray-900">${item.lokasi || '-'}</span>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex space-x-2">
                    <button onclick="editKunjungan(${item.id})" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-blue-700 transition-colors min-h-[44px]">
                        Edit
                    </button>
                    <button onclick="deleteKunjungan(${item.id})" class="flex-1 bg-red-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-red-700 transition-colors min-h-[44px]">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Create mobile card for donatur data
function createDonaturMobileCard(item) {
    const kategoriColors = {
        'individu': 'bg-blue-100 text-blue-800',
        'perusahaan': 'bg-purple-100 text-purple-800',
        'organisasi': 'bg-green-100 text-green-800'
    };
    
    return `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 text-sm">${item.nama}</h4>
                    <p class="text-xs text-gray-600">${item.email || '-'}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${kategoriColors[item.kategori]}">
                    ${item.kategori.charAt(0).toUpperCase() + item.kategori.slice(1)}
                </span>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">No. HP:</span>
                    <span class="text-gray-900">${item.hp}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Alamat:</span>
                    <span class="text-gray-900">${item.alamat.substring(0, 30)}${item.alamat.length > 30 ? '...' : ''}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Donasi:</span>
                    <span class="text-gray-900 font-medium">${Utils.formatCurrency(item.totalDonasi || 0)}</span>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex space-x-2">
                    <button onclick="editDonatur(${item.id})" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-blue-700 transition-colors min-h-[44px]">
                        Edit
                    </button>
                    <button onclick="deleteDonatur(${item.id})" class="flex-1 bg-red-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-red-700 transition-colors min-h-[44px]">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Create mobile card for user data
function createUserMobileCard(item) {
    return `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 text-sm">${item.nama}</h4>
                    <p class="text-xs text-gray-600">${item.email}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${item.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'}">
                    ${item.role.charAt(0).toUpperCase() + item.role.slice(1)}
                </span>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">No. HP:</span>
                    <span class="text-gray-900">${item.hp}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Target Harian:</span>
                    <span class="text-gray-900">${item.target} kunjungan</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Kunjungan:</span>
                    <span class="text-gray-900">${item.totalKunjungan || 0}</span>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex space-x-2">
                    <button onclick="editUser(${item.id})" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-blue-700 transition-colors min-h-[44px]">
                        Edit
                    </button>
                    <button onclick="deleteUser(${item.id})" class="flex-1 bg-red-600 text-white px-3 py-2 rounded-lg text-xs hover:bg-red-700 transition-colors min-h-[44px]">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Update table display based on screen size
function updateTableDisplay() {
    const isMobile = window.innerWidth <= 768;
    const desktopTables = document.querySelectorAll('.desktop-table');
    const mobileCards = document.querySelectorAll('.mobile-card');
    
    desktopTables.forEach(table => {
        table.style.display = isMobile ? 'none' : 'block';
    });
    
    mobileCards.forEach(card => {
        card.style.display = isMobile ? 'block' : 'none';
    });
}

// Pull to refresh functionality
let pullStartY = 0;
let pullMoveY = 0;
let isPulling = false;

function initPullToRefresh() {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return;
    
    // Create pull to refresh indicator
    const pullIndicator = document.createElement('div');
    pullIndicator.className = 'pull-to-refresh';
    pullIndicator.innerHTML = `
        <div class="flex items-center justify-center">
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Pull to refresh...</span>
        </div>
    `;
    document.body.appendChild(pullIndicator);
    
    // Touch events
    mainContent.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            pullStartY = e.touches[0].clientY;
            isPulling = true;
        }
    });
    
    mainContent.addEventListener('touchmove', function(e) {
        if (!isPulling) return;
        
        pullMoveY = e.touches[0].clientY;
        const pullDistance = pullMoveY - pullStartY;
        
        if (pullDistance > 0 && window.scrollY === 0) {
            e.preventDefault();
            
            if (pullDistance > 100) {
                pullIndicator.classList.add('show');
                pullIndicator.innerHTML = `
                    <div class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Release to refresh</span>
                    </div>
                `;
            } else {
                pullIndicator.classList.remove('show');
                pullIndicator.innerHTML = `
                    <div class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Pull to refresh...</span>
                    </div>
                `;
            }
        }
    });
    
    mainContent.addEventListener('touchend', function(e) {
        if (!isPulling) return;
        
        const pullDistance = pullMoveY - pullStartY;
        
        if (pullDistance > 100) {
            // Trigger refresh
            pullIndicator.innerHTML = `
                <div class="flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Refreshing...</span>
                </div>
            `;
            
            // Refresh the page data
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        // Reset
        setTimeout(() => {
            pullIndicator.classList.remove('show');
            isPulling = false;
            pullStartY = 0;
            pullMoveY = 0;
        }, 300);
    });
}

// Add touch feedback to interactive elements
function addTouchFeedback() {
    const interactiveElements = document.querySelectorAll('button, a, .card-hover');
    
    interactiveElements.forEach(element => {
        element.classList.add('touch-feedback');
    });
}

// Show skeleton loading
function showSkeletonLoading(containerId, count = 3) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const skeletonHTML = Array(count).fill(`
        <div class="skeleton-card">
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-button"></div>
        </div>
    `).join('');
    
    container.innerHTML = skeletonHTML;
}

// Hide skeleton loading
function hideSkeletonLoading(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const skeletons = container.querySelectorAll('.skeleton-card');
    skeletons.forEach(skeleton => {
        skeleton.style.opacity = '0';
        setTimeout(() => {
            skeleton.remove();
        }, 300);
    });
}

// Initialize mobile-specific features
document.addEventListener('DOMContentLoaded', function() {
    updateTableDisplay();
    
    // Update on window resize
    window.addEventListener('resize', updateTableDisplay);
    
    // Add touch-friendly interactions
    const buttons = document.querySelectorAll('button, a');
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
        
        // Close sidebar when clicking on a link
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('mobile-open');
                }
            });
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
        }
    });
    
    // Initialize mobile-specific features
    if (window.innerWidth <= 768) {
        initPullToRefresh();
        addTouchFeedback();
    }
}); 