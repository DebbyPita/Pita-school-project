<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Call the logout function to clear session and log the action
logout();

// Redirect to login page with a success message
header("Location: login.php?logged_out=1");
exit();
