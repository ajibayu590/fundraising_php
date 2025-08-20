<?php
/**
 * Kunjungan API
 * Handles CRUD operations for kunjungan data
 */

require_once 'config.php';

// Log API activity
log_api_activity('/api/kunjungan', $_SERVER['REQUEST_METHOD']);

// Check rate limiting
check_rate_limit($_SESSION['user_id'] ?? null, 100, 3600);

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            get_kunjungan($id);
        } else {
            list_kunjungan();
        }
        break;
        
    case 'POST':
        create_kunjungan();
        break;
        
    case 'PUT':
        if ($id) {
            update_kunjungan($id);
        } else {
            api_error('Kunjungan ID required');
        }
        break;
        
    case 'DELETE':
        if ($id) {
            delete_kunjungan($id);
        } else {
            api_error('Kunjungan ID required');
        }
        break;
        
    default:
        api_error('Method not allowed', 405);
}

/**
 * Get single kunjungan
 */
function get_kunjungan($id) {
    require_auth();
    
    try {
        $stmt = $pdo->prepare("
            SELECT k.*, 
                   u.name as fundraiser_name,
                   d.nama as donatur_name,
                   d.hp as donatur_hp,
                   d.alamat as donatur_alamat
            FROM kunjungan k
            JOIN users u ON k.fundraiser_id = u.id
            JOIN donatur d ON k.donatur_id = d.id
            WHERE k.id = ?
        ");
        $stmt->execute([$id]);
        $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kunjungan) {
            api_error('Kunjungan not found', 404);
        }
        
        // Check access rights
        if ($_SESSION['user_role'] === 'user' && $kunjungan['fundraiser_id'] != $_SESSION['user_id']) {
            api_error('Access denied', 403);
        }
        
        api_success($kunjungan, 'Kunjungan retrieved successfully');
        
    } catch (Exception $e) {
        api_error('Failed to get kunjungan: ' . $e->getMessage());
    }
}

/**
 * List kunjungan with filtering and pagination
 */
function list_kunjungan() {
    require_auth();
    
    try {
        $pagination = get_pagination_params();
        $search = get_search_params();
        
        // Build query based on user role
        $where_conditions = [];
        $params = [];
        
        if ($_SESSION['user_role'] === 'user') {
            $where_conditions[] = "k.fundraiser_id = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        if (!empty($search['search'])) {
            $where_conditions[] = "(d.nama LIKE ? OR d.hp LIKE ? OR u.name LIKE ?)";
            $search_term = "%{$search['search']}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($search['status'])) {
            $where_conditions[] = "k.status = ?";
            $params[] = $search['status'];
        }
        
        if (!empty($search['date_from'])) {
            $where_conditions[] = "DATE(k.created_at) >= ?";
            $params[] = $search['date_from'];
        }
        
        if (!empty($search['date_to'])) {
            $where_conditions[] = "DATE(k.created_at) <= ?";
            $params[] = $search['date_to'];
        }
        
        if (!empty($search['fundraiser_id'])) {
            $where_conditions[] = "k.fundraiser_id = ?";
            $params[] = $search['fundraiser_id'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Count total records
        $count_sql = "
            SELECT COUNT(*) as total
            FROM kunjungan k
            JOIN users u ON k.fundraiser_id = u.id
            JOIN donatur d ON k.donatur_id = d.id
            $where_clause
        ";
        
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $sql = "
            SELECT k.*, 
                   u.name as fundraiser_name,
                   d.nama as donatur_name,
                   d.hp as donatur_hp,
                   d.alamat as donatur_alamat
            FROM kunjungan k
            JOIN users u ON k.fundraiser_id = u.id
            JOIN donatur d ON k.donatur_id = d.id
            $where_clause
            ORDER BY k.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $kunjungan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate pagination info
        $total_pages = ceil($total / $pagination['limit']);
        
        api_success([
            'data' => $kunjungan_list,
            'pagination' => [
                'page' => $pagination['page'],
                'limit' => $pagination['limit'],
                'total' => $total,
                'total_pages' => $total_pages,
                'has_next' => $pagination['page'] < $total_pages,
                'has_prev' => $pagination['page'] > 1
            ],
            'filters' => $search
        ], 'Kunjungan list retrieved successfully');
        
    } catch (Exception $e) {
        api_error('Failed to get kunjungan list: ' . $e->getMessage());
    }
}

/**
 * Create new kunjungan
 */
function create_kunjungan() {
    require_auth();
    
    $data = get_json_input();
    validate_required($data, ['donatur_id', 'status', 'nominal']);
    
    try {
        // Validate donatur exists
        $stmt = $pdo->prepare("SELECT id FROM donatur WHERE id = ?");
        $stmt->execute([$data['donatur_id']]);
        if (!$stmt->fetch()) {
            api_error('Donatur not found');
        }
        
        // Set fundraiser_id based on user role
        $fundraiser_id = $_SESSION['user_role'] === 'user' ? $_SESSION['user_id'] : ($data['fundraiser_id'] ?? $_SESSION['user_id']);
        
        // Handle file upload if present
        $foto_path = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/kunjungan/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $foto_path = 'uploads/kunjungan/' . $filename;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO kunjungan (
                fundraiser_id, donatur_id, status, nominal, catatan, 
                foto, latitude, longitude, location_address, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $fundraiser_id,
            $data['donatur_id'],
            $data['status'],
            $data['nominal'],
            $data['catatan'] ?? null,
            $foto_path,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['location_address'] ?? null
        ]);
        
        $kunjungan_id = $pdo->lastInsertId();
        
        // Get created kunjungan
        $stmt = $pdo->prepare("
            SELECT k.*, 
                   u.name as fundraiser_name,
                   d.nama as donatur_name
            FROM kunjungan k
            JOIN users u ON k.fundraiser_id = u.id
            JOIN donatur d ON k.donatur_id = d.id
            WHERE k.id = ?
        ");
        $stmt->execute([$kunjungan_id]);
        $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        api_success($kunjungan, 'Kunjungan created successfully');
        
    } catch (Exception $e) {
        api_error('Failed to create kunjungan: ' . $e->getMessage());
    }
}

/**
 * Update kunjungan
 */
function update_kunjungan($id) {
    require_auth();
    
    $data = get_json_input();
    
    try {
        // Check if kunjungan exists and user has access
        $stmt = $pdo->prepare("SELECT fundraiser_id FROM kunjungan WHERE id = ?");
        $stmt->execute([$id]);
        $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kunjungan) {
            api_error('Kunjungan not found', 404);
        }
        
        if ($_SESSION['user_role'] === 'user' && $kunjungan['fundraiser_id'] != $_SESSION['user_id']) {
            api_error('Access denied', 403);
        }
        
        // Build update query
        $update_fields = [];
        $params = [];
        
        if (isset($data['status'])) {
            $update_fields[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (isset($data['nominal'])) {
            $update_fields[] = "nominal = ?";
            $params[] = $data['nominal'];
        }
        
        if (isset($data['catatan'])) {
            $update_fields[] = "catatan = ?";
            $params[] = $data['catatan'];
        }
        
        if (isset($data['latitude'])) {
            $update_fields[] = "latitude = ?";
            $params[] = $data['latitude'];
        }
        
        if (isset($data['longitude'])) {
            $update_fields[] = "longitude = ?";
            $params[] = $data['longitude'];
        }
        
        if (isset($data['location_address'])) {
            $update_fields[] = "location_address = ?";
            $params[] = $data['location_address'];
        }
        
        if (empty($update_fields)) {
            api_error('No fields to update');
        }
        
        $update_fields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE kunjungan SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated kunjungan
        $stmt = $pdo->prepare("
            SELECT k.*, 
                   u.name as fundraiser_name,
                   d.nama as donatur_name
            FROM kunjungan k
            JOIN users u ON k.fundraiser_id = u.id
            JOIN donatur d ON k.donatur_id = d.id
            WHERE k.id = ?
        ");
        $stmt->execute([$id]);
        $updated_kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        api_success($updated_kunjungan, 'Kunjungan updated successfully');
        
    } catch (Exception $e) {
        api_error('Failed to update kunjungan: ' . $e->getMessage());
    }
}

/**
 * Delete kunjungan
 */
function delete_kunjungan($id) {
    require_admin_or_monitor();
    
    try {
        // Check if kunjungan exists
        $stmt = $pdo->prepare("SELECT foto FROM kunjungan WHERE id = ?");
        $stmt->execute([$id]);
        $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kunjungan) {
            api_error('Kunjungan not found', 404);
        }
        
        // Delete associated photo if exists
        if ($kunjungan['foto'] && file_exists('../' . $kunjungan['foto'])) {
            unlink('../' . $kunjungan['foto']);
        }
        
        // Delete kunjungan
        $stmt = $pdo->prepare("DELETE FROM kunjungan WHERE id = ?");
        $stmt->execute([$id]);
        
        api_success(null, 'Kunjungan deleted successfully');
        
    } catch (Exception $e) {
        api_error('Failed to delete kunjungan: ' . $e->getMessage());
    }
}
?>
