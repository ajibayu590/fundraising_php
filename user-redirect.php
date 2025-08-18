<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is a user role trying to access admin pages
if ($_SESSION['user_role'] === 'user') {
    // Redirect to user dashboard with message
    $_SESSION['redirect_message'] = "Anda tidak memiliki akses ke halaman tersebut. Anda telah dialihkan ke Dashboard Fundraiser Anda.";
    header("Location: user-dashboard.php");
    exit;
}

// If admin/monitor, redirect to main dashboard
header("Location: dashboard.php");
exit;
?>