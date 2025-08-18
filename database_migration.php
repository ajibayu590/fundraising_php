<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Fundraising System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Database Migration - Fix Donatur Schema</h1>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
                require_once 'config.php';
                
                try {
                    echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                    echo '<strong>🔧 Running Migration...</strong><br>';
                    
                    // Check if catatan column exists
                    $stmt = $pdo->prepare("SHOW COLUMNS FROM donatur LIKE 'catatan'");
                    $stmt->execute();
                    $columnExists = $stmt->fetch();
                    
                    if (!$columnExists) {
                        echo '📝 Adding "catatan" column to donatur table...<br>';
                        
                        // Add catatan column
                        $pdo->exec("ALTER TABLE donatur ADD COLUMN catatan TEXT NULL AFTER kategori");
                        
                        // Update existing records
                        $pdo->exec("UPDATE donatur SET catatan = '' WHERE catatan IS NULL");
                        
                        echo '✅ Column "catatan" successfully added!<br>';
                        echo '</div>';
                        
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                        echo '<strong>🎉 Migration Completed Successfully!</strong><br>';
                        echo 'The donatur page should now work properly for admin users.';
                        echo '</div>';
                    } else {
                        echo 'ℹ️ Column "catatan" already exists in donatur table.<br>';
                        echo '</div>';
                        
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                        echo '<strong>⚠️ No Migration Needed</strong><br>';
                        echo 'The database schema is already correct.';
                        echo '</div>';
                    }
                    
                    // Verify the fix
                    $stmt = $pdo->prepare("DESCRIBE donatur");
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<div class="bg-gray-100 p-4 rounded mb-4">';
                    echo '<h3 class="font-semibold mb-2">📋 Current donatur table structure:</h3>';
                    echo '<ul class="list-disc list-inside space-y-1">';
                    foreach ($columns as $column) {
                        $highlight = $column['Field'] === 'catatan' ? 'text-green-600 font-semibold' : 'text-gray-700';
                        echo "<li class='$highlight'>{$column['Field']} ({$column['Type']})</li>";
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                    echo '<strong>❌ Migration Failed:</strong><br>';
                    echo htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
            ?>
            
            <div class="space-y-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Problem Detected</h3>
                    <p class="text-yellow-700">
                        The donatur table is missing the "catatan" column, which is causing SQL errors when admin tries to access the donatur page.
                    </p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">🔧 Solution</h3>
                    <p class="text-blue-700 mb-3">
                        This migration will add the missing "catatan" column to the donatur table to fix the error.
                    </p>
                    <p class="text-sm text-blue-600">
                        <strong>SQL Command:</strong> <code>ALTER TABLE donatur ADD COLUMN catatan TEXT NULL AFTER kategori;</code>
                    </p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <button type="submit" name="run_migration" value="1" 
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                            onclick="return confirm('Are you sure you want to run this database migration?')">
                        🚀 Run Migration - Add Catatan Column
                    </button>
                </form>
                
                <div class="text-center">
                    <a href="donatur.php" class="text-blue-600 hover:text-blue-800">← Back to Donatur Page</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>