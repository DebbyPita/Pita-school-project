<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    global $db;

    // Validation
    if (empty($username) || empty($password) || empty($firstName) || empty($lastName) || empty($email)) {
        $error = "All fields are required";
    } elseif (strlen($username) < 4) {
        $error = "Username must be at least 4 characters";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } else {
        // Check if username exists
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existingUser) {
            $error = "Username already taken";
        } else {
            // Check if email exists
            $existingEmail = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existingEmail) {
                $error = "Email already registered";
            } else {
                try {
                    // Start transaction
                    $db->getConnection()->begin_transaction();

                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Insert user
                    $db->query(
                        "INSERT INTO users (username, password, first_name, last_name, email, role) VALUES (?, ?, ?, ?, ?, ?)",
                        [$username, $hashedPassword, $firstName, $lastName, $email, 'student']
                    );

                    // Get new user ID
                    $userId = $db->getConnection()->insert_id;

                    // Generate account number
                    $accountNumber = 'ACC' . str_pad($userId + 1000, 4, '0', STR_PAD_LEFT);

                    // Create account with initial balance of 0
                    $db->query(
                        "INSERT INTO accounts (user_id, account_number, balance, status) VALUES (?, ?, ?, ?)",
                        [$userId, $accountNumber, 0.00, 'active']
                    );

                    // Log audit
                    $db->query(
                        "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                        [$userId, 'registration', 'New user registered', $_SERVER['REMOTE_ADDR']]
                    );

                    // Commit transaction
                    $db->getConnection()->commit();

                    $success = "Registration successful! Your account number is: " . $accountNumber . ". You can now login.";
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container" style="max-width: 500px;">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="login-form">
            <h2>Create Account</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="login.php" class="btn">Go to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required minlength="4" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <small>Minimum 4 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Register</button>
                </form>

                <div style="margin-top: 20px; text-align: center;">
                    <p>Already have an account? <a href="login.php" style="color: #3498db;">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>