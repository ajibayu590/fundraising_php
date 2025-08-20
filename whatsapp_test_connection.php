<?php
/**
 * WhatsApp Test Connection
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
    $app_key = get_app_setting('whatsapp_app_key') ?: 'e98095ab-363d-47a4-b3b6-af99d68ef2b8';
    $auth_key = get_app_setting('whatsapp_auth_key') ?: 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT';
    $sandbox = get_app_setting('whatsapp_sandbox') ?: false;
    
    // Test connection
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
        CURLOPT_POSTFIELDS => [
            'appkey' => $app_key,
            'authkey' => $auth_key,
            'to' => '6281234567890',
            'message' => 'Test connection from Fundraising System - ' . date('Y-m-d H:i:s'),
            'sandbox' => $sandbox ? 'true' : 'false'
        ],
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
            'message' => 'Connection failed: ' . $error
        ]);
        exit();
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['message_status']) && $responseData['message_status'] === 'Success') {
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp API connection successful',
            'data' => [
                'base_url' => $base_url,
                'http_code' => $httpCode,
                'response' => $responseData,
                'sandbox_mode' => $sandbox
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'WhatsApp API connection failed',
            'data' => [
                'base_url' => $base_url,
                'http_code' => $httpCode,
                'response' => $responseData,
                'raw_response' => $response
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Test connection failed: ' . $e->getMessage()
    ]);
}
?>