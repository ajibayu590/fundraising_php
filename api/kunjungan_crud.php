<?php
// Kunjungan CRUD API - Hybrid Approach
// This API handles form submissions while dashboard uses PHP directly

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

function validate_hp_number($hp) {
    return preg_match('/^[0-9]{10,13}$/', $hp) === 1;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Create new kunjungan
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Validate required fields
            $required_fields = ['fundraiser', 'donatur', 'hp', 'status', 'alamat'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            if (!validate_hp_number($data['hp'])) {
                throw new Exception('Nomor HP donatur harus 10-13 digit');
            }
            if (!in_array($data['status'], ['berhasil','tidak-berhasil','follow-up'], true)) {
                throw new Exception('Status tidak valid');
            }
            if ($data['status'] === 'berhasil') {
                if (!isset($data['nominal']) || (float)$data['nominal'] < 1000) {
                    throw new Exception('Nominal minimal 1000 untuk status berhasil');
                }
            }
            if ($data['status'] === 'follow-up') {
                if (empty($data['followUpDate'])) {
                    throw new Exception('Tanggal follow up wajib diisi untuk status follow-up');
                }
            }
            
            // Check if donatur exists, if not create new one
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE nama = ? AND hp = ?");
            $stmt->execute([$data['donatur'], $data['hp']]);
            $existing_donatur = $stmt->fetch();
            
            if ($existing_donatur) {
                $donatur_id = $existing_donatur['id'];
            } else {
                // Create new donatur (default kategori individu)
                $stmt = $pdo->prepare("INSERT INTO donatur (nama, hp, alamat, kategori, created_at) VALUES (?, ?, ?, 'individu', NOW())");
                $stmt->execute([$data['donatur'], $data['hp'], $data['alamat']]);
                $donatur_id = $pdo->lastInsertId();
            }
            
            // Insert kunjungan
            $stmt = $pdo->prepare("
                INSERT INTO kunjungan (fundraiser_id, donatur_id, status, nominal, alamat, catatan, follow_up_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $nominal = ($data['status'] === 'berhasil' && !empty($data['nominal'])) ? (float)$data['nominal'] : 0;
            $followUp = ($data['status'] === 'follow-up' && !empty($data['followUpDate'])) ? $data['followUpDate'] : null;
            
            $stmt->execute([
                $data['fundraiser'],
                $donatur_id,
                $data['status'],
                $nominal,
                $data['alamat'],
                $data['catatan'] ?? '',
                $followUp
            ]);
            
            $kunjungan_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan berhasil ditambahkan',
                'data' => [
                    'id' => $kunjungan_id,
                    'fundraiser_id' => $data['fundraiser'],
                    'donatur_id' => $donatur_id,
                    'status' => $data['status'],
                    'nominal' => $nominal
                ]
            ]);
            break;
            
        case 'PUT':
            // Update kunjungan
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception("Kunjungan ID is required");
            }
            if (empty($data['fundraiser'])) {
                throw new Exception('Fundraiser wajib diisi');
            }
            if (empty($data['status']) || !in_array($data['status'], ['berhasil','tidak-berhasil','follow-up'], true)) {
                throw new Exception('Status tidak valid');
            }
            if ($data['status'] === 'berhasil') {
                if (!isset($data['nominal']) || (float)$data['nominal'] < 1000) {
                    throw new Exception('Nominal minimal 1000 untuk status berhasil');
                }
            }
            if ($data['status'] === 'follow-up') {
                if (empty($data['followUpDate'])) {
                    throw new Exception('Tanggal follow up wajib diisi untuk status follow-up');
                }
            }
            
            // Update kunjungan
            $stmt = $pdo->prepare("
                UPDATE kunjungan 
                SET fundraiser_id = ?, status = ?, nominal = ?, alamat = ?, catatan = ?, follow_up_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $nominal = ($data['status'] === 'berhasil' && !empty($data['nominal'])) ? (float)$data['nominal'] : 0;
            $followUp = ($data['status'] === 'follow-up' && !empty($data['followUpDate'])) ? $data['followUpDate'] : null;
            
            $stmt->execute([
                $data['fundraiser'],
                $data['status'],
                $nominal,
                $data['alamat'],
                $data['catatan'] ?? '',
                $followUp,
                $data['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan berhasil diperbarui'
            ]);
            break;
            
        case 'DELETE':
            // Delete kunjungan
            $kunjungan_id = $_GET['id'] ?? null;
            
            if (!$kunjungan_id) {
                throw new Exception("Kunjungan ID is required");
            }
            
            $stmt = $pdo->prepare("DELETE FROM kunjungan WHERE id = ?");
            $stmt->execute([$kunjungan_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan berhasil dihapus'
            ]);
            break;
            
        case 'GET':
            // Get single kunjungan for editing
            $kunjungan_id = $_GET['id'] ?? null;
            
            if ($kunjungan_id) {
                $stmt = $pdo->prepare("
                    SELECT k.*, u.name as fundraiser_name, d.nama as donatur_name, d.hp as donatur_hp
                    FROM kunjungan k 
                    LEFT JOIN users u ON k.fundraiser_id = u.id 
                    LEFT JOIN donatur d ON k.donatur_id = d.id 
                    WHERE k.id = ?
                ");
                $stmt->execute([$kunjungan_id]);
                $kunjungan = $stmt->fetch();
                
                if ($kunjungan) {
                    echo json_encode([
                        'success' => true,
                        'data' => $kunjungan
                    ]);
                } else {
                    throw new Exception("Kunjungan not found");
                }
            } else {
                throw new Exception("Kunjungan ID is required");
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
