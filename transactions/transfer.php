<?php

/**
 * OPTIONAL CLEANER VERSION using transaction_helpers.php
 * Only use this if you create the transaction_helpers.php file
 * Otherwise, use the updated version from the previous artifact
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/transaction_helpers.php'; // NEW

requireLogin();

$account = getAccount($_SESSION['user_id']);
$error = '';
$success = '';

if (!$account) {
    $error = "No account found. Please contact administrator.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $account) {
    $toAccountNumber = trim($_POST['to_account']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);

    global $db;

    // Validate amount
    $amountErrors = validateTransactionAmount($amount, 'transfer', $account['balance']);
    if (!empty($amountErrors)) {
        $error = implode('. ', $amountErrors);
    } else {
        // Get recipient account
        $toAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_number = ?", [$toAccountNumber]);

        if (!$toAccount) {
            $error = "Recipient account not found: " . htmlspecialchars($toAccountNumber);
        } elseif ($toAccount['id'] == $account['id']) {
            $error = "Cannot transfer to your own account";
        } else {
            // Validate recipient account
            $accountErrors = validateAccount($toAccount);
            if (!empty($accountErrors)) {
                $error = implode('. ', $accountErrors);
            } else {
                // Process the transfer
                $result = processTransfer($account['id'], $toAccount['id'], $amount, $description);

                if ($result['success']) {
                    // Log to audit
                    logTransaction($_SESSION['user_id'], 'transfer', $amount, $result['reference']);

                    $success = "Transfer successful! Amount: " . formatCurrency($amount) .
                        " to " . $toAccountNumber . " | Reference: " . $result['reference'];

                    // Refresh account
                    $account = getAccount($_SESSION['user_id']);
                } else {
                    $error = "Transfer failed: " . $result['error'];
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
    <title>Transfer Money - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <a href="../logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h2>Transfer Money</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($account): ?>
                <div style="background: #e3f2fd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                    <p><strong>Your Account:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
                    <p><strong>Available Balance:</strong> <?php echo formatCurrency($account['balance']); ?></p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="to_account">Recipient Account Number</label>
                        <input type="text" id="to_account" name="to_account" placeholder="ACC1002" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Transfer Amount (UGX)</label>
                        <input type="number" id="amount" name="amount" step="1" min="1" max="<?php echo min($account['balance'], 5000000); ?>" required>
                        <small>Maximum transfer: UGX 5,000,000 per transaction</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" placeholder="Payment for..." maxlength="255" required>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn">Complete Transfer</button>
                        <a href="../dashboard.php" class="btn secondary">Cancel</a>
                    </div>
                </form>

                <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-top: 20px;">
                    <h4>Available Accounts (Demo):</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>ACC1001 - Admin User</li>
                        <li>ACC1002 - John Doe</li>
                        <li>ACC1003 - Jane Smith</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>