<?php
// API CRUD untuk Kunjungan User (Fundraiser)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if user has 'user' role
if ($_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. User role required.']);
    exit;
}

require_once dirname(__DIR__) . '/config.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get kunjungan data for specific user
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                // Get specific kunjungan (only if belongs to user)
                $stmt = $pdo->prepare("
                    SELECT 
                        k.id,
                        k.fundraiser_id,
                        k.donatur_id,
                        k.alamat,
                        k.status,
                        k.nominal,
                        k.catatan,
                        k.created_at,
                        k.updated_at,
                        d.nama as donatur_nama,
                        d.hp as donatur_hp,
                        d.email as donatur_email
                    FROM kunjungan k
                    JOIN donatur d ON k.donatur_id = d.id
                    WHERE k.id = ? AND k.fundraiser_id = ?
                ");
                $stmt->execute([$id, $user_id]);
                $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$kunjungan) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Kunjungan not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $kunjungan]);
            } else {
                // Get all kunjungan for user
                $stmt = $pdo->prepare("
                    SELECT 
                        k.id,
                        k.fundraiser_id,
                        k.donatur_id,
                        k.alamat,
                        k.status,
                        k.nominal,
                        k.catatan,
                        k.created_at,
                        k.updated_at,
                        d.nama as donatur_nama,
                        d.hp as donatur_hp,
                        d.email as donatur_email
                    FROM kunjungan k
                    JOIN donatur d ON k.donatur_id = d.id
                    WHERE k.fundraiser_id = ?
                    ORDER BY k.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $kunjungan = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $kunjungan,
                    'total' => count($kunjungan)
                ]);
            }
            break;
            
        case 'POST':
            // Create new kunjungan
            check_csrf();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['donatur_id']) || empty($input['alamat']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // Validate donatur exists and belongs to user (if user has visited this donatur before)
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE id = ?");
            $stmt->execute([$input['donatur_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Donatur not found']);
                exit;
            }
            
            // Insert new kunjungan
            $stmt = $pdo->prepare("
                INSERT INTO kunjungan (fundraiser_id, donatur_id, alamat, status, nominal, catatan, waktu, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $input['donatur_id'],
                $input['alamat'],
                $input['status'],
                $input['nominal'] ?? 0,
                $input['catatan'] ?? ''
            ]);
            

            
            $kunjungan_id = $pdo->lastInsertId();
            
            // Get the created kunjungan
            $stmt = $pdo->prepare("
                SELECT 
                    k.id,
                    k.fundraiser_id,
                    k.donatur_id,
                    k.alamat,
                    k.status,
                    k.nominal,
                    k.catatan,
                    k.created_at,
                    k.updated_at,
                    d.nama as donatur_nama,
                    d.hp as donatur_hp,
                    d.email as donatur_email
                FROM kunjungan k
                JOIN donatur d ON k.donatur_id = d.id
                WHERE k.id = ?
            ");
            $stmt->execute([$kunjungan_id]);
            $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan berhasil ditambahkan',
                'data' => $kunjungan
            ]);
            break;
            
        case 'PUT':
            // Update kunjungan
            check_csrf();
            
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing kunjungan ID']);
                exit;
            }
            
            // Check if kunjungan belongs to user
            $stmt = $pdo->prepare("SELECT id FROM kunjungan WHERE id = ? AND fundraiser_id = ?");
            $stmt->execute([$id, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kunjungan not found or access denied']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['donatur_id']) || empty($input['alamat']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // Update kunjungan
            $stmt = $pdo->prepare("
                UPDATE kunjungan 
                SET donatur_id = ?, alamat = ?, status = ?, nominal = ?, catatan = ?, waktu = NOW(), updated_at = NOW()
                WHERE id = ? AND fundraiser_id = ?
            ");
            
            $stmt->execute([
                $input['donatur_id'],
                $input['alamat'],
                $input['status'],
                $input['nominal'] ?? 0,
                $input['catatan'] ?? '',
                $id,
                $user_id
            ]);
            
            // Get the updated kunjungan
            $stmt = $pdo->prepare("
                SELECT 
                    k.id,
                    k.fundraiser_id,
                    k.donatur_id,
                    k.alamat,
                    k.status,
                    k.nominal,
                    k.catatan,
                    k.created_at,
                    k.updated_at,
                    d.nama as donatur_nama,
                    d.hp as donatur_hp,
                    d.email as donatur_email
                FROM kunjungan k
                JOIN donatur d ON k.donatur_id = d.id
                WHERE k.id = ?
            ");
            $stmt->execute([$id]);
            $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan berhasil diperbarui',
                'data' => $kunjungan
            ]);
            break;
            
        case 'DELETE':
            // Delete kunjungan (disabled for user role)
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Delete operation not allowed for user role']);
            exit;
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>