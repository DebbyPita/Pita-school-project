<?php

/**
 * Password Hash Generator Tool
 * Use this to generate password hashes for new users
 * Access: http://localhost/pita-school/generate_password.php
 * DELETE THIS FILE in production!
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #2980b9;
        }

        .result {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            word-break: break-all;
        }

        .warning {
            background: #ffe5e5;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-top: 20px;
        }

        .info {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-top: 20px;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>

        <form method="POST">
            <label><strong>Enter Password to Hash:</strong></label>
            <input type="password" name="password" required>
            <button type="submit">Generate Hash</button>
        </form>

        <?php if (isset($hash)): ?>
            <div class="result">
                <h3>Generated Hash:</h3>
                <p><strong>Original Password:</strong> <?php echo htmlspecialchars($password); ?></p>
                <p><strong>Hashed Password:</strong></p>
                <code><?php echo $hash; ?></code>
            </div>

            <div class="info">
                <h3>📝 How to Use:</h3>
                <p>Copy the hash above and use it in your SQL INSERT statement:</p>
                <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto;">
INSERT INTO users (username, password, first_name, last_name, email, role) 
VALUES ('username', '<?php echo $hash; ?>', 'First', 'Last', 'email@example.com', 'student');
            </pre>
            </div>
        <?php endif; ?>

        <div class="warning">
            <h3>⚠️ Security Warning</h3>
            <p><strong>DELETE THIS FILE after use!</strong> This tool should never be accessible in a production environment.</p>
        </div>

        <div class="info">
            <h3>ℹ️ Default System Passwords</h3>
            <p><strong>Admin:</strong> admin123</p>
            <p><strong>Students:</strong> password123</p>
        </div>
    </div>
</body>

</html>