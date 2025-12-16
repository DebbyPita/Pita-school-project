<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } else {
        global $db;

        $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $db->query(
                "INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)",
                [$user['id'], $token, $expiry]
            );

            // In production, send email here
            // For now, we'll just show the reset link
            $resetLink = SITE_URL . "/reset_password.php?token=" . $token;

            $success = "Password reset instructions sent! <br><br><strong>Demo Reset Link:</strong><br><a href='reset_password.php?token=" . $token . "'>Click here to reset password</a><br><br><small>(In production, this would be emailed to you)</small>";

            // Log audit
            $db->query(
                "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                [$user['id'], 'password_reset_request', 'Password reset requested', $_SERVER['REMOTE_ADDR']]
            );
        } else {
            // Don't reveal if email exists for security
            $success = "If that email is registered, you will receive password reset instructions.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="login-form">
            <h2>Forgot Password</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php else: ?>
                <p style="margin-bottom: 20px; color: #666;">Enter your email address and we'll send you instructions to reset your password.</p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Send Reset Instructions</button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 20px; text-align: center;">
                <p><a href="login.php" style="color: #3498db;">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>

</html>