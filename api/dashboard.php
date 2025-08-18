<?php
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

// Database connection
require_once dirname(__DIR__) . '/config.php';

try {
    // Get dashboard statistics
    $stats = getDashboardStats($pdo);
    $progress = getProgressData($pdo);
    $recentActivities = getRecentActivities($pdo);
    $dummyDataInfo = getDummyDataInfo($pdo);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'progress' => $progress,
        'recent_activities' => $recentActivities,
        'dummy_data_info' => $dummyDataInfo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getDashboardStats($pdo) {
    $today = date('Y-m-d');
    
    // Total kunjungan hari ini (including dummy)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $totalKunjunganHariIni = $stmt->fetchColumn();
    
    // Donasi berhasil hari ini (including dummy)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $donasiBerhasilHariIni = $stmt->fetchColumn();
    
    // Total donasi hari ini (including dummy)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kunjungan WHERE DATE(created_at) = ? AND status = 'berhasil'");
    $stmt->execute([$today]);
    $totalDonasiHariIni = $stmt->fetchColumn();
    
    // Fundraiser aktif (including dummy)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $fundraiserAktif = $stmt->fetchColumn();
    
    return [
        'total_kunjungan_hari_ini' => $totalKunjunganHariIni,
        'donasi_berhasil_hari_ini' => $donasiBerhasilHariIni,
        'total_donasi_hari_ini' => $totalDonasiHariIni,
        'fundraiser_aktif' => $fundraiserAktif
    ];
}

function getProgressData($pdo) {
    $today = date('Y-m-d');
    
    // Get all users with their targets and current progress (including dummy)
    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            u.target,
            COALESCE(COUNT(k.id), 0) as current,
            u.email
        FROM users u
        LEFT JOIN kunjungan k ON u.id = k.fundraiser_id AND DATE(k.created_at) = ?
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.target, u.email
        ORDER BY u.name
    ");
    $stmt->execute([$today]);
    
    $progress = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isDummy = strpos($row['email'], '@dummy.com') !== false;
        $progress[] = [
            'name' => $row['name'],
            'target' => (int)$row['target'],
            'current' => (int)$row['current'],
            'is_dummy' => $isDummy
        ];
    }
    
    return $progress;
}

function getRecentActivities($pdo) {
    // Get recent kunjungan activities (including dummy)
    $stmt = $pdo->prepare("
        SELECT 
            k.status,
            k.nominal,
            k.created_at,
            k.catatan,
            u.name as fundraiser_name,
            u.email as fundraiser_email,
            d.nama as donatur_name,
            d.email as donatur_email
        FROM kunjungan k
        JOIN users u ON k.fundraiser_id = u.id
        JOIN donatur d ON k.donatur_id = d.id
        ORDER BY k.created_at DESC
        LIMIT 15
    ");
    $stmt->execute();
    
    $activities = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isDummy = strpos($row['fundraiser_email'], '@dummy.com') !== false || 
                   strpos($row['donatur_email'], '@dummy') !== false;
        
        $statusText = '';
        switch ($row['status']) {
            case 'berhasil':
                $statusText = "Kunjungan berhasil oleh {$row['fundraiser_name']} ke {$row['donatur_name']} - Donasi Rp " . number_format($row['nominal'], 0, ',', '.');
                break;
            case 'tidak-berhasil':
                $statusText = "Kunjungan tidak berhasil oleh {$row['fundraiser_name']} ke {$row['donatur_name']}";
                break;
            case 'follow-up':
                $statusText = "Follow-up oleh {$row['fundraiser_name']} ke {$row['donatur_name']}";
                break;
        }
        
        // Add dummy indicator if it's dummy data
        if ($isDummy) {
            $statusText = "[DUMMY] " . $statusText;
        }
        
        $activities[] = [
            'description' => $statusText,
            'time' => date('d/m/Y H:i', strtotime($row['created_at'])),
            'is_dummy' => $isDummy,
            'catatan' => $row['catatan']
        ];
    }
    
    return $activities;
}

function getDummyDataInfo($pdo) {
    // Check if dummy data exists
    $dummyUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@dummy.com'")->fetchColumn();
    $dummyDonatur = $pdo->query("SELECT COUNT(*) FROM donatur WHERE email LIKE '%@dummy%'")->fetchColumn();
    $dummyKunjungan = $pdo->query("SELECT COUNT(*) FROM kunjungan WHERE catatan LIKE '%DUMMY%'")->fetchColumn();
    
    return [
        'has_dummy_data' => ($dummyUsers > 0 || $dummyDonatur > 0 || $dummyKunjungan > 0),
        'dummy_users_count' => $dummyUsers,
        'dummy_donatur_count' => $dummyDonatur,
        'dummy_kunjungan_count' => $dummyKunjungan,
        'warning_message' => ($dummyUsers > 0 || $dummyDonatur > 0 || $dummyKunjungan > 0) 
            ? '⚠️ Data dummy terdeteksi di sistem. Data ini ditandai dengan [DUMMY] dan harus dihapus sebelum production.'
            : null
    ];
}
?>
