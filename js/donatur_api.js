// Donatur API Operations - Hybrid Approach
// This handles form submissions while table display uses PHP directly

let currentEditingDonaturId = null;

const DonaturAPI = {
	// Submit new donatur
	async submitDonatur(formData) {
		try {
			const response = await fetch('api/donatur_crud.php', {
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
				hideDonaturModal();
				// Refresh page to show new data
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			} else {
				Utils.showNotification(result.message, 'error');
			}
			
			return result;
		} catch (error) {
			console.error('Error submitting donatur:', error);
			Utils.showNotification('Gagal menambahkan donatur', 'error');
			return { success: false, message: error.message };
		}
	},

	// Update donatur
	async updateDonatur(id, formData) {
		try {
			const response = await fetch('api/donatur_crud.php', {
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
				hideDonaturModal();
				// Refresh page to show updated data
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			} else {
				Utils.showNotification(result.message, 'error');
			}
			
			return result;
		} catch (error) {
			console.error('Error updating donatur:', error);
			Utils.showNotification('Gagal memperbarui donatur', 'error');
			return { success: false, message: error.message };
		}
	},

	// Delete donatur
	async deleteDonatur(id) {
		if (!confirm('Apakah Anda yakin ingin menghapus donatur ini?')) {
			return { success: false, message: 'Cancelled' };
		}
		
		try {
			const response = await fetch(`api/donatur_crud.php?id=${id}`, {
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
			console.error('Error deleting donatur:', error);
			Utils.showNotification('Gagal menghapus donatur', 'error');
			return { success: false, message: error.message };
		}
	},

	// Get donatur for editing
	async getDonatur(id) {
		try {
			const response = await fetch(`api/donatur_crud.php?id=${id}`, {
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
			console.error('Error getting donatur:', error);
			Utils.showNotification('Gagal mengambil data donatur', 'error');
			return null;
		}
	}
};

// Form handling functions
function showDonaturModal() {
	currentEditingDonaturId = null;
	const title = document.querySelector('#donatur-modal h3');
	if (title) title.textContent = 'Tambah Donatur Baru';
	const submitBtn = document.querySelector('#donatur-form button[type="submit"]');
	if (submitBtn) submitBtn.lastChild.textContent = ' Simpan Donatur';
	document.getElementById('donatur-modal').classList.remove('hidden');
	document.getElementById('donatur-modal').classList.add('flex');
}

function hideDonaturModal() {
	currentEditingDonaturId = null;
	document.getElementById('donatur-modal').classList.add('hidden');
	document.getElementById('donatur-modal').classList.remove('flex');
	document.getElementById('donatur-form').reset();
	// reset UI text
	const title = document.querySelector('#donatur-modal h3');
	if (title) title.textContent = 'Tambah Donatur Baru';
	const submitBtn = document.querySelector('#donatur-form button[type="submit"]');
	if (submitBtn) submitBtn.lastChild.textContent = ' Simpan Donatur';
}

async function editDonatur(id) {
	const donatur = await DonaturAPI.getDonatur(id);
	if (donatur) {
		currentEditingDonaturId = id;
		// Populate form
		document.getElementById('donatur-nama').value = donatur.nama || '';
		document.getElementById('donatur-hp').value = donatur.hp || '';
		document.getElementById('donatur-email').value = donatur.email || '';
		document.getElementById('donatur-kategori').value = donatur.kategori || '';
		document.getElementById('donatur-alamat').value = donatur.alamat || '';
		const catatanField = document.getElementById('donatur-catatan');
		if (catatanField) catatanField.value = donatur.catatan || '';
		// Update UI text
		const title = document.querySelector('#donatur-modal h3');
		if (title) title.textContent = 'Edit Donatur';
		const submitBtn = document.querySelector('#donatur-form button[type="submit"]');
		if (submitBtn) submitBtn.lastChild.textContent = ' Update Donatur';
		// Show modal
		document.getElementById('donatur-modal').classList.remove('hidden');
		document.getElementById('donatur-modal').classList.add('flex');
	}
}

async function deleteDonatur(id) {
	await DonaturAPI.deleteDonatur(id);
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
	const donaturForm = document.getElementById('donatur-form');
	if (donaturForm) {
		donaturForm.addEventListener('submit', async function(e) {
			e.preventDefault();
			
			const formData = new FormData(this);
			const data = Object.fromEntries(formData.entries());
			
			// Show loading state
			const submitBtn = this.querySelector('button[type="submit"]');
			const loadingSpan = submitBtn.querySelector('.loading');
			submitBtn.disabled = true;
			loadingSpan.classList.remove('hidden');
			
			try {
				if (currentEditingDonaturId) {
					await DonaturAPI.updateDonatur(currentEditingDonaturId, data);
				} else {
					await DonaturAPI.submitDonatur(data);
				}
			} finally {
				// Reset loading state
				submitBtn.disabled = false;
				loadingSpan.classList.add('hidden');
			}
		});
	}
	
	// HP number validation
	const hpInput = document.getElementById('donatur-hp');
	if (hpInput) {
		hpInput.addEventListener('input', function() {
			// Remove non-numeric characters
			this.value = this.value.replace(/[^0-9]/g, '');
			
			// Limit to 13 digits
			if (this.value.length > 13) {
				this.value = this.value.slice(0, 13);
			}
		});
	}
	
	// Email validation
	const emailInput = document.getElementById('donatur-email');
	if (emailInput) {
		emailInput.addEventListener('blur', function() {
			if (this.value && !isValidEmail(this.value)) {
				this.setCustomValidity('Format email tidak valid');
				this.classList.add('border-red-500');
			} else {
				this.setCustomValidity('');
				this.classList.remove('border-red-500');
			}
		});
	}
});

// Helper function to validate email
function isValidEmail(email) {
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailRegex.test(email);
}

// Export function
function exportToExcel() {
	// Get current URL with filters
	const currentUrl = window.location.href;
	const exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=excel';
	
	// Create temporary link and click it
	const link = document.createElement('a');
	link.href = exportUrl;
	link.download = 'donatur_data.xlsx';
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
