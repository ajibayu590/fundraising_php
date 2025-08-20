<?php
/**
 * WhatsApp Database Setup Script
 * Automatically creates all required tables for WhatsApp API
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user has admin role
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

$setup_results = [];
$success = true;

try {
    // 1. Create whatsapp_messages table
    $sql = "
    CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NULL,
        `donor_id` INT UNSIGNED NULL,
        `to_number` VARCHAR(20) NOT NULL,
        `message` TEXT NOT NULL,
        `template_id` VARCHAR(100) NULL,
        `variables` JSON NULL,
        `file_url` VARCHAR(500) NULL,
        `success` TINYINT(1) NOT NULL DEFAULT 0,
        `response_data` JSON NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_whatsapp_user` (`user_id`),
        KEY `idx_whatsapp_donor` (`donor_id`),
        KEY `idx_whatsapp_success` (`success`),
        KEY `idx_whatsapp_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    $setup_results[] = [
        'table' => 'whatsapp_messages',
        'status' => 'SUCCESS',
        'message' => 'Table created successfully'
    ];
    
    // 2. Create whatsapp_templates table
    $sql = "
    CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `template_id` VARCHAR(100) NOT NULL UNIQUE,
        `name` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `variables` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_template_id` (`template_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    $setup_results[] = [
        'table' => 'whatsapp_templates',
        'status' => 'SUCCESS',
        'message' => 'Table created successfully'
    ];
    
    // 3. Create api_logs table
    $sql = "
    CREATE TABLE IF NOT EXISTS `api_logs` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `endpoint` VARCHAR(255) NOT NULL,
        `method` VARCHAR(10) NOT NULL,
        `user_id` INT UNSIGNED NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` TEXT NULL,
        `details` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_api_user` (`user_id`),
        KEY `idx_api_endpoint` (`endpoint`),
        KEY `idx_api_method` (`method`),
        KEY `idx_api_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    $setup_results[] = [
        'table' => 'api_logs',
        'status' => 'SUCCESS',
        'message' => 'Table created successfully'
    ];
    
    // 4. Insert default templates
    $templates = [
        [
            'template_id' => 'welcome_donor',
            'name' => 'Welcome Donor',
            'message' => 'Halo {nama_donatur}, terima kasih telah mendukung program fundraising kami. Kami akan menghubungi Anda segera untuk informasi lebih lanjut.',
            'variables' => 'nama_donatur'
        ],
        [
            'template_id' => 'kunjungan_success',
            'name' => 'Kunjungan Berhasil',
            'message' => 'Halo {nama_donatur}, kunjungan fundraising kami telah berhasil. Terima kasih atas donasi sebesar {nominal_donasi} pada {tanggal_kunjungan}. Kami sangat menghargai dukungan Anda.',
            'variables' => 'nama_donatur, nominal_donasi, tanggal_kunjungan'
        ],
        [
            'template_id' => 'kunjungan_followup',
            'name' => 'Kunjungan Follow Up',
            'message' => 'Halo {nama_donatur}, kami akan melakukan follow up kunjungan fundraising pada {tanggal_kunjungan}. Terima kasih atas waktu dan perhatian Anda.',
            'variables' => 'nama_donatur, tanggal_kunjungan'
        ],
        [
            'template_id' => 'reminder_target',
            'name' => 'Reminder Target',
            'message' => 'Halo {nama_fundraiser}, target kunjungan hari ini adalah {target}. Silakan lakukan kunjungan untuk mencapai target yang telah ditentukan.',
            'variables' => 'nama_fundraiser, target'
        ],
        [
            'template_id' => 'donation_thankyou',
            'name' => 'Thank You Donation',
            'message' => 'Terima kasih {nama_donatur} atas donasi sebesar {nominal_donasi}. Donasi Anda akan digunakan untuk membantu program sosial kami. Semoga Allah SWT membalas kebaikan Anda.',
            'variables' => 'nama_donatur, nominal_donasi'
        ],
        [
            'template_id' => 'fundraiser_update',
            'name' => 'Fundraiser Update',
            'message' => 'Halo {nama_donatur}, ini adalah update dari fundraiser {nama_fundraiser}. Status kunjungan: {status_kunjungan}. Terima kasih.',
            'variables' => 'nama_donatur, nama_fundraiser, status_kunjungan'
        ],
        [
            'template_id' => 'location_info',
            'name' => 'Location Information',
            'message' => 'Halo {nama_donatur}, kami akan melakukan kunjungan ke alamat: {alamat_donatur}. Mohon konfirmasi jika ada perubahan alamat. Terima kasih.',
            'variables' => 'nama_donatur, alamat_donatur'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO whatsapp_templates (template_id, name, message, variables) 
        VALUES (?, ?, ?, ?)
    ");
    
    $templates_created = 0;
    foreach ($templates as $template) {
        $stmt->execute([
            $template['template_id'],
            $template['name'],
            $template['message'],
            $template['variables']
        ]);
        if ($stmt->rowCount() > 0) {
            $templates_created++;
        }
    }
    
    $setup_results[] = [
        'table' => 'whatsapp_templates',
        'status' => 'SUCCESS',
        'message' => "$templates_created default templates inserted"
    ];
    
    // 5. Create uploads directories if they don't exist
    $directories = ['uploads', 'uploads/kunjungan', 'uploads/logos', 'logs'];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $setup_results[] = [
            'directory' => $dir,
            'status' => 'SUCCESS',
            'message' => 'Directory created/verified'
        ];
    }
    
} catch (Exception $e) {
    $success = false;
    $setup_results[] = [
        'error' => 'Database Setup Error',
        'status' => 'FAILED',
        'message' => $e->getMessage()
    ];
}

$page_title = 'WhatsApp Database Setup';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/icon-fixes.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-4xl w-full mx-auto p-6">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">WhatsApp Database Setup</h1>
                    <p class="text-gray-600">Setup database tables for WhatsApp API system</p>
                </div>
                
                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <strong>✅ Setup Completed Successfully!</strong> All WhatsApp database tables have been created.
                </div>
                <?php else: ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <strong>❌ Setup Failed!</strong> Some errors occurred during the setup process.
                </div>
                <?php endif; ?>
                
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Setup Results:</h2>
                    
                    <?php foreach ($setup_results as $result): ?>
                    <div class="border rounded-lg p-4 <?php echo $result['status'] === 'SUCCESS' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($result['table'] ?? $result['directory'] ?? $result['error'] ?? 'Unknown'); ?>
                                </h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($result['message']); ?>
                                </p>
                            </div>
                            <div class="flex items-center">
                                <?php if ($result['status'] === 'SUCCESS'): ?>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <?php else: ?>
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-8 flex justify-center space-x-4">
                    <a href="whatsapp-manager.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Go to WhatsApp Manager
                    </a>
                    
                    <a href="whatsapp_settings.php" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Configure Settings
                    </a>
                    
                    <a href="dashboard.php" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
                
                <?php if ($success): ?>
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-medium text-blue-900 mb-2">Next Steps:</h3>
                    <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
                        <li>Go to <strong>WhatsApp Settings</strong> to configure your API credentials</li>
                        <li>Test the connection to ensure everything is working</li>
                        <li>Start using <strong>WhatsApp Manager</strong> to send messages</li>
                        <li>Use the WhatsApp button in <strong>Kunjungan</strong> page for notifications</li>
                    </ol>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>