<?php
// Donatur CRUD API - Hybrid Approach
// This API handles form submissions while table display uses PHP directly

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check CSRF for POST/PUT/DELETE requests
check_csrf();

header('Content-Type: application/json');

function validate_kategori($kategori) {
    $allowed = ['individu','korporasi','yayasan','organisasi'];
    return in_array($kategori, $allowed, true);
}

function validate_hp($hp) {
    return preg_match('/^[0-9]{10,13}$/', $hp) === 1;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Create new donatur
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Validate required fields
            $required_fields = ['nama', 'hp', 'alamat', 'kategori'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            
            if (!validate_hp($data['hp'])) {
                throw new Exception('Nomor HP harus 10-13 digit angka');
            }
            if (!validate_kategori($data['kategori'])) {
                throw new Exception('Kategori tidak valid');
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }
            
            // Check if donatur already exists
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ?");
            $stmt->execute([$data['hp']]);
            $existing_donatur = $stmt->fetch();
            
            if ($existing_donatur) {
                throw new Exception("Donatur dengan nomor HP ini sudah terdaftar");
            }
            
            // Insert donatur
            $stmt = $pdo->prepare("
                INSERT INTO donatur (nama, hp, email, alamat, kategori, catatan, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['nama'],
                $data['hp'],
                $data['email'] ?? '',
                $data['alamat'],
                $data['kategori'],
                $data['catatan'] ?? ''
            ]);
            
            $donatur_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Donatur berhasil ditambahkan',
                'data' => [
                    'id' => $donatur_id,
                    'nama' => $data['nama'],
                    'hp' => $data['hp'],
                    'kategori' => $data['kategori']
                ]
            ]);
            break;
            
        case 'PUT':
            // Update donatur
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception("Donatur ID is required");
            }
            
            // Validate if provided
            if (!empty($data['hp']) && !validate_hp($data['hp'])) {
                throw new Exception('Nomor HP harus 10-13 digit angka');
            }
            if (!empty($data['kategori']) && !validate_kategori($data['kategori'])) {
                throw new Exception('Kategori tidak valid');
            }
            if (isset($data['email']) && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }
            
            // Check if HP already exists for other donatur (only when hp provided)
            if (!empty($data['hp'])) {
                $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ? AND id != ?");
                $stmt->execute([$data['hp'], $data['id']]);
                $existing_donatur = $stmt->fetch();
                if ($existing_donatur) {
                    throw new Exception("Nomor HP sudah digunakan oleh donatur lain");
                }
            }
            
            // Build dynamic update
            $fields = [];
            $params = [];
            foreach (['nama'=>'nama','hp'=>'hp','email'=>'email','alamat'=>'alamat','kategori'=>'kategori','catatan'=>'catatan'] as $k=>$col) {
                if (array_key_exists($k, $data)) { $fields[] = "$col = ?"; $params[] = $data[$k]; }
            }
            if (empty($fields)) {
                throw new Exception('Tidak ada perubahan');
            }
            $fields[] = 'updated_at = NOW()';
            $params[] = $data['id'];
            $sql = 'UPDATE donatur SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Donatur berhasil diperbarui'
            ]);
            break;
            
        case 'DELETE':
            // Delete donatur
            $donatur_id = $_GET['id'] ?? null;
            
            if (!$donatur_id) {
                throw new Exception("Donatur ID is required");
            }
            
            // Check if donatur has related kunjungan
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE donatur_id = ?");
            $stmt->execute([$donatur_id]);
            $kunjungan_count = $stmt->fetchColumn();
            
            if ($kunjungan_count > 0) {
                throw new Exception("Tidak dapat menghapus donatur yang memiliki riwayat kunjungan");
            }
            
            $stmt = $pdo->prepare("DELETE FROM donatur WHERE id = ?");
            $stmt->execute([$donatur_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Donatur berhasil dihapus'
            ]);
            break;
            
        case 'GET':
            // Get single donatur for editing
            $donatur_id = $_GET['id'] ?? null;
            
            if ($donatur_id) {
                $stmt = $pdo->prepare("
                    SELECT d.*, 
                    COUNT(k.id) as jumlah_kunjungan,
                    COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
                    FROM donatur d 
                    LEFT JOIN kunjungan k ON d.id = k.donatur_id
                    WHERE d.id = ?
                    GROUP BY d.id, d.nama, d.hp, d.email, d.alamat, d.kategori, d.catatan, d.created_at
                ");
                $stmt->execute([$donatur_id]);
                $donatur = $stmt->fetch();
                
                if ($donatur) {
                    echo json_encode([
                        'success' => true,
                        'data' => $donatur
                    ]);
                } else {
                    throw new Exception("Donatur not found");
                }
            } else {
                throw new Exception("Donatur ID is required");
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
