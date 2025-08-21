<?php
/**
 * WhatsApp API for Fundraising System
 * Focused on database integration and fundraising data
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
require_once 'config.php';
require_once 'app_settings.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login first'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'send_message':
                send_whatsapp_message();
                break;
            case 'send_template':
                send_template_message();
                break;
            case 'send_bulk':
                send_bulk_messages();
                break;
            case 'send_kunjungan_notification':
                send_kunjungan_notification();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'templates':
                get_message_templates();
                break;
            case 'history':
                get_message_history();
                break;
            case 'test_connection':
                test_whatsapp_connection();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * WhatsApp API Class
 */
class WhatsAppAPI {
    private $base_url;
    private $app_key;
    private $auth_key;
    private $sandbox;
    
    public function __construct() {
        $this->base_url = get_app_setting('whatsapp_base_url') ?: 'https://app.saungwa.com/api';
        $this->app_key = get_app_setting('whatsapp_app_key') ?: 'e98095ab-363d-47a4-b3b6-af99d68ef2b8';
        $this->auth_key = get_app_setting('whatsapp_auth_key') ?: 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT';
        $this->sandbox = get_app_setting('whatsapp_sandbox') ?: false;
    }
    
    /**
     * Send text message
     */
    public function sendTextMessage($to, $message, $file = null) {
        $url = $this->base_url . '/create-message';
        
        $postData = [
            'appkey' => $this->app_key,
            'authkey' => $this->auth_key,
            'to' => $this->formatPhoneNumber($to),
            'message' => $message,
            'sandbox' => $this->sandbox ? 'true' : 'false'
        ];
        
        if ($file) {
            $postData['file'] = $file;
        }
        
        return $this->makeRequest($url, $postData);
    }
    
    /**
     * Send template message
     */
    public function sendTemplateMessage($to, $template_id, $variables = []) {
        $url = $this->base_url . '/create-message';
        
        $postData = [
            'appkey' => $this->app_key,
            'authkey' => $this->auth_key,
            'to' => $this->formatPhoneNumber($to),
            'template_id' => $template_id,
            'variables' => json_encode($variables),
            'sandbox' => $this->sandbox ? 'true' : 'false'
        ];
        
        return $this->makeRequest($url, $postData);
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($url, $postData) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            // SSL Settings to handle certificate issues
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }
        
        // Clean response - remove any trailing commas or invalid JSON
        $response = trim($response);
        if (substr($response, -1) === ',') {
            $response = rtrim($response, ',');
        }
        
        $responseData = json_decode($response, true);
        
        // Check if JSON decode failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }
        
        return [
            'success' => $httpCode === 200 && isset($responseData['message_status']) && $responseData['message_status'] === 'Success',
            'http_code' => $httpCode,
            'response' => $responseData,
            'raw_response' => $response
        ];
    }
    
    /**
     * Format phone number
     */
    private function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (strlen($phone) === 10 && substr($phone, 0, 1) !== '0') {
            $phone = '62' . $phone;
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Test connection
     */
    public function testConnection() {
        return $this->sendTextMessage('6281234567890', 'Test connection from Fundraising System');
    }
}

/**
 * Send WhatsApp message
 */
function send_whatsapp_message() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['to']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: to, message']);
        return;
    }
    
    try {
        $whatsapp = new WhatsAppAPI();
        
        $result = $whatsapp->sendTextMessage(
            $input['to'],
            $input['message'],
            $input['file'] ?? null
        );
        
        // Log message
        log_whatsapp_message([
            'user_id' => $_SESSION['user_id'],
            'to' => $input['to'],
            'message' => $input['message'],
            'file' => $input['file'] ?? null,
            'success' => $result['success'],
            'response' => $result['response'] ?? null
        ]);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $result['response'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send message: ' . ($result['error'] ?? 'Unknown error')
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send WhatsApp message: ' . $e->getMessage()
        ]);
    }
}

/**
 * Send template message
 */
function send_template_message() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['to']) || !isset($input['template_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: to, template_id']);
        return;
    }
    
    try {
        $whatsapp = new WhatsAppAPI();
        
        // Convert variables to proper format if it's a string
        $variables = $input['variables'] ?? [];
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }
        
        $result = $whatsapp->sendTemplateMessage(
            $input['to'],
            $input['template_id'],
            $variables
        );
        
        // Log message
        log_whatsapp_message([
            'user_id' => $_SESSION['user_id'],
            'to' => $input['to'],
            'template_id' => $input['template_id'],
            'variables' => json_encode($input['variables'] ?? []),
            'success' => $result['success'],
            'response' => $result['response'] ?? null
        ]);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Template message sent successfully',
                'data' => $result['response'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send template message: ' . ($result['error'] ?? 'Unknown error')
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send template message: ' . $e->getMessage()
        ]);
    }
}

/**
 * Send bulk messages to donors
 */
function send_bulk_messages() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['donor_ids']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: donor_ids, message']);
        return;
    }
    
    try {
        global $pdo;
        $whatsapp = new WhatsAppAPI();
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        // Get donor phone numbers
        $placeholders = str_repeat('?,', count($input['donor_ids']) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur WHERE id IN ($placeholders)");
        $stmt->execute($input['donor_ids']);
        $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($donors as $donor) {
            if (empty($donor['hp'])) {
                $results[] = [
                    'donor_id' => $donor['id'],
                    'donor_name' => $donor['nama'],
                    'success' => false,
                    'error' => 'No phone number'
                ];
                $error_count++;
                continue;
            }
            
            $result = $whatsapp->sendTextMessage(
                $donor['hp'],
                $input['message']
            );
            
            $results[] = [
                'donor_id' => $donor['id'],
                'donor_name' => $donor['nama'],
                'phone' => $donor['hp'],
                'success' => $result['success'],
                'response' => $result['response'] ?? null
            ];
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
            
            // Log individual message
            log_whatsapp_message([
                'user_id' => $_SESSION['user_id'],
                'to' => $donor['hp'],
                'message' => $input['message'],
                'donor_id' => $donor['id'],
                'success' => $result['success'],
                'response' => $result['response'] ?? null
            ]);
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Bulk message sent: $success_count successful, $error_count failed",
            'data' => [
                'total' => count($donors),
                'success' => $success_count,
                'errors' => $error_count,
                'results' => $results
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send bulk messages: ' . $e->getMessage()
        ]);
    }
}

/**
 * Send kunjungan notification with database data
 */
function send_kunjungan_notification() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['kunjungan_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required field: kunjungan_id']);
        return;
    }
    
    try {
        global $pdo;
        $whatsapp = new WhatsAppAPI();
        
        // Get kunjungan data with donor and fundraiser info
        $stmt = $pdo->prepare("
            SELECT k.*, d.nama as donor_name, d.hp as donor_hp, d.alamat as donor_alamat,
                   u.name as fundraiser_name
            FROM kunjungan k
            LEFT JOIN donatur d ON k.donatur_id = d.id
            LEFT JOIN users u ON k.fundraiser_id = u.id
            WHERE k.id = ?
        ");
        $stmt->execute([$input['kunjungan_id']]);
        $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kunjungan) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Kunjungan not found']);
            return;
        }
        
        if (empty($kunjungan['donor_hp'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Donor has no phone number']);
            return;
        }
        
        // Get template
        $template_id = $input['template_id'] ?? 'kunjungan_success';
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_templates WHERE template_id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template not found']);
            return;
        }
        
        // Prepare variables
        $variables = [
            '{nama_donatur}' => $kunjungan['donor_name'],
            '{nominal_donasi}' => 'Rp ' . number_format($kunjungan['nominal'], 0, ',', '.'),
            '{tanggal_kunjungan}' => date('d/m/Y H:i', strtotime($kunjungan['created_at'])),
            '{nama_fundraiser}' => $kunjungan['fundraiser_name'],
            '{status_kunjungan}' => ucfirst($kunjungan['status']),
            '{alamat_donatur}' => $kunjungan['donor_alamat'],
            '{hp_donatur}' => $kunjungan['donor_hp']
        ];
        
                       // Replace variables in message
               $message = $template['message'];
               foreach ($variables as $key => $value) {
                   $message = str_replace($key, $value, $message);
               }
               
               // Convert variables to proper format for API
               $api_variables = [];
               foreach ($variables as $key => $value) {
                   $api_variables[$key] = $value;
               }
        
        // Send message
        $result = $whatsapp->sendTextMessage($kunjungan['donor_hp'], $message);
        
        // Log message
        log_whatsapp_message([
            'user_id' => $_SESSION['user_id'],
            'to' => $kunjungan['donor_hp'],
            'message' => $message,
            'donor_id' => $kunjungan['donatur_id'],
            'template_id' => $template_id,
            'variables' => json_encode($variables),
            'success' => $result['success'],
            'response' => $result['response'] ?? null
        ]);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Kunjungan notification sent successfully',
                'data' => [
                    'kunjungan_id' => $kunjungan['id'],
                    'donor_name' => $kunjungan['donor_name'],
                    'donor_phone' => $kunjungan['donor_hp'],
                    'message' => $message,
                    'response' => $result['response'] ?? null
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send kunjungan notification: ' . ($result['error'] ?? 'Unknown error')
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send kunjungan notification: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get message templates
 */
function get_message_templates() {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_templates ORDER BY name");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Templates retrieved successfully',
            'data' => $templates
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get templates: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get message history
 */
function get_message_history() {
    try {
        global $pdo;
        
        $page = $_GET['page'] ?? 1;
        $limit = min($_GET['limit'] ?? 20, 100);
        $offset = ($page - 1) * $limit;
        
        // Build query based on user role
        $where_conditions = [];
        $params = [];
        
        if ($_SESSION['user_role'] === 'user') {
            $where_conditions[] = "wm.user_id = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Count total records
        $count_sql = "SELECT COUNT(*) as total FROM whatsapp_messages wm $where_clause";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $sql = "
            SELECT wm.*, u.name as user_name, d.nama as donor_name
            FROM whatsapp_messages wm
            LEFT JOIN users u ON wm.user_id = u.id
            LEFT JOIN donatur d ON wm.donor_id = d.id
            $where_clause
            ORDER BY wm.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_pages = ceil($total / $limit);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message history retrieved successfully',
            'data' => [
                'data' => $messages,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $total_pages
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get message history: ' . $e->getMessage()
        ]);
    }
}

/**
 * Test WhatsApp connection
 */
function test_whatsapp_connection() {
    try {
        $whatsapp = new WhatsAppAPI();
        $result = $whatsapp->testConnection();
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'WhatsApp API connection successful',
                'data' => $result['response'] ?? null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'WhatsApp API connection failed: ' . ($result['error'] ?? 'Unknown error')
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Connection test failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Log WhatsApp message
 */
function log_whatsapp_message($data) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_messages (
                user_id, donor_id, to_number, message, template_id, 
                variables, file_url, success, response_data, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $responseData = $data['response'] ?? [];
        if (is_string($responseData)) {
            $responseData = ['raw_response' => $responseData];
        }
        
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['donor_id'] ?? null,
            $data['to'] ?? null,
            $data['message'] ?? null,
            $data['template_id'] ?? null,
            $data['variables'] ?? null,
            $data['file'] ?? null,
            $data['success'] ? 1 : 0,
            json_encode($responseData)
        ]);
        
    } catch (Exception $e) {
        error_log("WhatsApp Log Error: " . $e->getMessage());
    }
}
?>