<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Redirect to appropriate page based on login status
if (isLoggedIn()) {
    // If user is logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
