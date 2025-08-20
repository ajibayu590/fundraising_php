<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'config.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Check if user has admin role
if ($user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Include app settings
require_once 'app_settings.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        
        if (isset($_POST['update_whatsapp_settings'])) {
            $base_url = trim($_POST['base_url']);
            $app_key = trim($_POST['app_key']);
            $auth_key = trim($_POST['auth_key']);
            $sandbox_mode = isset($_POST['sandbox_mode']) ? 1 : 0;
            
            // Update WhatsApp settings
            update_app_setting('whatsapp_base_url', $base_url);
            update_app_setting('whatsapp_app_key', $app_key);
            update_app_setting('whatsapp_auth_key', $auth_key);
            update_app_setting('whatsapp_sandbox', $sandbox_mode);
            
            $success_message = "WhatsApp settings updated successfully";
            header("Location: whatsapp_settings.php?success=" . urlencode($success_message));
            exit;
        }
        
        if (isset($_POST['update_template'])) {
            $template_id = trim($_POST['template_id']);
            $template_name = trim($_POST['template_name']);
            $template_message = trim($_POST['template_message']);
            $template_variables = trim($_POST['template_variables']);
            
            // Update or insert template
            $stmt = $pdo->prepare("
                INSERT INTO whatsapp_templates (template_id, name, message, variables, updated_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                message = VALUES(message), 
                variables = VALUES(variables), 
                updated_at = NOW()
            ");
            $stmt->execute([$template_id, $template_name, $template_message, $template_variables]);
            
            $success_message = "Template updated successfully";
            header("Location: whatsapp_settings.php?success=" . urlencode($success_message));
            exit;
        }
        
        if (isset($_POST['delete_template'])) {
            $template_id = trim($_POST['template_id']);
            
            $stmt = $pdo->prepare("DELETE FROM whatsapp_templates WHERE template_id = ?");
            $stmt->execute([$template_id]);
            
            $success_message = "Template deleted successfully";
            header("Location: whatsapp_settings.php?success=" . urlencode($success_message));
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current WhatsApp settings
$whatsapp_base_url = get_app_setting('whatsapp_base_url') ?: 'https://app.saungwa.com/api';
$whatsapp_app_key = get_app_setting('whatsapp_app_key') ?: 'e98095ab-363d-47a4-b3b6-af99d68ef2b8';
$whatsapp_auth_key = get_app_setting('whatsapp_auth_key') ?: 'jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT';
$whatsapp_sandbox = get_app_setting('whatsapp_sandbox') ?: 0;

// Get existing templates
$stmt = $pdo->prepare("SELECT * FROM whatsapp_templates ORDER BY name");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine sidebar
$sidebarFile = 'sidebar-admin.php';

$page_title = 'WhatsApp Settings';
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
    <?php echo get_csrf_token_meta(); ?>
    
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 50 !important;
            background: white !important;
        }
        
        .sidebar {
            position: fixed !important;
            top: 64px !important;
            left: 0 !important;
            height: calc(100vh - 64px) !important;
            width: 16rem !important;
            z-index: 40 !important;
            background: white !important;
            transform: translateX(0) !important;
        }
        
        .main-content {
            margin-left: 16rem !important;
            margin-top: 64px !important;
            padding: 2rem !important;
            min-height: calc(100vh - 64px) !important;
            width: calc(100% - 16rem) !important;
            background-color: #f9fafb !important;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%) !important;
            }
            
            .sidebar.show {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="mobile-menu-btn fixed top-4 left-4 z-50 md:hidden bg-white p-2 rounded-lg shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Fixed Header -->
    <header class="fixed-header bg-white">
        <div class="header-content">
            <!-- Logo and Title Section -->
            <div class="flex items-center">
                <?php 
                require_once 'logo_manager.php';
                echo get_logo_html('w-10 h-10', 'mr-3'); 
                ?>
                <div class="header-title">
                    WhatsApp Settings
                </div>
            </div>
            <div class="header-user-info">
                <span class="welcome-text text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <span class="user-role-badge">
                    <?php echo ucfirst($user['role']); ?>
                </span>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    </header>

    <!-- Include appropriate sidebar -->
    <?php include $sidebarFile; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="whatsapp-manager.php" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    WhatsApp Manager
                </a>
                
                <button onclick="testConnection()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Test Connection
                </button>
                
                <button onclick="openTemplateModal()" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Template
                </button>
            </div>
        </div>

        <div class="mb-6 md:mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">WhatsApp API Settings</h2>
            <p class="text-gray-600 mt-2">Konfigurasi API WhatsApp dan template pesan</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <strong>Success:</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- WhatsApp API Configuration -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🔧 WhatsApp API Configuration</h3>
                
                <form method="POST" class="space-y-4">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" name="update_whatsapp_settings" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Base URL</label>
                        <input type="url" name="base_url" value="<?php echo htmlspecialchars($whatsapp_base_url); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Contoh: https://app.saungwa.com/api atau https://your-domain.com/api</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">App Key</label>
                        <input type="text" name="app_key" value="<?php echo htmlspecialchars($whatsapp_app_key); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Auth Key</label>
                        <input type="text" name="auth_key" value="<?php echo htmlspecialchars($whatsapp_auth_key); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="sandbox_mode" id="sandbox_mode" <?php echo $whatsapp_sandbox ? 'checked' : ''; ?> 
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="sandbox_mode" class="ml-2 text-sm text-gray-700">Enable Sandbox Mode (for testing)</label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Settings Preview -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">👁️ Current Settings</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Base URL</p>
                            <p class="font-medium"><?php echo htmlspecialchars($whatsapp_base_url); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">App Key</p>
                            <p class="font-medium"><?php echo htmlspecialchars(substr($whatsapp_app_key, 0, 20)) . '...'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Auth Key</p>
                            <p class="font-medium"><?php echo htmlspecialchars(substr($whatsapp_auth_key, 0, 20)) . '...'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Sandbox Mode</p>
                            <p class="font-medium"><?php echo $whatsapp_sandbox ? 'Enabled' : 'Disabled'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Available Variables</h3>
                    <div class="space-y-2">
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{nama_donatur}</span> - Nama donatur
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{nominal_donasi}</span> - Jumlah donasi (format: Rp 1,000,000)
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{tanggal_kunjungan}</span> - Tanggal kunjungan
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{nama_fundraiser}</span> - Nama fundraiser
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{status_kunjungan}</span> - Status kunjungan
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{alamat_donatur}</span> - Alamat donatur
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-blue-600">{hp_donatur}</span> - Nomor HP donatur
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Templates -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📝 Message Templates</h3>
            
            <?php if (!empty($templates)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variables</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($template['template_id']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate">
                                    <?php echo htmlspecialchars(substr($template['message'], 0, 100)); ?>
                                    <?php if (strlen($template['message']) > 100): ?>...<?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($template['variables']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editTemplate('<?php echo htmlspecialchars($template['template_id']); ?>')" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                    <?php echo get_csrf_token_field(); ?>
                                    <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($template['template_id']); ?>">
                                    <button type="submit" name="delete_template" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-4">No templates found. Create your first template above.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Template Modal -->
    <div id="templateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add Message Template</h3>
                <form id="templateForm" method="POST" class="space-y-4">
                    <?php echo get_csrf_token_field(); ?>
                    <input type="hidden" name="update_template" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template ID</label>
                        <input type="text" id="templateId" name="template_id" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="welcome_donor">
                        <p class="text-xs text-gray-500 mt-1">Unique identifier for the template (e.g., welcome_donor, success_notification)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                        <input type="text" id="templateName" name="template_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Welcome Donor Message">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message Template</label>
                        <textarea id="templateMessage" name="template_message" rows="6" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Halo {nama_donatur}, terima kasih atas donasi sebesar {nominal_donasi} pada {tanggal_kunjungan}."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Use variables in curly braces: {nama_donatur}, {nominal_donasi}, {tanggal_kunjungan}, etc.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Variables (comma separated)</label>
                        <input type="text" id="templateVariables" name="template_variables" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="nama_donatur, nominal_donasi, tanggal_kunjungan">
                        <p class="text-xs text-gray-500 mt-1">List of variables used in the template (optional, for documentation)</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeTemplateModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Save Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('show');
                    }
                });
            }
        });

        // Template modal functions
        function openTemplateModal() {
            document.getElementById('modalTitle').textContent = 'Add Message Template';
            document.getElementById('templateForm').reset();
            document.getElementById('templateModal').classList.remove('hidden');
        }

        function closeTemplateModal() {
            document.getElementById('templateModal').classList.add('hidden');
        }

        function editTemplate(templateId) {
            // This would need to be implemented to fetch template data
            // For now, just open the modal
            document.getElementById('modalTitle').textContent = 'Edit Message Template';
            document.getElementById('templateId').value = templateId;
            document.getElementById('templateModal').classList.remove('hidden');
        }

        // Test connection function
        async function testConnection() {
            try {
                const response = await fetch('whatsapp_test_connection.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Connection successful! WhatsApp API is working.');
                } else {
                    alert('❌ Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('❌ Error testing connection: ' + error.message);
            }
        }
    </script>
</body>
</html>