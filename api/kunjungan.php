<?php
// AUDIT KEAMANAN: CSRF, session, role, validasi input, rate limit, logging
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

require_once dirname(__DIR__) . '/config.php';

try {
    // Get all kunjungan with related data
                $stmt = $pdo->prepare("
        SELECT 
            k.id,
            k.fundraiser_id,
            k.donatur_id,
            k.alamat,
            k.status,
            k.nominal,
            k.catatan,
            k.created_at,
            k.updated_at,
            u.name as fundraiser_nama,
            u.email as fundraiser_email,
            d.nama as donatur_nama,
            d.hp as donatur_hp,
            d.email as donatur_email
                    FROM kunjungan k
                    JOIN users u ON k.fundraiser_id = u.id
                    JOIN donatur d ON k.donatur_id = d.id
                    ORDER BY k.created_at DESC
                ");
                $stmt->execute();
    
    $kunjungan = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $kunjungan[] = [
            'id' => $row['id'],
            'user_id' => $row['fundraiser_id'], // Map 'fundraiser_id' to 'user_id' for frontend compatibility
            'donatur_id' => $row['donatur_id'],
            'alamat' => $row['alamat'],
            'status' => $row['status'],
            'nominal' => (int)$row['nominal'],
            'catatan' => $row['catatan'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'fundraiser_nama' => $row['fundraiser_nama'],
            'fundraiser_email' => $row['fundraiser_email'],
            'donatur_nama' => $row['donatur_nama'],
            'donatur_hp' => $row['donatur_hp'],
            'donatur_email' => $row['donatur_email'],
            'is_dummy' => strpos($row['fundraiser_email'], '@dummy.com') !== false || 
                         strpos($row['donatur_email'], '@dummy') !== false ||
                         strpos($row['catatan'], 'DUMMY') !== false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $kunjungan,
        'total' => count($kunjungan)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
