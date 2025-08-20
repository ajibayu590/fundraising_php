<?php
/**
 * API Configuration
 * Handles CORS, authentication, and database connection for API endpoints
 */

// Enable CORS for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session for API authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../config.php';

/**
 * API Response Helper Functions
 */
function api_response($data = null, $status = 200, $message = 'Success') {
    http_response_code($status);
    echo json_encode([
        'success' => $status < 400,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit();
}

function api_error($message = 'Error', $status = 400) {
    api_response(null, $status, $message);
}

function api_success($data = null, $message = 'Success') {
    api_response($data, 200, $message);
}

/**
 * Authentication Functions
 */
function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        api_error('Authentication required', 401);
    }
}

function require_admin() {
    require_auth();
    if ($_SESSION['user_role'] !== 'admin') {
        api_error('Admin access required', 403);
    }
}

function require_admin_or_monitor() {
    require_auth();
    if (!in_array($_SESSION['user_role'], ['admin', 'monitor'])) {
        api_error('Admin or Monitor access required', 403);
    }
}

/**
 * Input Validation Functions
 */
function get_json_input() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        api_error('Invalid JSON input');
    }
    
    return $data;
}

function validate_required($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        api_error('Missing required fields: ' . implode(', ', $missing));
    }
}

/**
 * Pagination Helper
 */
function get_pagination_params() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Search and Filter Helper
 */
function get_search_params() {
    return [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'fundraiser_id' => $_GET['fundraiser_id'] ?? '',
        'donatur_id' => $_GET['donatur_id'] ?? ''
    ];
}

/**
 * Log API Activity
 */
function log_api_activity($endpoint, $method, $user_id = null, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO api_logs (endpoint, method, user_id, ip_address, user_agent, details, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $endpoint,
            $method,
            $user_id ?? $_SESSION['user_id'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $details
        ]);
    } catch (Exception $e) {
        // Silently fail for logging errors
        error_log("API Log Error: " . $e->getMessage());
    }
}

/**
 * Rate Limiting (Basic Implementation)
 */
function check_rate_limit($user_id = null, $limit = 100, $window = 3600) {
    $identifier = $user_id ?? $_SERVER['REMOTE_ADDR'];
    $cache_key = "rate_limit_{$identifier}";
    
    // Simple file-based rate limiting (can be replaced with Redis)
    $cache_file = sys_get_temp_dir() . "/{$cache_key}.txt";
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data['count'] >= $limit && (time() - $data['reset_time']) < $window) {
            api_error('Rate limit exceeded. Please try again later.', 429);
        }
        
        if ((time() - $data['reset_time']) >= $window) {
            $data = ['count' => 1, 'reset_time' => time()];
        } else {
            $data['count']++;
        }
    } else {
        $data = ['count' => 1, 'reset_time' => time()];
    }
    
    file_put_contents($cache_file, json_encode($data));
}
?>