<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Check for timeout
if (isset($_GET['timeout'])) {
    $error = "Your session has expired. Please login again.";
}

// Check if user just logged out
if (isset($_GET['logged_out'])) {
    $success = "You have been logged out successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $ip = $_SERVER['REMOTE_ADDR'];

        // Check if IP is blacklisted
        if (isIPBlacklisted($ip)) {
            $error = "Access denied. Your IP has been temporarily blocked.";
        } elseif (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } elseif (checkLoginAttempts($username)) {
            $error = "Too many failed login attempts. Please try again in 15 minutes.";
            logFailedLogin($username, $ip);
        } else {
            global $db;
            $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);

            if ($user && $user['account_locked'] == 1) {
                $error = "Your account has been locked. Please contact support.";
            } elseif ($user && password_verify($password, $user['password'])) {
                // Reset failed attempts
                $db->query("UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE id = ?", [$user['id']]);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['last_activity'] = time();

                // Generate new CSRF token
                generateCSRFToken();

                // Log successful login
                $db->query(
                    "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                    [$user['id'], 'login', 'User logged in successfully', $ip]
                );

                // Store session info
                $sessionId = session_id();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $db->query(
                    "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
                    [$user['id'], $sessionId, $ip, $userAgent]
                );

                header("Location: dashboard.php");
                exit();
            } else {
                // Increment failed attempts
                if ($user) {
                    $attempts = $user['failed_login_attempts'] + 1;
                    $db->query("UPDATE users SET failed_login_attempts = ? WHERE id = ?", [$attempts, $user['id']]);

                    // Lock account after 5 failed attempts
                    if ($attempts >= 5) {
                        $db->query("UPDATE users SET account_locked = 1 WHERE id = ?", [$user['id']]);
                        $error = "Account locked due to multiple failed login attempts. Contact support.";
                    } else {
                        $error = "Invalid username or password";
                    }
                } else {
                    $error = "Invalid username or password";
                }

                logFailedLogin($username, $ip);
            }
        }
    }
}

// Generate CSRF token for form
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="login-form">
            <h2>Secure Login</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>

            <div style="margin-top: 15px; text-align: center;">
                <a href="forgot_password.php" style="color: #3498db; font-size: 14px;">Forgot Password?</a>
            </div>

            <div style="margin-top: 10px; text-align: center;">
                <p style="font-size: 14px;">Don't have an account? <a href="register.php" style="color: #3498db;">Register here</a></p>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 4px; font-size: 14px;">
                <strong>Demo Credentials:</strong><br>
                Admin: <code>admin</code> / <code>admin123</code><br>
                Student: <code>john.doe</code> / <code>password123</code>
            </div>
        </div>
    </div>
</body>

</html>