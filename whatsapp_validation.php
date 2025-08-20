<?php
/**
 * WhatsApp API System Validation
 * Comprehensive validation for all WhatsApp components
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login first'
    ]);
    exit();
}

// Database connection
require_once 'config.php';
require_once 'app_settings.php';

$validation_results = [];

try {
    // 1. Validate Database Tables
    $validation_results['database_tables'] = validateDatabaseTables();
    
    // 2. Validate File Structure
    $validation_results['file_structure'] = validateFileStructure();
    
    // 3. Validate API Configuration
    $validation_results['api_configuration'] = validateAPIConfiguration();
    
    // 4. Validate Dependencies
    $validation_results['dependencies'] = validateDependencies();
    
    // 5. Validate Templates
    $validation_results['templates'] = validateTemplates();
    
    // 6. Validate Integration
    $validation_results['integration'] = validateIntegration();
    
    // 7. Validate Permissions
    $validation_results['permissions'] = validatePermissions();
    
    // Summary
    $total_checks = 0;
    $passed_checks = 0;
    
    foreach ($validation_results as $category => $results) {
        foreach ($results as $check) {
            $total_checks++;
            if ($check['status'] === 'PASS') {
                $passed_checks++;
            }
        }
    }
    
    $validation_results['summary'] = [
        'total_checks' => $total_checks,
        'passed_checks' => $passed_checks,
        'failed_checks' => $total_checks - $passed_checks,
        'success_rate' => round(($passed_checks / $total_checks) * 100, 2),
        'overall_status' => ($passed_checks === $total_checks) ? 'PASS' : 'FAIL'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Validation completed',
        'data' => $validation_results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed: ' . $e->getMessage(),
        'data' => $validation_results ?? []
    ]);
}

/**
 * Validate Database Tables
 */
function validateDatabaseTables() {
    global $pdo;
    $results = [];
    
    // Check whatsapp_messages table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_messages'");
        $results[] = [
            'check' => 'whatsapp_messages table exists',
            'status' => $stmt->rowCount() > 0 ? 'PASS' : 'FAIL',
            'details' => $stmt->rowCount() > 0 ? 'Table found' : 'Table not found'
        ];
    } catch (Exception $e) {
        $results[] = [
            'check' => 'whatsapp_messages table exists',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    // Check whatsapp_templates table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_templates'");
        $results[] = [
            'check' => 'whatsapp_templates table exists',
            'status' => $stmt->rowCount() > 0 ? 'PASS' : 'FAIL',
            'details' => $stmt->rowCount() > 0 ? 'Table found' : 'Table not found'
        ];
    } catch (Exception $e) {
        $results[] = [
            'check' => 'whatsapp_templates table exists',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    // Check api_logs table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'api_logs'");
        $results[] = [
            'check' => 'api_logs table exists',
            'status' => $stmt->rowCount() > 0 ? 'PASS' : 'FAIL',
            'details' => $stmt->rowCount() > 0 ? 'Table found' : 'Table not found'
        ];
    } catch (Exception $e) {
        $results[] = [
            'check' => 'api_logs table exists',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    // Check template count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM whatsapp_templates");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $results[] = [
            'check' => 'Default templates loaded',
            'status' => $count >= 7 ? 'PASS' : 'FAIL',
            'details' => "Found $count templates (expected >= 7)"
        ];
    } catch (Exception $e) {
        $results[] = [
            'check' => 'Default templates loaded',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    return $results;
}

/**
 * Validate File Structure
 */
function validateFileStructure() {
    $results = [];
    $required_files = [
        'whatsapp_api.php',
        'whatsapp-manager.php',
        'whatsapp_settings.php',
        'whatsapp_test_connection.php',
        'whatsapp_templates_table.sql',
        'api_database_tables.sql',
        'app_settings.php'
    ];
    
    foreach ($required_files as $file) {
        $results[] = [
            'check' => "File exists: $file",
            'status' => file_exists($file) ? 'PASS' : 'FAIL',
            'details' => file_exists($file) ? 'File found' : 'File not found'
        ];
    }
    
    return $results;
}

/**
 * Validate API Configuration
 */
function validateAPIConfiguration() {
    $results = [];
    
    // Check WhatsApp settings
    $base_url = get_app_setting('whatsapp_base_url');
    $app_key = get_app_setting('whatsapp_app_key');
    $auth_key = get_app_setting('whatsapp_auth_key');
    $sandbox = get_app_setting('whatsapp_sandbox');
    
    $results[] = [
        'check' => 'WhatsApp base URL configured',
        'status' => !empty($base_url) ? 'PASS' : 'FAIL',
        'details' => !empty($base_url) ? "URL: $base_url" : 'Not configured'
    ];
    
    $results[] = [
        'check' => 'WhatsApp app key configured',
        'status' => !empty($app_key) ? 'PASS' : 'FAIL',
        'details' => !empty($app_key) ? 'App key set' : 'Not configured'
    ];
    
    $results[] = [
        'check' => 'WhatsApp auth key configured',
        'status' => !empty($auth_key) ? 'PASS' : 'FAIL',
        'details' => !empty($auth_key) ? 'Auth key set' : 'Not configured'
    ];
    
    $results[] = [
        'check' => 'WhatsApp sandbox mode configured',
        'status' => isset($sandbox) ? 'PASS' : 'FAIL',
        'details' => isset($sandbox) ? "Sandbox: " . ($sandbox ? 'Enabled' : 'Disabled') : 'Not configured'
    ];
    
    return $results;
}

/**
 * Validate Dependencies
 */
function validateDependencies() {
    $results = [];
    
    // Check if cURL is available
    $results[] = [
        'check' => 'cURL extension available',
        'status' => function_exists('curl_init') ? 'PASS' : 'FAIL',
        'details' => function_exists('curl_init') ? 'cURL enabled' : 'cURL not available'
    ];
    
    // Check if JSON functions are available
    $results[] = [
        'check' => 'JSON extension available',
        'status' => function_exists('json_encode') ? 'PASS' : 'FAIL',
        'details' => function_exists('json_encode') ? 'JSON enabled' : 'JSON not available'
    ];
    
    // Check if PDO is available
    $results[] = [
        'check' => 'PDO extension available',
        'status' => class_exists('PDO') ? 'PASS' : 'FAIL',
        'details' => class_exists('PDO') ? 'PDO enabled' : 'PDO not available'
    ];
    
    // Check if config.php exists and is readable
    $results[] = [
        'check' => 'config.php accessible',
        'status' => file_exists('config.php') && is_readable('config.php') ? 'PASS' : 'FAIL',
        'details' => file_exists('config.php') && is_readable('config.php') ? 'File accessible' : 'File not accessible'
    ];
    
    return $results;
}

/**
 * Validate Templates
 */
function validateTemplates() {
    global $pdo;
    $results = [];
    
    try {
        // Check if templates have required fields
        $stmt = $pdo->query("SELECT template_id, name, message FROM whatsapp_templates WHERE template_id IN ('welcome_donor', 'kunjungan_success')");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($templates as $template) {
            $results[] = [
                'check' => "Template valid: {$template['template_id']}",
                'status' => (!empty($template['name']) && !empty($template['message'])) ? 'PASS' : 'FAIL',
                'details' => "Name: {$template['name']}, Message length: " . strlen($template['message'])
            ];
        }
        
        // Check if templates contain variables
        $stmt = $pdo->query("SELECT template_id, message FROM whatsapp_templates WHERE message LIKE '%{nama_donatur}%'");
        $variable_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results[] = [
            'check' => 'Templates contain variables',
            'status' => count($variable_templates) > 0 ? 'PASS' : 'FAIL',
            'details' => count($variable_templates) . ' templates with variables found'
        ];
        
    } catch (Exception $e) {
        $results[] = [
            'check' => 'Template validation',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    return $results;
}

/**
 * Validate Integration
 */
function validateIntegration() {
    $results = [];
    
    // Check if WhatsApp API class can be instantiated
    try {
        if (file_exists('whatsapp_api.php')) {
            require_once 'whatsapp_api.php';
            $results[] = [
                'check' => 'WhatsApp API class loadable',
                'status' => 'PASS',
                'details' => 'Class can be loaded'
            ];
        } else {
            $results[] = [
                'check' => 'WhatsApp API class loadable',
                'status' => 'FAIL',
                'details' => 'File not found'
            ];
        }
    } catch (Exception $e) {
        $results[] = [
            'check' => 'WhatsApp API class loadable',
            'status' => 'FAIL',
            'details' => 'Error: ' . $e->getMessage()
        ];
    }
    
    // Check if sidebar integration exists
    $sidebar_content = file_get_contents('sidebar-admin.php');
    $results[] = [
        'check' => 'Sidebar integration',
        'status' => (strpos($sidebar_content, 'whatsapp-manager.php') !== false) ? 'PASS' : 'FAIL',
        'details' => (strpos($sidebar_content, 'whatsapp-manager.php') !== false) ? 'WhatsApp Manager link found' : 'WhatsApp Manager link not found'
    ];
    
    // Check if kunjungan page integration exists
    $kunjungan_content = file_get_contents('kunjungan.php');
    $results[] = [
        'check' => 'Kunjungan page integration',
        'status' => (strpos($kunjungan_content, 'sendWhatsAppNotification') !== false) ? 'PASS' : 'FAIL',
        'details' => (strpos($kunjungan_content, 'sendWhatsAppNotification') !== false) ? 'WhatsApp button found' : 'WhatsApp button not found'
    ];
    
    return $results;
}

/**
 * Validate Permissions
 */
function validatePermissions() {
    $results = [];
    
    // Check if uploads directory exists and is writable
    $uploads_dir = 'uploads';
    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }
    
    $results[] = [
        'check' => 'Uploads directory writable',
        'status' => is_writable($uploads_dir) ? 'PASS' : 'FAIL',
        'details' => is_writable($uploads_dir) ? 'Directory writable' : 'Directory not writable'
    ];
    
    // Check if logs directory exists and is writable
    $logs_dir = 'logs';
    if (!file_exists($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    $results[] = [
        'check' => 'Logs directory writable',
        'status' => is_writable($logs_dir) ? 'PASS' : 'FAIL',
        'details' => is_writable($logs_dir) ? 'Directory writable' : 'Directory not writable'
    ];
    
    return $results;
}
?>