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

// Determine sidebar
$sidebarFile = ($user['role'] === 'admin' || $user['role'] === 'monitor') ? 'sidebar-admin.php' : 'sidebar-user.php';

// Get donors for dropdown
$stmt = $pdo->prepare("SELECT id, nama, hp FROM donatur ORDER BY nama");
$stmt->execute();
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get message history
$where_conditions = [];
$params = [];

if ($user['role'] === 'user') {
    $where_conditions[] = "wm.user_id = ?";
    $params[] = $user['id'];
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("
    SELECT wm.*, u.name as user_name, d.nama as donor_name
    FROM whatsapp_messages wm
    LEFT JOIN users u ON wm.user_id = u.id
    LEFT JOIN donatur d ON wm.donor_id = d.id
    $where_clause
    ORDER BY wm.created_at DESC
    LIMIT 50
");
$stmt->execute($params);
$message_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'WhatsApp Manager';
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
                    WhatsApp Manager
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
                <button onclick="openSendMessageModal()" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Send Message
                </button>
                
                <button onclick="openBulkMessageModal()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                    </svg>
                    Bulk Message
                </button>
                
                <button onclick="loadTemplates()" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Templates
                </button>
            </div>
        </div>

        <div class="mb-6 md:mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">WhatsApp Message Manager</h2>
            <p class="text-gray-600 mt-2">Kelola dan kirim pesan WhatsApp ke donatur</p>
        </div>

        <!-- Message History -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">📱 Message History</h3>
                <p class="text-sm text-gray-600">Riwayat pesan WhatsApp yang telah dikirim</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($message_history)): ?>
                            <?php foreach ($message_history as $message): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($message['to_number']); ?>
                                    <?php if ($message['donor_name']): ?>
                                        <br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($message['donor_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">
                                        <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                        <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $message['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $message['success'] ? 'Success' : 'Failed'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($message['user_name'] ?? 'Unknown'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No message history found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Send Message Modal -->
    <div id="sendMessageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send WhatsApp Message</h3>
                <form id="sendMessageForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To (Phone Number)</label>
                        <input type="text" id="messageTo" name="to" placeholder="6281234567890" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="messageText" name="message" rows="4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter your message here..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeSendMessageModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Message Modal -->
    <div id="bulkMessageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send Bulk WhatsApp Messages</h3>
                <form id="bulkMessageForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Donors</label>
                        <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-2">
                            <?php foreach ($donors as $donor): ?>
                            <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                                <input type="checkbox" name="donor_ids[]" value="<?php echo $donor['id']; ?>" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm">
                                    <?php echo htmlspecialchars($donor['nama']); ?> 
                                    (<?php echo htmlspecialchars($donor['hp']); ?>)
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="bulkMessageText" name="message" rows="4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter your message here..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeBulkMessageModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Send Bulk Messages
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

        // Modal functions
        function openSendMessageModal() {
            document.getElementById('sendMessageModal').classList.remove('hidden');
        }

        function closeSendMessageModal() {
            document.getElementById('sendMessageModal').classList.add('hidden');
        }

        function openBulkMessageModal() {
            document.getElementById('bulkMessageModal').classList.remove('hidden');
        }

        function closeBulkMessageModal() {
            document.getElementById('bulkMessageModal').classList.add('hidden');
        }

        // Send message form
        document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                to: formData.get('to'),
                message: formData.get('message')
            };
            
            try {
                const response = await fetch('whatsapp_api.php?action=send_message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Invalid JSON response:', responseText);
                    alert('Error: Invalid response from server. Please check console for details.');
                    return;
                }
                
                if (result.success) {
                    alert('Message sent successfully!');
                    closeSendMessageModal();
                    location.reload();
                } else {
                    alert('Failed to send message: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Error sending message: ' + error.message);
            }
        });

        // Bulk message form
        document.getElementById('bulkMessageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const donorIds = formData.getAll('donor_ids[]');
            
            if (donorIds.length === 0) {
                alert('Please select at least one donor');
                return;
            }
            
            const data = {
                donor_ids: donorIds,
                message: formData.get('message')
            };
            
            try {
                const response = await fetch('whatsapp_api.php?action=send_bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Invalid JSON response:', responseText);
                    alert('Error: Invalid response from server. Please check console for details.');
                    return;
                }
                
                if (result.success) {
                    alert(`Bulk messages sent: ${result.data.success} successful, ${result.data.errors} failed`);
                    closeBulkMessageModal();
                    location.reload();
                } else {
                    alert('Failed to send bulk messages: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Error sending bulk messages: ' + error.message);
            }
        });

        // Load templates
        async function loadTemplates() {
            try {
                const response = await fetch('whatsapp_api.php?action=templates');
                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Invalid JSON response:', responseText);
                    alert('Error: Invalid response from server. Please check console for details.');
                    return;
                }
                
                if (result.success) {
                    let templateList = 'Available Templates:\n\n';
                    result.data.forEach(template => {
                        templateList += `${template.name}:\n${template.message}\n\n`;
                    });
                    alert(templateList);
                } else {
                    alert('Failed to load templates: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Error loading templates: ' + error.message);
            }
        }
    </script>
</body>
</html>