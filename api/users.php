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
    // Get all users
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.hp,
            u.target,
            u.role,
            u.status,
            u.created_at,
            u.updated_at,
            COALESCE(COUNT(k.id), 0) as total_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id
        GROUP BY u.id, u.name, u.email, u.hp, u.target, u.role, u.status, u.created_at, u.updated_at
        ORDER BY u.name
    ");
                $stmt->execute();
    
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate today's kunjungan
        $todayStmt = $pdo->prepare("
            SELECT COUNT(*) as kunjungan_hari_ini 
            FROM kunjungan 
            WHERE fundraiser_id = ? AND DATE(created_at) = CURDATE()
        ");
        $todayStmt->execute([$row['id']]);
        $todayKunjungan = $todayStmt->fetchColumn();
        
        $users[] = [
            'id' => $row['id'],
            'nama' => $row['name'], // Map 'name' to 'nama' for frontend compatibility
            'email' => $row['email'],
            'hp' => $row['hp'],
            'target' => (int)$row['target'],
            'role' => $row['role'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'total_kunjungan' => (int)$row['total_kunjungan'],
            'total_donasi' => (int)$row['total_donasi'],
            'kunjungan_hari_ini' => (int)$todayKunjungan,
            'is_dummy' => strpos($row['email'], '@dummy.com') !== false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users,
        'total' => count($users)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
