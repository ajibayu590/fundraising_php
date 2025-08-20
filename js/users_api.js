// Users API - Hybrid Approach
// Table rendering is server-side; this handles form submissions and actions

let currentEditingUserId = null;

const UsersAPI = {
	async createUser(formData) {
		try {
			const res = await fetch('api/users_crud.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-Token': window.PHP_DATA.csrfToken
				},
				credentials: 'same-origin',
				body: JSON.stringify(formData)
			});
			const result = await res.json();
			if (result.success) {
				Utils.showNotification(result.message, 'success');
				hideUserModal();
				setTimeout(() => window.location.reload(), 800);
			} else {
				Utils.showNotification(result.message, 'error');
			}
			return result;
		} catch (e) {
			console.error('Create user error:', e);
			Utils.showNotification('Gagal menambahkan user', 'error');
			return { success: false, message: e.message };
		}
	},

	async updateUser(id, updates) {
		try {
			const res = await fetch('api/users_crud.php', {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-Token': window.PHP_DATA.csrfToken
				},
				credentials: 'same-origin',
				body: JSON.stringify({ id, ...updates })
			});
			const result = await res.json();
			if (result.success) {
				Utils.showNotification(result.message, 'success');
				hideUserModal();
				setTimeout(() => window.location.reload(), 800);
			} else {
				Utils.showNotification(result.message, 'error');
			}
			return result;
		} catch (e) {
			console.error('Update user error:', e);
			Utils.showNotification('Gagal memperbarui user', 'error');
			return { success: false, message: e.message };
		}
	},

	async deleteUser(id) {
		if (!confirm('Hapus user ini?')) return { success: false, message: 'Cancelled' };
		try {
			const res = await fetch(`api/users_crud.php?id=${id}`, {
				method: 'DELETE',
				headers: { 'X-CSRF-Token': window.PHP_DATA.csrfToken },
				credentials: 'same-origin'
			});
			const result = await res.json();
			if (result.success) {
				Utils.showNotification(result.message, 'success');
				setTimeout(() => window.location.reload(), 800);
			} else {
				Utils.showNotification(result.message, 'error');
			}
			return result;
		} catch (e) {
			console.error('Delete user error:', e);
			Utils.showNotification('Gagal menghapus user', 'error');
			return { success: false, message: e.message };
		}
	},

	async getUser(id) {
		try {
			const res = await fetch(`api/users_crud.php?id=${id}`, { credentials: 'same-origin' });
			const result = await res.json();
			if (result.success) return result.data;
			Utils.showNotification(result.message, 'error');
			return null;
		} catch (e) {
			console.error('Get user error:', e);
			Utils.showNotification('Gagal mengambil data user', 'error');
			return null;
		}
	}
};

function showUserModal() {
	currentEditingUserId = null;
	const title = document.querySelector('#user-modal h3');
	if (title) title.textContent = 'Tambah Fundraiser';
	const submitBtn = document.querySelector('#user-form button[type="submit"]');
	if (submitBtn) submitBtn.lastChild.textContent = ' Simpan';
	document.getElementById('user-modal').classList.remove('hidden');
	document.getElementById('user-modal').classList.add('flex');
}

function hideUserModal() {
	currentEditingUserId = null;
	document.getElementById('user-modal').classList.add('hidden');
	document.getElementById('user-modal').classList.remove('flex');
	document.getElementById('user-form').reset();
	const title = document.querySelector('#user-modal h3');
	if (title) title.textContent = 'Tambah Fundraiser';
	const submitBtn = document.querySelector('#user-form button[type="submit"]');
	if (submitBtn) submitBtn.lastChild.textContent = ' Simpan';
}

async function editUser(id) {
	const user = await UsersAPI.getUser(id);
	if (!user) return;
	currentEditingUserId = id;
	// Populate form
	document.getElementById('user-nama').value = user.name || '';
	document.getElementById('user-email').value = user.email || '';
	if (document.getElementById('user-hp')) document.getElementById('user-hp').value = user.hp || '';
	document.getElementById('user-target').value = user.target || 8;
	document.getElementById('user-role').value = user.role || 'user';
	// Password left blank for security; filling it will update
	const title = document.querySelector('#user-modal h3');
	if (title) title.textContent = 'Edit Fundraiser';
	const submitBtn = document.querySelector('#user-form button[type="submit"]');
	if (submitBtn) submitBtn.lastChild.textContent = ' Update';
	document.getElementById('user-modal').classList.remove('hidden');
	document.getElementById('user-modal').classList.add('flex');
}

async function deleteUser(id) {
	await UsersAPI.deleteUser(id);
}

// Form handler
document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementById('user-form');
	if (form) {
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const fd = new FormData(form);
			const data = Object.fromEntries(fd.entries());
			// basic validation
			if (data.email && !/^([^\s@]+)@([^\s@]+)\.[^\s@]+$/.test(data.email)) {
				Utils.showNotification('Format email tidak valid', 'error');
				return;
			}
			const submitBtn = form.querySelector('button[type="submit"]');
			const loading = submitBtn.querySelector('.loading');
			submitBtn.disabled = true;
			loading.classList.remove('hidden');
			try {
				if (currentEditingUserId) {
					// If password empty, remove to avoid updating
					if (!data.password) delete data.password;
					await UsersAPI.updateUser(currentEditingUserId, data);
				} else {
					await UsersAPI.createUser(data);
				}
			} finally {
				submitBtn.disabled = false;
				loading.classList.add('hidden');
			}
		});
	}

	// mobile menu toggle
	const mobileBtn = document.getElementById('mobile-menu-btn');
	if (mobileBtn) {
		mobileBtn.addEventListener('click', () => {
			const sidebar = document.querySelector('.sidebar');
			if (sidebar) sidebar.classList.toggle('hidden');
		});
	}
});
