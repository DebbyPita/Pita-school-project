<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$error = '';
$success = '';
$validToken = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

global $db;

// Check if token is valid
if (!empty($token)) {
    $reset = $db->fetchOne(
        "SELECT pr.*, u.id as user_id, u.username FROM password_resets pr 
         JOIN users u ON pr.user_id = u.id 
         WHERE pr.token = ? AND pr.expiry > NOW() AND pr.used = 0",
        [$token]
    );

    if ($reset) {
        $validToken = true;
    } else {
        $error = "Invalid or expired reset token";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($password)) {
        $error = "Please enter a password";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        try {
            // Start transaction
            $db->getConnection()->begin_transaction();

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Update user password
            $db->query(
                "UPDATE users SET password = ? WHERE id = ?",
                [$hashedPassword, $reset['user_id']]
            );

            // Mark token as used
            $db->query(
                "UPDATE password_resets SET used = 1 WHERE token = ?",
                [$token]
            );

            // Log audit
            $db->query(
                "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                [$reset['user_id'], 'password_reset', 'Password was reset', $_SERVER['REMOTE_ADDR']]
            );

            // Commit transaction
            $db->getConnection()->commit();

            $success = "Password reset successful! You can now login with your new password.";
            $validToken = false;
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $error = "Password reset failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="login-form">
            <h2>Reset Password</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="login.php" class="btn">Go to Login</a>
                </div>
            <?php elseif ($validToken): ?>
                <p style="margin-bottom: 20px; color: #666;">Enter your new password below.</p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="error-message">Invalid or expired reset link</div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="forgot_password.php" class="btn">Request New Reset Link</a>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px; text-align: center;">
                <p><a href="login.php" style="color: #3498db;">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>

</html>