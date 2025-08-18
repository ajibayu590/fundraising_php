<?php
// AUDIT KEAMANAN: CSRF, session, role, validasi input, rate limit, logging
session_start();
header('Content-Type: application/json');
require_once '../config.php';

// Check CSRF
check_csrf();

// Helper: cek login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Helper: cek role
function require_role($roles) {
    if (!in_array($_SESSION['user_role'], (array)$roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

// Helper: ambil data input JSON
function get_json_input() {
    return json_decode(file_get_contents('php://input'), true);
}

// Helper: validasi setting key
function validate_setting_key($key) {
    $allowedKeys = [
        'app_name', 'version', 'company_name', 'company_address', 'company_phone', 
        'company_email', 'target_monthly', 'target_yearly', 'currency_format',
        'app_logo', 'theme_color', 'notification_enabled', 'auto_backup'
    ];
    return in_array($key, $allowedKeys);
}

// Helper: validasi setting value
function validate_setting_value($key, $value) {
    switch ($key) {
        case 'target_monthly':
        case 'target_yearly':
            return is_numeric($value) && $value >= 0;
        case 'currency_format':
            return in_array($value, ['IDR', 'USD', 'EUR', 'SGD']);
        case 'notification_enabled':
        case 'auto_backup':
            return in_array($value, ['true', 'false', '1', '0', true, false]);
        default:
            return !empty($value);
    }
}

// Helper: sanitasi input
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

require_login();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // GET: List settings atau get setting by key
            if (isset($_GET['key'])) {
                // Get setting by key
                $key = $_GET['key'];
                
                if (!validate_setting_key($key)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid setting key']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT setting_key, setting_value, updated_at FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $setting = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$setting) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Setting not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $setting]);
            } else {
                // List all settings
                $stmt = $pdo->prepare("SELECT setting_key, setting_value, updated_at FROM settings ORDER BY setting_key");
                $stmt->execute();
                $settingsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Convert to associative array
                $settingsArray = [];
                foreach ($settingsList as $setting) {
                    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
                }
                
                echo json_encode(['success' => true, 'data' => $settingsArray]);
            }
            break;

        case 'POST':
            // POST: Create new setting (admin only)
            require_role(['admin']);
            
            $input = get_json_input();
            $input = sanitize_input($input);
            
            // Validasi input
            if (empty($input['key']) || !isset($input['value'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting key and value are required']);
                exit;
            }
            
            if (!validate_setting_key($input['key'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid setting key']);
                exit;
            }
            
            if (!validate_setting_value($input['key'], $input['value'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid setting value']);
                exit;
            }
            
            // Cek apakah setting sudah ada
            $stmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = ?");
            $stmt->execute([$input['key']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting already exists']);
                exit;
            }
            
            // Insert setting
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$input['key'], $input['value']]);
            
            // Get created setting
            $stmt = $pdo->prepare("SELECT setting_key, setting_value, created_at, updated_at FROM settings WHERE setting_key = ?");
            $stmt->execute([$input['key']]);
            $newSetting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => 'Setting created successfully', 'data' => $newSetting]);
            break;

        case 'PUT':
            // PUT: Update setting (admin only)
            require_role(['admin']);
            
            if (!isset($_GET['key'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting key is required']);
                exit;
            }
            
            $key = $_GET['key'];
            
            if (!validate_setting_key($key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid setting key']);
                exit;
            }
            
            // Cek apakah setting ada
            $stmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Setting not found']);
                exit;
            }
            
            $input = get_json_input();
            $input = sanitize_input($input);
            
            // Validasi input
            if (!isset($input['value'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting value is required']);
                exit;
            }
            
            if (!validate_setting_value($key, $input['value'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid setting value']);
                exit;
            }
            
            // Update setting
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$input['value'], $key]);
            
            // Get updated setting
            $stmt = $pdo->prepare("SELECT setting_key, setting_value, updated_at FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $updatedSetting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => 'Setting updated successfully', 'data' => $updatedSetting]);
            break;

        case 'DELETE':
            // DELETE: Delete setting (admin only)
            require_role(['admin']);
            
            if (!isset($_GET['key'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting key is required']);
                exit;
            }
            
            $key = $_GET['key'];
            
            if (!validate_setting_key($key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid setting key']);
                exit;
            }
            
            // Cek apakah setting ada
            $stmt = $pdo->prepare("SELECT setting_key FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Setting not found']);
                exit;
            }
            
            // Cek apakah setting critical (tidak boleh dihapus)
            $criticalSettings = ['app_name', 'version', 'company_name'];
            if (in_array($key, $criticalSettings)) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete critical setting']);
                exit;
            }
            
            // Delete setting
            $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            echo json_encode(['success' => true, 'message' => 'Setting deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Settings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
