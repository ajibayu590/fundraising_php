<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];

// Redirect based on role and context
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    
    if ($type === 'fundraiser') {
        // Always redirect to fundraiser management
        header("Location: fundraiser.php");
        exit;
    } elseif ($type === 'admin') {
        // Always redirect to admin user management
        header("Location: admin-users.php");
        exit;
    }
}

// Default behavior based on role
if ($user_role === 'user') {
    // Regular users don't have access to user management
    header("Location: dashboard.php");
    exit;
} else {
    // Admin and Monitor - default to fundraiser management
    // Since that's what they most commonly need
    header("Location: fundraiser.php");
    exit;
}
?>