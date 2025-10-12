<?php
require_once 'db.php';
require_once 'functions.php';

function login($username, $password) {
    global $db;
    
    $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        // Log audit
        $ip = $_SERVER['REMOTE_ADDR'];
        $db->query(
            "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$user['id'], 'login', 'User logged in', $ip]
        );
        
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

function logout() {
    global $db;
    
    if (isLoggedIn()) {
        // Log audit
        $ip = $_SERVER['REMOTE_ADDR'];
        $db->query(
            "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$_SESSION['user_id'], 'logout', 'User logged out', $ip]
        );
    }
    
    session_unset();
    session_destroy();
}