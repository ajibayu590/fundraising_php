<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
}

// Database connection
require_once dirname(__DIR__) . '/config.php';

// Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'insert_dummy_data':
            insertDummyData($pdo);
            break;
        case 'delete_dummy_data':
            deleteDummyData($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function insertDummyData($pdo) {
    $pdo->beginTransaction();
    
    try {
        // Check if dummy data already exists
        $existingUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@dummy.com'")->fetchColumn();
        if ($existingUsers > 0) {
            throw new Exception('Data dummy sudah ada di database. Hapus data dummy terlebih dahulu.');
        }

        // Insert dummy users (fundraisers) with clear dummy indicators
                $dummyUsers = [
            ['name' => '[DUMMY] Ahmad Rahman', 'email' => 'ahmad@dummy.com', 'hp' => '081234567890', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user', 'target' => 10],
            ['name' => '[DUMMY] Siti Nurhaliza', 'email' => 'siti@dummy.com', 'hp' => '081234567891', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user', 'target' => 8],
            ['name' => '[DUMMY] Budi Santoso', 'email' => 'budi@dummy.com', 'hp' => '081234567892', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user', 'target' => 12],
            ['name' => '[DUMMY] Dewi Sartika', 'email' => 'dewi@dummy.com', 'hp' => '081234567893', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user', 'target' => 9],
            ['name' => '[DUMMY] Rudi Hermawan', 'email' => 'rudi@dummy.com', 'hp' => '081234567894', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user', 'target' => 11]
        ];

        $userStmt = $pdo->prepare("INSERT INTO users (name, email, hp, password, role, target) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($dummyUsers as $user) {
            $userStmt->execute([$user['name'], $user['email'], $user['hp'], $user['password'], $user['role'], $user['target']]);
                }

        // Insert dummy donatur with clear dummy indicators
                $dummyDonatur = [
            ['nama' => '[DUMMY] PT Maju Bersama', 'hp' => '081234567895', 'email' => 'info@dummy-perusahaan.com', 'alamat' => 'Jl. Sudirman No. 123, Jakarta', 'kategori' => 'perusahaan'],
            ['nama' => '[DUMMY] Yayasan Peduli Anak', 'hp' => '081234567896', 'email' => 'contact@dummy-organisasi.org', 'alamat' => 'Jl. Thamrin No. 45, Jakarta', 'kategori' => 'organisasi'],
            ['nama' => '[DUMMY] Bapak Suharto', 'hp' => '081234567897', 'email' => 'suharto@dummy-individu.com', 'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta', 'kategori' => 'individu'],
            ['nama' => '[DUMMY] Ibu Siti Aminah', 'hp' => '081234567898', 'email' => 'siti.aminah@dummy-individu.com', 'alamat' => 'Jl. Rasuna Said No. 89, Jakarta', 'kategori' => 'individu'],
            ['nama' => '[DUMMY] CV Sukses Mandiri', 'hp' => '081234567899', 'email' => 'info@dummy-cv.co.id', 'alamat' => 'Jl. Kuningan No. 12, Jakarta', 'kategori' => 'perusahaan']
        ];

        $donaturStmt = $pdo->prepare("INSERT INTO donatur (nama, hp, email, alamat, kategori) VALUES (?, ?, ?, ?, ?)");
                foreach ($dummyDonatur as $donatur) {
            $donaturStmt->execute([$donatur['nama'], $donatur['hp'], $donatur['email'], $donatur['alamat'], $donatur['kategori']]);
        }

        // Get user IDs for kunjungan
        $userIds = $pdo->query("SELECT id FROM users WHERE email LIKE '%@dummy.com'")->fetchAll(PDO::FETCH_COLUMN);
        $donaturIds = $pdo->query("SELECT id FROM donatur WHERE email LIKE '%@dummy%'")->fetchAll(PDO::FETCH_COLUMN);

        // Insert dummy kunjungan with today's date to ensure visibility
        $statuses = ['berhasil', 'tidak-berhasil', 'follow-up'];
        $alamatList = [
            'Jl. Sudirman No. 123, Jakarta Pusat',
            'Jl. Thamrin No. 45, Jakarta Pusat',
            'Jl. Gatot Subroto No. 67, Jakarta Selatan',
            'Jl. Rasuna Said No. 89, Jakarta Selatan',
            'Jl. Kuningan No. 12, Jakarta Selatan'
        ];

        $kunjunganStmt = $pdo->prepare("INSERT INTO kunjungan (fundraiser_id, donatur_id, alamat, status, nominal, catatan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Create kunjungan for today and past few days
        for ($i = 0; $i < 25; $i++) {
            $userId = $userIds[array_rand($userIds)];
            $donaturId = $donaturIds[array_rand($donaturIds)];
            $status = $statuses[array_rand($statuses)];
            $alamat = $alamatList[array_rand($alamatList)];
            $nominal = ($status === 'berhasil') ? rand(50000, 500000) * 1000 : 0;
            $catatan = '[DUMMY DATA] Kunjungan dummy untuk testing sistem - Data ini akan dihapus saat production';
            
            // Create dates: 10 for today, 10 for yesterday, 5 for day before yesterday
            if ($i < 10) {
                $date = date('Y-m-d H:i:s'); // Today
            } elseif ($i < 20) {
                $date = date('Y-m-d H:i:s', strtotime('-1 day')); // Yesterday
            } else {
                $date = date('Y-m-d H:i:s', strtotime('-2 days')); // Day before yesterday
            }
            
            $kunjunganStmt->execute([$userId, $donaturId, $alamat, $status, $nominal, $catatan, $date]);
        }

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Data dummy berhasil dimasukkan! Data ini ditandai dengan [DUMMY] dan akan terlihat di dashboard hari ini.',
            'details' => [
                'users_added' => count($dummyUsers),
                'donatur_added' => count($dummyDonatur),
                'kunjungan_added' => 25,
                'note' => 'Data dummy ditandai dengan [DUMMY] di nama dan catatan'
            ]
        ]);
        
            } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Gagal memasukkan data dummy: ' . $e->getMessage());
    }
}

function deleteDummyData($pdo) {
    $pdo->beginTransaction();
    
    try {
        // Count data before deletion for reporting
        $kunjunganCount = $pdo->query("SELECT COUNT(*) FROM kunjungan WHERE catatan LIKE '%DUMMY%'")->fetchColumn();
        $donaturCount = $pdo->query("SELECT COUNT(*) FROM donatur WHERE email LIKE '%@dummy%'")->fetchColumn();
        $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@dummy.com'")->fetchColumn();
        
        // Delete kunjungan data
        $pdo->exec("DELETE FROM kunjungan WHERE catatan LIKE '%DUMMY%'");
        
        // Delete donatur data
        $pdo->exec("DELETE FROM donatur WHERE email LIKE '%@dummy%'");
        
        // Delete user data (keep admin)
        $pdo->exec("DELETE FROM users WHERE email LIKE '%@dummy.com'");
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Data dummy berhasil dihapus dari database!',
            'details' => [
                'kunjungan_deleted' => $kunjunganCount,
                'donatur_deleted' => $donaturCount,
                'users_deleted' => $userCount
            ]
        ]);
        
            } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Gagal menghapus data dummy: ' . $e->getMessage());
    }
}
?>
