<?php
// Users CRUD API - Hybrid Approach
// Only admins can create/update/delete users; listing is done via PHP server-side in users.php

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

// Authentication
if (!isset($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

// Fetch current user
try {
	$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
	$stmt->execute([$_SESSION['user_id']]);
	$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$currentUser) {
		throw new Exception('Invalid session user');
	}
} catch (Exception $e) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// CSRF for state-changing methods
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
	check_csrf();
}

// Admin-only guard for modifications
function require_admin($currentUser) {
	if (($currentUser['role'] ?? '') !== 'admin') {
		http_response_code(403);
		echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
		exit;
	}
}

try {
	switch ($method) {
		case 'POST': {
			require_admin($currentUser);
			$input = json_decode(file_get_contents('php://input'), true);
			if (!$input) { $input = $_POST; }

			$name = trim($input['nama'] ?? '');
			$email = trim($input['email'] ?? '');
			$hp = trim($input['hp'] ?? '');
			$target = (int)($input['target'] ?? 8);
			$role = $input['role'] ?? 'user';
			$password = (string)($input['password'] ?? '');
			$status = 'aktif';

			if ($name === '' || $email === '' || $password === '') {
				throw new Exception('Nama, email, dan password wajib diisi');
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Format email tidak valid');
			}
			if (!in_array($role, ['admin', 'user', 'monitor'], true)) {
				throw new Exception('Role tidak valid');
			}
			if ($target < 1 || $target > 50) {
				throw new Exception('Target harus antara 1-50');
			}

			// Unique email
			$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
			$stmt->execute([$email]);
			if ($stmt->fetch()) {
				throw new Exception('Email sudah terdaftar');
			}

			$hashed = password_hash($password, PASSWORD_BCRYPT);

			$stmt = $pdo->prepare('INSERT INTO users (name, email, password, hp, role, status, target, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
			$stmt->execute([$name, $email, $hashed, $hp, $role, $status, $target]);
			$newId = $pdo->lastInsertId();

			echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan', 'data' => ['id' => (int)$newId]]);
			break;
		}

		case 'PUT': {
			require_admin($currentUser);
			$input = json_decode(file_get_contents('php://input'), true);
			if (!$input) { throw new Exception('Invalid input'); }

			$id = (int)($input['id'] ?? 0);
			if ($id <= 0) { throw new Exception('User ID wajib diisi'); }

			// Fetch existing
			$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$existing = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$existing) { throw new Exception('User tidak ditemukan'); }

			$fields = [];
			$params = [];

			if (isset($input['nama'])) { $fields[] = 'name = ?'; $params[] = trim($input['nama']); }
			if (isset($input['email'])) {
				$email = trim($input['email']);
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception('Format email tidak valid'); }
				// Unique email for other users
				$check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
				$check->execute([$email, $id]);
				if ($check->fetch()) { throw new Exception('Email sudah digunakan'); }
				$fields[] = 'email = ?';
				$params[] = $email;
			}
			if (isset($input['hp'])) { $fields[] = 'hp = ?'; $params[] = trim($input['hp']); }
			if (isset($input['target'])) {
				$target = (int)$input['target'];
				if ($target < 1 || $target > 50) { throw new Exception('Target harus antara 1-50'); }
				$fields[] = 'target = ?'; $params[] = $target;
			}
			if (isset($input['role'])) {
				$role = $input['role'];
				if (!in_array($role, ['admin', 'user', 'monitor'], true)) { throw new Exception('Role tidak valid'); }
				$fields[] = 'role = ?'; $params[] = $role;
			}
			if (isset($input['status'])) {
				$status = $input['status'];
				if (!in_array($status, ['aktif', 'nonaktif'], true)) { throw new Exception('Status tidak valid'); }
				$fields[] = 'status = ?'; $params[] = $status;
			}
			if (!empty($input['password'])) {
				$hashed = password_hash($input['password'], PASSWORD_BCRYPT);
				$fields[] = 'password = ?'; $params[] = $hashed;
			}

			if (empty($fields)) { throw new Exception('Tidak ada perubahan'); }
			$fields[] = 'updated_at = NOW()';
			$sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
			$params[] = $id;
			$upd = $pdo->prepare($sql);
			$upd->execute($params);

			echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui']);
			break;
		}

		case 'DELETE': {
			require_admin($currentUser);
			$id = (int)($_GET['id'] ?? 0);
			if ($id <= 0) { throw new Exception('User ID wajib diisi'); }
			if ($id === (int)$currentUser['id']) { throw new Exception('Tidak dapat menghapus akun sendiri'); }

			// Optional: prevent delete if user has kunjungan records
			$stmt = $pdo->prepare('SELECT COUNT(*) FROM kunjungan WHERE fundraiser_id = ?');
			$stmt->execute([$id]);
			if ((int)$stmt->fetchColumn() > 0) {
				throw new Exception('Tidak dapat menghapus user dengan riwayat kunjungan');
			}

			$del = $pdo->prepare('DELETE FROM users WHERE id = ?');
			$del->execute([$id]);
			echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
			break;
		}

		case 'GET': {
			// Get single user by id
			$id = (int)($_GET['id'] ?? 0);
			if ($id <= 0) { throw new Exception('User ID wajib diisi'); }
			$stmt = $pdo->prepare('SELECT id, name, email, hp, target, role, status FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$user) { throw new Exception('User tidak ditemukan'); }
			echo json_encode(['success' => true, 'data' => $user]);
			break;
		}

		default: {
			http_response_code(405);
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);
		}
	}
} catch (Exception $e) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
