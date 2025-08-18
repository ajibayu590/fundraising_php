<?php
// Test Hybrid Approach for Users

echo '<h2>🧪 TEST HYBRID APPROACH - USERS</h2>';

try {
	require_once 'config.php';
	echo '<p>✅ Database connected</p>';

	$searchQuery = '';
	$roleFilter = '';
	$statusFilter = '';

	$where = ["1=1"];
	$params = [];
	if ($searchQuery !== '') {
		$where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.hp LIKE ?)";
		$like = "%$searchQuery%";
		$params = array_merge($params, [$like, $like, $like]);
	}
	if ($roleFilter !== '') { $where[] = 'u.role = ?'; $params[] = $roleFilter; }
	if ($statusFilter !== '') { $where[] = 'u.status = ?'; $params[] = $statusFilter; }
	$whereClause = implode(' AND ', $where);

	$stmt = $pdo->prepare("SELECT u.id,u.name,u.email,u.hp,u.target,u.role,u.status,u.created_at,
		COALESCE(COUNT(k.id),0) total_kunjungan,
		COALESCE(SUM(CASE WHEN k.status='berhasil' THEN k.nominal ELSE 0 END),0) total_donasi,
		COALESCE(SUM(CASE WHEN DATE(k.created_at)=CURDATE() THEN 1 ELSE 0 END),0) kunjungan_hari_ini,
		COALESCE(SUM(CASE WHEN MONTH(k.created_at)=MONTH(CURRENT_DATE()) AND YEAR(k.created_at)=YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END),0) kunjungan_bulan_ini,
		COALESCE(SUM(CASE WHEN MONTH(k.created_at)=MONTH(CURRENT_DATE()) AND YEAR(k.created_at)=YEAR(CURRENT_DATE()) AND k.status='berhasil' THEN k.nominal ELSE 0 END), 0) donasi_bulan_ini
		FROM users u LEFT JOIN kunjungan k ON u.id=k.fundraiser_id WHERE $whereClause
		GROUP BY u.id,u.name,u.email,u.hp,u.target,u.role,u.status,u.created_at ORDER BY u.name");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo '<p>✅ Users Loaded: ' . count($rows) . ' records</p>';

	$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
	$aktifUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='aktif'")->fetchColumn();
	$fundraiserUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
	$avgTarget = (int)$pdo->query("SELECT COALESCE(AVG(target),0) FROM users")->fetchColumn();
	echo '<p>✅ Total: ' . $totalUsers . ' | Aktif: ' . $aktifUsers . ' | Fundraiser: ' . $fundraiserUsers . ' | Avg Target: ' . $avgTarget . '</p>';

	// API presence
	if (file_exists('api/users_crud.php')) echo '<p>✅ API exists: api/users_crud.php</p>';
	else echo '<p>❌ API missing</p>';

	// JS presence
	if (file_exists('js/users_api.js')) echo '<p>✅ JS exists: js/users_api.js</p>';
	else echo '<p>❌ JS missing</p>';

	// Sample
	if (!empty($rows)) {
		echo '<h3>📋 Sample 3 Users</h3>';
		echo "<ul>";
		foreach (array_slice($rows, 0, 3) as $u) {
			echo '<li>' . htmlspecialchars($u['name']) . ' - ' . htmlspecialchars($u['email']) . ' (' . htmlspecialchars($u['role']) . ')</li>';
		}
		echo "</ul>";
	}

} catch (Exception $e) {
	echo '<p style="color:red">❌ Error: ' . $e->getMessage() . '</p>';
}
