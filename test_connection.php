<?php

/**
 * Database Connection Test Script
 * Place this file in the root directory and access via browser
 * http://localhost/pita-school/test_connection.php
 * DELETE THIS FILE after testing!
 */

echo "<h1>School Banking System - Connection Test</h1>";
echo "<hr>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion();
echo (version_compare(phpversion(), '7.0.0', '>='))
    ? " ✅ OK"
    : " ❌ FAILED (Need PHP 7.0+)";
echo "<br><br>";

// Test 2: Required Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$extensions = ['mysqli', 'session'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✅ Loaded" : "❌ Not Loaded") . "<br>";
}
echo "<br>";

// Test 3: Database Connection
echo "<h2>3. Database Connection</h2>";
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'school_banking';

try {
    $conn = new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "MySQL Connection: ✅ Success<br>";

    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result->num_rows > 0) {
        echo "Database '$dbname': ✅ Exists<br>";

        // Select database and check tables
        $conn->select_db($dbname);

        $tables = ['users', 'accounts', 'transactions', 'audit_log'];
        echo "<br><h3>Database Tables:</h3>";
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "$table: " . ($result->num_rows > 0 ? "✅ Exists" : "❌ Missing") . "<br>";
        }

        // Check user count
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<br>Total Users: " . $row['count'] . "<br>";
    } else {
        echo "Database '$dbname': ❌ Not Found<br>";
        echo "<p style='color: red;'>Please run the database setup script!</p>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<p style='color: red;'>Make sure MySQL is running in XAMPP!</p>";
}
echo "<br>";

// Test 4: File Structure
echo "<h2>4. File Structure</h2>";
$files = [
    'includes/config.php',
    'includes/auth.php',
    'includes/database.php',
    'includes/functions.php',
    'login.php',
    'dashboard.php',
    'logout.php',
    'assets/css/style.css'
];

foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "✅ Exists" : "❌ Missing") . "<br>";
}
echo "<br>";

// Test 5: Session
echo "<h2>5. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Not Active") . "<br>";
echo "<br>";

// Final message
echo "<hr>";
echo "<h2>Setup Status</h2>";
echo "<p><strong>If all tests show ✅, your system is ready!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Delete this test_connection.php file</li>";
echo "<li>Go to <a href='login.php'>login.php</a></li>";
echo "<li>Login with: <strong>admin</strong> / <strong>admin123</strong></li>";
echo "</ol>";
echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ DELETE THIS FILE AFTER TESTING!</strong></p>";
