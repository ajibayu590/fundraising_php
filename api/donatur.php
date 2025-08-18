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
    // Get all donatur
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.nama,
            d.hp,
            d.email,
            d.alamat,
            d.kategori,
            d.created_at,
            d.updated_at,
            COALESCE(COUNT(k.id), 0) as jumlah_kunjungan,
            COALESCE(SUM(CASE WHEN k.status = 'berhasil' THEN k.nominal ELSE 0 END), 0) as total_donasi,
            COALESCE(AVG(CASE WHEN k.status = 'berhasil' THEN k.nominal END), 0) as rata_rata_donasi,
            MIN(CASE WHEN k.status = 'berhasil' THEN k.created_at END) as first_donation,
            MAX(CASE WHEN k.status = 'berhasil' THEN k.created_at END) as last_donation
        FROM donatur d
        LEFT JOIN kunjungan k ON d.id = k.donatur_id
        GROUP BY d.id, d.nama, d.hp, d.email, d.alamat, d.kategori, d.created_at, d.updated_at
        ORDER BY d.nama
    ");
                $stmt->execute();
    
    $donatur = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $donatur[] = [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'hp' => $row['hp'],
            'email' => $row['email'],
            'alamat' => $row['alamat'],
            'kategori' => $row['kategori'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'jumlah_kunjungan' => (int)$row['jumlah_kunjungan'],
            'total_donasi' => (int)$row['total_donasi'],
            'rata_rata_donasi' => (int)$row['rata_rata_donasi'],
            'first_donation' => $row['first_donation'],
            'last_donation' => $row['last_donation'],
            'status' => 'aktif', // Default status
            'is_dummy' => strpos($row['email'], '@dummy') !== false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $donatur,
        'total' => count($donatur)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
