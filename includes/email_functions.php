<?php

/**
 * Email Notification Functions
 * Place this file in includes/email_functions.php
 */

/**
 * Send email notification
 * In production, configure proper SMTP settings
 */
function sendEmail($to, $subject, $message, $userId = null)
{
    global $db;

    // For development, we'll just log the email
    // In production, use PHPMailer or similar

    $headers = "From: " . SITE_NAME . " <noreply@school-banking.local>\r\n";
    $headers .= "Reply-To: support@school-banking.local\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Wrap message in HTML template
    $htmlMessage = getEmailTemplate($subject, $message);

    // Store in database for tracking
    if ($userId) {
        $db->query(
            "INSERT INTO email_notifications (user_id, type, subject, message, status) VALUES (?, ?, ?, ?, ?)",
            [$userId, 'general', $subject, $message, 'pending']
        );
        $emailId = $db->getConnection()->insert_id;
    }

    // In development, just return true (email would be sent in production)
    // Uncomment the following line to actually send emails:
    // $result = mail($to, $subject, $htmlMessage, $headers);

    $result = true; // Simulate success

    // Update status
    if ($userId && isset($emailId)) {
        $status = $result ? 'sent' : 'failed';
        $db->query(
            "UPDATE email_notifications SET status = ?, sent_at = NOW() WHERE id = ?",
            [$status, $emailId]
        );
    }

    return $result;
}

/**
 * Email template wrapper
 */
function getEmailTemplate($subject, $content)
{
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . SITE_NAME . '</h1>
            </div>
            <div class="content">
                <h2>' . $subject . '</h2>
                ' . $content . '
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                <p>This is an automated message. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Send transaction notification
 */
function sendTransactionNotification($userId, $type, $amount, $balance, $reference)
{
    global $db;

    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user) return false;

    $typeText = ucwords(str_replace('_', ' ', $type));
    $subject = "Transaction Notification - " . $typeText;

    $message = "
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>A transaction has been processed on your account:</p>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Transaction Type:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . $typeText . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Amount:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>$" . number_format(abs($amount), 2) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>New Balance:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>$" . number_format($balance, 2) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Reference:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($reference) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Date:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . date('F d, Y H:i:s') . "</td>
            </tr>
        </table>
        <p>If you did not authorize this transaction, please contact us immediately.</p>
        <p><a href='" . SITE_URL . "/dashboard.php' class='btn'>View Dashboard</a></p>
    ";

    return sendEmail($user['email'], $subject, $message, $userId);
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($userId)
{
    global $db;

    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    $account = $db->fetchOne("SELECT * FROM accounts WHERE user_id = ?", [$userId]);

    if (!$user || !$account) return false;

    $subject = "Welcome to " . SITE_NAME;

    $message = "
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>Welcome to " . SITE_NAME . "! Your account has been successfully created.</p>
        <p><strong>Account Details:</strong></p>
        <ul>
            <li><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</li>
            <li><strong>Account Number:</strong> " . htmlspecialchars($account['account_number']) . "</li>
            <li><strong>Initial Balance:</strong> $" . number_format($account['balance'], 2) . "</li>
        </ul>
        <p>You can now login and start using our banking services.</p>
        <p><a href='" . SITE_URL . "/login.php' class='btn'>Login Now</a></p>
        <p>If you have any questions, please don't hesitate to contact our support team.</p>
    ";

    return sendEmail($user['email'], $subject, $message, $userId);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($userId, $token)
{
    global $db;

    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user) return false;

    $subject = "Password Reset Request";
    $resetLink = SITE_URL . "/reset_password.php?token=" . $token;

    $message = "
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>We received a request to reset your password. Click the button below to create a new password:</p>
        <p style='text-align: center; margin: 30px 0;'>
            <a href='" . $resetLink . "' class='btn'>Reset Password</a>
        </p>
        <p>Or copy and paste this link into your browser:</p>
        <p style='word-break: break-all; color: #3498db;'>" . $resetLink . "</p>
        <p><strong>This link will expire in 1 hour.</strong></p>
        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
    ";

    return sendEmail($user['email'], $subject, $message, $userId);
}

/**
 * Send low balance alert
 */
function sendLowBalanceAlert($userId, $balance)
{
    global $db;

    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user) return false;

    $subject = "Low Balance Alert";

    $message = "
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p style='color: #e74c3c; font-weight: bold;'>Your account balance is running low!</p>
        <p>Current Balance: <strong>$" . number_format($balance, 2) . "</strong></p>
        <p>We recommend making a deposit to ensure you have sufficient funds for your transactions.</p>
        <p><a href='" . SITE_URL . "/transactions/deposit.php' class='btn'>Make a Deposit</a></p>
    ";

    return sendEmail($user['email'], $subject, $message, $userId);
}
