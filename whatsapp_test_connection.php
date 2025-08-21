<?php
/**
 * WhatsApp Test Connection
 * Updated to use new API format and handle JSON responses properly
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

try {
    // Get WhatsApp settings
    $base_url = get_app_setting('whatsapp_base_url') ?: 'https://app.saungwa.com/api';
    $app_key = get_app_setting('whatsapp_app_key') ?: '659d05f2-bd15-43e8-932f-89a9d68f91db';
    $auth_key = get_app_setting('whatsapp_auth_key') ?: 'v14tk0y8NQACMq5oXEt7JIXn7XgxiEq3s3ERYwPIkqmwjTa0gY';
    $sandbox = get_app_setting('whatsapp_sandbox') ?: '0';

    // Test phone number (replace with your test number)
    $test_number = '6281234567890';
    
    // Prepare test data
    $postData = [
        'appkey' => $app_key,
        'authkey' => $auth_key,
        'to' => $test_number,
        'message' => 'Test connection from Fundraising System - ' . date('Y-m-d H:i:s'),
        'sandbox' => $sandbox ? 'true' : 'false'
    ];

    // Make cURL request
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $base_url . '/create-message',
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
        echo json_encode([
            'success' => false,
            'message' => 'cURL Error: ' . $error,
            'data' => [
                'http_code' => $httpCode,
                'url' => $base_url . '/create-message',
                'post_data' => $postData
            ]
        ]);
        exit();
    }

    // Clean response - remove any trailing commas or invalid JSON
    $response = trim($response);
    if (substr($response, -1) === ',') {
        $response = rtrim($response, ',');
    }

    // Try to decode JSON response
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON response: ' . json_last_error_msg(),
            'data' => [
                'http_code' => $httpCode,
                'raw_response' => $response,
                'url' => $base_url . '/create-message',
                'post_data' => $postData
            ]
        ]);
        exit();
    }

    // Check if request was successful
    $isSuccess = $httpCode === 200 && isset($responseData['message_status']) && $responseData['message_status'] === 'Success';

    echo json_encode([
        'success' => $isSuccess,
        'message' => $isSuccess ? 'Connection successful!' : 'Connection failed',
        'data' => [
            'http_code' => $httpCode,
            'response' => $responseData,
            'raw_response' => $response,
            'url' => $base_url . '/create-message',
            'post_data' => $postData,
            'settings' => [
                'base_url' => $base_url,
                'app_key' => substr($app_key, 0, 20) . '...',
                'auth_key' => substr($auth_key, 0, 20) . '...',
                'sandbox' => $sandbox ? 'true' : 'false'
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => [
            'error_details' => $e->getTraceAsString()
        ]
    ]);
}
?>