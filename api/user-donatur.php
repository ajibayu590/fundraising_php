<?php
// API CRUD untuk Donatur User (Fundraiser)
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
            // Get donatur data for specific user
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                // Get specific donatur (only if user has visited this donatur)
                $stmt = $pdo->prepare("
                    SELECT d.*
                    FROM donatur d
                    WHERE d.id = ? AND d.id IN (SELECT DISTINCT donatur_id FROM kunjungan WHERE fundraiser_id = ?)
                ");
                $stmt->execute([$id, $user_id]);
                $donatur = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$donatur) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Donatur not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $donatur]);
            } else {
                // Get all donatur that user has visited
                $stmt = $pdo->prepare("
                    SELECT d.*
                    FROM donatur d
                    WHERE d.id IN (SELECT DISTINCT donatur_id FROM kunjungan WHERE fundraiser_id = ?)
                    ORDER BY d.nama ASC
                ");
                $stmt->execute([$user_id]);
                $donatur = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $donatur,
                    'total' => count($donatur)
                ]);
            }
            break;
            
        case 'POST':
            // Create new donatur
            check_csrf();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['nama']) || empty($input['hp'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama dan HP wajib diisi']);
                exit;
            }
            
            // Check if donatur with same HP already exists
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ?");
            $stmt->execute([$input['hp']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Donatur dengan HP tersebut sudah ada']);
                exit;
            }
            
            // Insert new donatur
            $stmt = $pdo->prepare("
                INSERT INTO donatur (nama, hp, email, alamat, kategori, catatan, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['nama'],
                $input['hp'],
                $input['email'] ?? '',
                $input['alamat'] ?? '',
                $input['kategori'] ?? 'perorangan',
                $input['catatan'] ?? ''
            ]);
            
            $donatur_id = $pdo->lastInsertId();
            
            // Get the created donatur
            $stmt = $pdo->prepare("SELECT * FROM donatur WHERE id = ?");
            $stmt->execute([$donatur_id]);
            $donatur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Donatur berhasil ditambahkan',
                'data' => $donatur
            ]);
            break;
            
        case 'PUT':
            // Update donatur
            check_csrf();
            
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing donatur ID']);
                exit;
            }
            
            // Check if donatur exists and user has visited this donatur
            $stmt = $pdo->prepare("
                SELECT id FROM donatur 
                WHERE id = ? AND id IN (SELECT DISTINCT donatur_id FROM kunjungan WHERE fundraiser_id = ?)
            ");
            $stmt->execute([$id, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Donatur not found or access denied']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['nama']) || empty($input['hp'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama dan HP wajib diisi']);
                exit;
            }
            
            // Check if HP is already used by another donatur
            $stmt = $pdo->prepare("SELECT id FROM donatur WHERE hp = ? AND id != ?");
            $stmt->execute([$input['hp'], $id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'HP sudah digunakan oleh donatur lain']);
                exit;
            }
            
            // Update donatur
            $stmt = $pdo->prepare("
                UPDATE donatur 
                SET nama = ?, hp = ?, email = ?, alamat = ?, kategori = ?, catatan = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['nama'],
                $input['hp'],
                $input['email'] ?? '',
                $input['alamat'] ?? '',
                $input['kategori'] ?? 'perorangan',
                $input['catatan'] ?? '',
                $id
            ]);
            
            // Get the updated donatur
            $stmt = $pdo->prepare("SELECT * FROM donatur WHERE id = ?");
            $stmt->execute([$id]);
            $donatur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Donatur berhasil diperbarui',
                'data' => $donatur
            ]);
            break;
            
        case 'DELETE':
            // Delete donatur (disabled for user role)
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