<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];

// Smart redirect based on role and context
if ($user_role === 'user') {
    // Regular users don't have access to user management
    header("Location: dashboard.php");
    exit;
} else {
    // Admin and Monitor - redirect to fundraiser management by default
    // This is what they most commonly need to access
    header("Location: fundraiser.php");
    exit;
}
?>