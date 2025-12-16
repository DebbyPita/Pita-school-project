<?php

/**
 * Security Functions
 * Place this file in includes/security.php
 */

/**
 * Generate CSRF Token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate Limiting - Check if user has exceeded login attempts
 */
function checkLoginAttempts($username)
{
    global $db;

    // Check attempts in last 15 minutes
    $attempts = $db->fetchOne(
        "SELECT COUNT(*) as count FROM audit_log 
         WHERE action = 'failed_login' 
         AND details LIKE ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
        ['%' . $username . '%']
    );

    return $attempts['count'] >= 5; // Max 5 attempts
}

/**
 * Log failed login attempt
 */
function logFailedLogin($username, $ip)
{
    global $db;

    $db->query(
        "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [0, 'failed_login', "Failed login attempt for: " . $username, $ip]
    );
}

/**
 * Sanitize input
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate account number format
 */
function validateAccountNumber($accountNumber)
{
    // Must be ACC followed by 4 digits
    return preg_match('/^ACC\d{4}$/', $accountNumber);
}

/**
 * Check if IP is blacklisted
 */
function isIPBlacklisted($ip)
{
    global $db;

    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM ip_blacklist WHERE ip_address = ? AND expiry > NOW()",
        [$ip]
    );

    return $result['count'] > 0;
}

/**
 * Add IP to blacklist
 */
function blacklistIP($ip, $reason, $hours = 24)
{
    global $db;

    $expiry = date('Y-m-d H:i:s', strtotime("+$hours hours"));

    $db->query(
        "INSERT INTO ip_blacklist (ip_address, reason, expiry) VALUES (?, ?, ?)",
        [$ip, $reason, $expiry]
    );
}

/**
 * Check for suspicious activity
 */
function checkSuspiciousActivity($userId)
{
    global $db;

    // Check for multiple large transactions in short time
    $recentTransactions = $db->fetchAll(
        "SELECT * FROM transactions 
         WHERE account_id = (SELECT id FROM accounts WHERE user_id = ?)
         AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
         AND ABS(amount) > 1000",
        [$userId]
    );

    if (count($recentTransactions) >= 5) {
        return true; // Suspicious: 5+ large transactions in 1 hour
    }

    return false;
}

/**
 * Encrypt sensitive data
 */
function encryptData($data, $key = null)
{
    if ($key === null) {
        $key = hash('sha256', 'your-secret-key-here'); // Change this in production
    }

    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt sensitive data
 */
function decryptData($data, $key = null)
{
    if ($key === null) {
        $key = hash('sha256', 'your-secret-key-here'); // Must match encryption key
    }

    list($encrypted, $iv) = explode('::', base64_decode($data), 2);

    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Validate strong password
 */
function isStrongPassword($password)
{
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Generate secure random password
 */
function generateSecurePassword($length = 12)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $password;
}

/**
 * Log security event
 */
function logSecurityEvent($userId, $event, $details)
{
    global $db;

    $db->query(
        "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$userId, 'security_' . $event, $details, $_SERVER['REMOTE_ADDR']]
    );
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout()
{
    $timeout = 1800; // 30 minutes in seconds

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Verify transaction PIN (additional security layer)
 */
function verifyTransactionPIN($userId, $pin)
{
    global $db;

    $user = $db->fetchOne(
        "SELECT transaction_pin FROM users WHERE id = ?",
        [$userId]
    );

    if ($user && isset($user['transaction_pin'])) {
        return password_verify($pin, $user['transaction_pin']);
    }

    return false;
}

/**
 * Two-Factor Authentication - Generate OTP
 */
function generateOTP()
{
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Store OTP in session
 */
function storeOTP($userId, $otp)
{
    $_SESSION['otp'] = [
        'code' => $otp,
        'user_id' => $userId,
        'expiry' => time() + 300 // 5 minutes
    ];
}

/**
 * Verify OTP
 */
function verifyOTP($userId, $code)
{
    if (!isset($_SESSION['otp'])) {
        return false;
    }

    $otp = $_SESSION['otp'];

    if ($otp['user_id'] != $userId || $otp['expiry'] < time()) {
        unset($_SESSION['otp']);
        return false;
    }

    if ($otp['code'] === $code) {
        unset($_SESSION['otp']);
        return true;
    }

    return false;
}
