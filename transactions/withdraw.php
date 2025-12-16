<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$account = getAccount($_SESSION['user_id']);
$error = '';
$success = '';

if (!$account) {
    $error = "No account found. Please contact administrator.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $account) {
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);

    if ($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif ($amount > $account['balance']) {
        $error = "Insufficient funds. Your balance is UGX " . number_format($account['balance'], 0);
    } elseif ($amount > 5000000) {
        $error = "Maximum withdrawal amount is UGX 5,000,000 per transaction";
    } else {
        global $db;

        try {
            // Start transaction
            $db->getConnection()->begin_transaction();

            // Update account balance
            $newBalance = $account['balance'] - $amount;
            $db->query(
                "UPDATE accounts SET balance = ? WHERE id = ?",
                [$newBalance, $account['id']]
            );

            // Generate unique reference number with microseconds
            $reference = 'WTH' . date('YmdHis') . substr(microtime(), 2, 6) . rand(100, 999);

            // Insert transaction record (negative amount for withdrawal)
            $db->query(
                "INSERT INTO transactions (account_id, type, amount, balance_after, description, reference_number) VALUES (?, ?, ?, ?, ?, ?)",
                [$account['id'], 'withdrawal', -$amount, $newBalance, $description, $reference]
            );

            // Log audit
            $db->query(
                "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                [$_SESSION['user_id'], 'withdrawal', "Withdrew UGX " . number_format($amount, 0), $_SERVER['REMOTE_ADDR']]
            );

            // Commit transaction
            $db->getConnection()->commit();

            $success = "Withdrawal successful! Amount: UGX " . number_format($amount, 0) . " | Reference: " . $reference;

            // Refresh account data
            $account = getAccount($_SESSION['user_id']);
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $error = "Transaction failed. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Withdrawal - <?php echo SITE_NAME; ?></title>
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
            <h2>Make a Withdrawal</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($account): ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
                    <p><strong>Available Balance:</strong> UGX <?php echo number_format($account['balance'], 0); ?></p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="amount">Withdrawal Amount (UGX)</label>
                        <input type="number" id="amount" name="amount" step="1" min="1" max="<?php echo min($account['balance'], 5000000); ?>" required>
                        <small>Maximum withdrawal: UGX 5,000,000 per transaction</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" placeholder="Optional description" maxlength="255">
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn">Complete Withdrawal</button>
                        <a href="../dashboard.php" class="btn secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>