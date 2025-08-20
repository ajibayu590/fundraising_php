<?php
/**
 * WhatsApp API Integration
 * Integrates with Saungwa/Wapanels API for sending WhatsApp messages
 */

require_once 'config.php';

// Log API activity
log_api_activity('/api/whatsapp', $_SERVER['REQUEST_METHOD']);

// Check rate limiting
check_rate_limit($_SESSION['user_id'] ?? null, 50, 3600); // 50 requests per hour

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'send':
                send_whatsapp_message();
                break;
            case 'send_template':
                send_template_message();
                break;
            case 'send_bulk':
                send_bulk_messages();
                break;
            default:
                api_error('Invalid action');
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
            default:
                api_error('Invalid action');
        }
        break;
        
    default:
        api_error('Method not allowed', 405);
}

/**
 * WhatsApp API Configuration
 */
class WhatsAppAPI {
    private $appkey = 'e98095ab-363d-47a4-b3b6-af99d68ef2b8';
    private $authkey = 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT';
    private $base_url = 'https://app.saungwa.com/api';
    private $sandbox = false; // Set to true for testing
    
    /**
     * Send text message
     */
    public function sendTextMessage($to, $message, $file = null) {
        $url = $this->base_url . '/create-message';
        
        $postData = [
            'appkey' => $this->appkey,
            'authkey' => $this->authkey,
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
            'appkey' => $this->appkey,
            'authkey' => $this->authkey,
            'to' => $this->formatPhoneNumber($to),
            'template_id' => $template_id,
            'variables' => $variables,
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
            ]
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
        
        $responseData = json_decode($response, true);
        
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
}

/**
 * Send WhatsApp message
 */
function send_whatsapp_message() {
    require_auth();
    
    $data = get_json_input();
    validate_required($data, ['to', 'message']);
    
    try {
        $whatsapp = new WhatsAppAPI();
        
        $result = $whatsapp->sendTextMessage(
            $data['to'],
            $data['message'],
            $data['file'] ?? null
        );
        
        // Log message
        log_whatsapp_message([
            'user_id' => $_SESSION['user_id'],
            'to' => $data['to'],
            'message' => $data['message'],
            'file' => $data['file'] ?? null,
            'success' => $result['success'],
            'response' => $result['response']
        ]);
        
        if ($result['success']) {
            api_success($result['response'], 'Message sent successfully');
        } else {
            api_error('Failed to send message: ' . ($result['error'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        api_error('Failed to send WhatsApp message: ' . $e->getMessage());
    }
}

/**
 * Send template message
 */
function send_template_message() {
    require_auth();
    
    $data = get_json_input();
    validate_required($data, ['to', 'template_id']);
    
    try {
        $whatsapp = new WhatsAppAPI();
        
        $result = $whatsapp->sendTemplateMessage(
            $data['to'],
            $data['template_id'],
            $data['variables'] ?? []
        );
        
        // Log message
        log_whatsapp_message([
            'user_id' => $_SESSION['user_id'],
            'to' => $data['to'],
            'template_id' => $data['template_id'],
            'variables' => json_encode($data['variables'] ?? []),
            'success' => $result['success'],
            'response' => $result['response']
        ]);
        
        if ($result['success']) {
            api_success($result['response'], 'Template message sent successfully');
        } else {
            api_error('Failed to send template message: ' . ($result['error'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        api_error('Failed to send template message: ' . $e->getMessage());
    }
}

/**
 * Send bulk messages to donors
 */
function send_bulk_messages() {
    require_admin_or_monitor();
    
    $data = get_json_input();
    validate_required($data, ['donor_ids', 'message']);
    
    try {
        $whatsapp = new WhatsAppAPI();
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        // Get donor phone numbers
        $placeholders = str_repeat('?,', count($data['donor_ids']) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur WHERE id IN ($placeholders)");
        $stmt->execute($data['donor_ids']);
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
                $data['message']
            );
            
            $results[] = [
                'donor_id' => $donor['id'],
                'donor_name' => $donor['nama'],
                'phone' => $donor['hp'],
                'success' => $result['success'],
                'response' => $result['response']
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
                'message' => $data['message'],
                'donor_id' => $donor['id'],
                'success' => $result['success'],
                'response' => $result['response']
            ]);
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }
        
        api_success([
            'total' => count($donors),
            'success' => $success_count,
            'errors' => $error_count,
            'results' => $results
        ], "Bulk message sent: $success_count successful, $error_count failed");
        
    } catch (Exception $e) {
        api_error('Failed to send bulk messages: ' . $e->getMessage());
    }
}

/**
 * Get message templates
 */
function get_message_templates() {
    require_auth();
    
    $templates = [
        [
            'id' => 'welcome_donor',
            'name' => 'Welcome Donor',
            'message' => 'Halo {nama}, terima kasih telah mendukung program fundraising kami. Kami akan menghubungi Anda segera.',
            'variables' => ['nama']
        ],
        [
            'id' => 'kunjungan_success',
            'name' => 'Kunjungan Berhasil',
            'message' => 'Halo {nama}, kunjungan fundraising kami telah berhasil. Terima kasih atas donasi sebesar Rp {nominal}.',
            'variables' => ['nama', 'nominal']
        ],
        [
            'id' => 'kunjungan_followup',
            'name' => 'Kunjungan Follow Up',
            'message' => 'Halo {nama}, kami akan melakukan follow up kunjungan fundraising pada {tanggal}. Terima kasih.',
            'variables' => ['nama', 'tanggal']
        ],
        [
            'id' => 'reminder_target',
            'name' => 'Reminder Target',
            'message' => 'Halo {nama}, target kunjungan hari ini adalah {target}. Silakan lakukan kunjungan untuk mencapai target.',
            'variables' => ['nama', 'target']
        ]
    ];
    
    api_success($templates, 'Message templates retrieved successfully');
}

/**
 * Get message history
 */
function get_message_history() {
    require_auth();
    
    try {
        $pagination = get_pagination_params();
        
        // Build query based on user role
        $where_conditions = [];
        $params = [];
        
        if ($_SESSION['user_role'] === 'user') {
            $where_conditions[] = "user_id = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Count total records
        $count_sql = "SELECT COUNT(*) as total FROM whatsapp_messages $where_clause";
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
        
        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_pages = ceil($total / $pagination['limit']);
        
        api_success([
            'data' => $messages,
            'pagination' => [
                'page' => $pagination['page'],
                'limit' => $pagination['limit'],
                'total' => $total,
                'total_pages' => $total_pages
            ]
        ], 'Message history retrieved successfully');
        
    } catch (Exception $e) {
        api_error('Failed to get message history: ' . $e->getMessage());
    }
}

/**
 * Log WhatsApp message
 */
function log_whatsapp_message($data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_messages (
                user_id, donor_id, to_number, message, template_id, 
                variables, file_url, success, response_data, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['donor_id'] ?? null,
            $data['to'] ?? null,
            $data['message'] ?? null,
            $data['template_id'] ?? null,
            $data['variables'] ?? null,
            $data['file'] ?? null,
            $data['success'] ? 1 : 0,
            json_encode($data['response'] ?? [])
        ]);
        
    } catch (Exception $e) {
        error_log("WhatsApp Log Error: " . $e->getMessage());
    }
}
?>