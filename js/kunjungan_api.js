// Kunjungan API Operations - Hybrid Approach
// This handles form submissions while table display uses PHP directly

let currentEditingKunjunganId = null;

const KunjunganAPI = {
    // Submit new kunjungan
    async submitKunjungan(formData) {
        try {
            const response = await fetch('api/kunjungan_crud.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.PHP_DATA.csrfToken
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                Utils.showNotification(result.message, 'success');
                hideKunjunganModal();
                // Refresh page to show new data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                Utils.showNotification(result.message, 'error');
            }
            
            return result;
        } catch (error) {
            console.error('Error submitting kunjungan:', error);
            Utils.showNotification('Gagal menambahkan kunjungan', 'error');
            return { success: false, message: error.message };
        }
    },

    // Update kunjungan
    async updateKunjungan(id, formData) {
        try {
            const response = await fetch('api/kunjungan_crud.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.PHP_DATA.csrfToken
                },
                body: JSON.stringify({ id, ...formData })
            });
            
            const result = await response.json();
            
            if (result.success) {
                Utils.showNotification(result.message, 'success');
                hideKunjunganModal();
                // Refresh page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                Utils.showNotification(result.message, 'error');
            }
            
            return result;
        } catch (error) {
            console.error('Error updating kunjungan:', error);
            Utils.showNotification('Gagal memperbarui kunjungan', 'error');
            return { success: false, message: error.message };
        }
    },

    // Delete kunjungan
    async deleteKunjungan(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus kunjungan ini?')) {
            return { success: false, message: 'Cancelled' };
        }
        
        try {
            const response = await fetch(`api/kunjungan_crud.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': window.PHP_DATA.csrfToken
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                Utils.showNotification(result.message, 'success');
                // Refresh page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                Utils.showNotification(result.message, 'error');
            }
            
            return result;
        } catch (error) {
            console.error('Error deleting kunjungan:', error);
            Utils.showNotification('Gagal menghapus kunjungan', 'error');
            return { success: false, message: error.message };
        }
    },

    // Get kunjungan for editing
    async getKunjungan(id) {
        try {
            const response = await fetch(`api/kunjungan_crud.php?id=${id}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': window.PHP_DATA.csrfToken
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                Utils.showNotification(result.message, 'error');
                return null;
            }
        } catch (error) {
            console.error('Error getting kunjungan:', error);
            Utils.showNotification('Gagal mengambil data kunjungan', 'error');
            return null;
        }
    }
};

// Form handling functions
function showKunjunganModal() {
    document.getElementById('kunjungan-modal').classList.remove('hidden');
    document.getElementById('kunjungan-modal').classList.add('flex');
}

function hideKunjunganModal() {
    document.getElementById('kunjungan-modal').classList.add('hidden');
    document.getElementById('kunjungan-modal').classList.remove('flex');
    document.getElementById('kunjungan-form').reset();
}

function updateKunjunganModalTitle(isEdit) {
    const title = document.querySelector('#kunjungan-modal h3');
    if (!title) return;
    title.textContent = isEdit ? 'Edit Kunjungan Fundraiser' : 'Tambah Kunjungan Fundraiser';
    const submitBtn = document.querySelector('#kunjungan-form button[type="submit"]');
    if (submitBtn) {
        submitBtn.lastChild.textContent = isEdit ? ' Update Kunjungan' : ' Simpan Kunjungan';
    }
}

function resetStatusDependentFields() {
    const statusSelect = document.getElementById('kunjungan-status');
    const nominalField = document.getElementById('nominal-field');
    const followUpField = document.getElementById('follow-up-field');
    const nominalInput = document.getElementById('kunjungan-nominal');
    const followUpInput = document.getElementById('kunjungan-follow-up');
    if (statusSelect && nominalField && followUpField && nominalInput && followUpInput) {
        nominalField.classList.add('hidden');
        followUpField.classList.add('hidden');
        nominalInput.required = false;
        followUpInput.required = false;
        statusSelect.value = '';
    }
}

async function editKunjungan(id) {
    const kunjungan = await KunjunganAPI.getKunjungan(id);
    if (!kunjungan) return;
    currentEditingKunjunganId = id;

    const fundraiserSelect = document.getElementById('kunjungan-fundraiser');
    const donaturInput = document.getElementById('kunjungan-donatur');
    const hpInput = document.getElementById('kunjungan-hp');
    const statusSelect = document.getElementById('kunjungan-status');
    const nominalInput = document.getElementById('kunjungan-nominal');
    const followUpInput = document.getElementById('kunjungan-follow-up');
    const alamatInput = document.getElementById('kunjungan-alamat');
    const catatanInput = document.getElementById('kunjungan-catatan');

    if (fundraiserSelect) fundraiserSelect.value = kunjungan.fundraiser_id || '';
    if (donaturInput) donaturInput.value = kunjungan.donatur_name || '';
    if (hpInput) hpInput.value = kunjungan.donatur_hp || '';
    if (statusSelect) statusSelect.value = kunjungan.status || '';

    // apply status dependent
    handleStatusToggle();

    if (nominalInput) nominalInput.value = (kunjungan.nominal && kunjungan.status === 'berhasil') ? kunjungan.nominal : '';
    if (followUpInput) followUpInput.value = (kunjungan.follow_up_date && kunjungan.status === 'follow-up') ? kunjungan.follow_up_date.substring(0,10) : '';
    if (alamatInput) alamatInput.value = kunjungan.alamat || '';
    if (catatanInput) catatanInput.value = kunjungan.catatan || '';

    updateKunjunganModalTitle(true);
    document.getElementById('kunjungan-modal').classList.remove('hidden');
    document.getElementById('kunjungan-modal').classList.add('flex');
}

async function deleteKunjungan(id) {
    await KunjunganAPI.deleteKunjungan(id);
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const kunjunganForm = document.getElementById('kunjungan-form');
    if (kunjunganForm) {
        kunjunganForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const loadingSpan = submitBtn.querySelector('.loading');
            submitBtn.disabled = true;
            loadingSpan.classList.remove('hidden');
            
            try {
                if (currentEditingKunjunganId) {
                    await KunjunganAPI.updateKunjungan(currentEditingKunjunganId, data);
                } else {
                    await KunjunganAPI.submitKunjungan(data);
                }
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                loadingSpan.classList.add('hidden');
            }
        });
    }
    
    // Status change handler
    const statusSelect = document.getElementById('kunjungan-status');
    if (statusSelect) {
        statusSelect.addEventListener('change', handleStatusToggle);
    }
    
    // Donatur suggestions
    const donaturInput = document.getElementById('kunjungan-donatur');
    const suggestionsDiv = document.getElementById('donatur-suggestions');
    
    if (donaturInput && suggestionsDiv) {
        donaturInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const suggestions = window.PHP_DATA.donaturList.filter(donatur => 
                donatur.nama.toLowerCase().includes(query) || 
                donatur.hp.includes(query)
            );
            
            if (suggestions.length > 0 && query.length > 0) {
                suggestionsDiv.innerHTML = suggestions.map(donatur => 
                    `<div class="p-2 hover:bg-gray-100 cursor-pointer" onclick="selectDonatur('${donatur.nama}', '${donatur.hp}')">
                        <div class="font-medium">${donatur.nama}</div>
                        <div class="text-sm text-gray-500">${donatur.hp}</div>
                    </div>`
                ).join('');
                suggestionsDiv.classList.remove('hidden');
            } else {
                suggestionsDiv.classList.add('hidden');
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!donaturInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.add('hidden');
            }
        });
    }
});

// Helper function to select donatur from suggestions
function selectDonatur(nama, hp) {
    document.getElementById('kunjungan-donatur').value = nama;
    document.getElementById('kunjungan-hp').value = hp;
    document.getElementById('donatur-suggestions').classList.add('hidden');
}

// Export function
function exportToExcel() {
    // Get current URL with filters
    const currentUrl = window.location.href;
    const exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=excel';
    
    // Create temporary link and click it
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'kunjungan_data.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Utils.showNotification('Export started', 'info');
}

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
            }
        });
    }
});
