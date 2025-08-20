<?php
/**
 * Authentication API
 * Handles login, logout, and user verification
 */

require_once 'config.php';

// Log API activity
log_api_activity('/api/auth', $_SERVER['REQUEST_METHOD']);

// Check rate limiting
check_rate_limit(null, 10, 300); // 10 requests per 5 minutes for auth

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'login':
                handle_login();
                break;
            case 'logout':
                handle_logout();
                break;
            case 'verify':
                handle_verify();
                break;
            default:
                api_error('Invalid action');
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'profile':
                handle_get_profile();
                break;
            case 'verify':
                handle_verify();
                break;
            default:
                api_error('Invalid action');
        }
        break;
        
    default:
        api_error('Method not allowed', 405);
}

/**
 * Handle user login
 */
function handle_login() {
    $data = get_json_input();
    validate_required($data, ['username', 'password']);
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    try {
        // Get user by username or email
        $stmt = $pdo->prepare("
            SELECT id, name, username, email, password, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            api_error('Invalid username or password', 401);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        // Return user data (without password)
        unset($user['password']);
        
        api_success([
            'user' => $user,
            'session_id' => session_id()
        ], 'Login successful');
        
    } catch (Exception $e) {
        api_error('Login failed: ' . $e->getMessage());
    }
}

/**
 * Handle user logout
 */
function handle_logout() {
    // Clear session
    session_destroy();
    
    api_success(null, 'Logout successful');
}

/**
 * Handle user verification
 */
function handle_verify() {
    require_auth();
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, username, email, role, status, created_at 
            FROM users 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            api_error('User not found', 404);
        }
        
        api_success([
            'user' => $user,
            'session_id' => session_id()
        ], 'User verified');
        
    } catch (Exception $e) {
        api_error('Verification failed: ' . $e->getMessage());
    }
}

/**
 * Get user profile
 */
function handle_get_profile() {
    require_auth();
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, username, email, role, status, phone, target, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            api_error('User not found', 404);
        }
        
        // Get user statistics
        $stats = get_user_stats($_SESSION['user_id']);
        
        api_success([
            'user' => $user,
            'stats' => $stats
        ], 'Profile retrieved successfully');
        
    } catch (Exception $e) {
        api_error('Failed to get profile: ' . $e->getMessage());
    }
}

/**
 * Get user statistics
 */
function get_user_stats($user_id) {
    try {
        // Get kunjungan stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_kunjungan,
                COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as berhasil,
                COUNT(CASE WHEN status = 'tidak-berhasil' THEN 1 END) as tidak_berhasil,
                COUNT(CASE WHEN status = 'follow-up' THEN 1 END) as follow_up,
                SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END) as total_donasi
            FROM kunjungan 
            WHERE fundraiser_id = ?
        ");
        $stmt->execute([$user_id]);
        $kunjungan_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get today's stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as today_kunjungan,
                SUM(CASE WHEN status = 'berhasil' THEN nominal ELSE 0 END) as today_donasi
            FROM kunjungan 
            WHERE fundraiser_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$user_id]);
        $today_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'kunjungan' => $kunjungan_stats,
            'today' => $today_stats
        ];
        
    } catch (Exception $e) {
        return [
            'kunjungan' => [],
            'today' => []
        ];
    }
}
?>