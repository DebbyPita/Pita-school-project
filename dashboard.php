<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$account = getAccount($_SESSION['user_id']);
$transactions = $account ? getTransactions($account['id']) : [];

// Check for low balance
$lowBalanceThreshold = 100000; // UGX 100,000
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="account-summary">
            <h2>Account Summary</h2>
            <?php if ($account): ?>
                <div class="account-details">
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($account['account_number']); ?></p>
                    <p><strong>Account Status:</strong>
                        <span style="color: <?php echo $account['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>;">
                            <?php echo ucfirst($account['status']); ?>
                        </span>
                    </p>
                    <p><strong>Current Balance:</strong>
                        <span style="font-size: 28px; color: #2c3e50; font-weight: bold;">
                            UGX <?php echo number_format($account['balance'], 0); ?>
                        </span>
                    </p>

                    <?php if ($account['balance'] < $lowBalanceThreshold && $account['balance'] > 0): ?>
                        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-top: 10px; border-radius: 4px;">
                            ⚠️ <strong>Low Balance Alert:</strong> Your balance is below UGX <?php echo number_format($lowBalanceThreshold, 0); ?>. Consider making a deposit.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No account found. Please contact administrator.</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="transactions/deposit.php" class="btn">💰 Make Deposit</a>
            <a href="transactions/withdraw.php" class="btn">💸 Make Withdrawal</a>
            <a href="transactions/transfer.php" class="btn">↔️ Transfer Money</a>
            <a href="reports.php" class="btn secondary">📊 View Reports</a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="btn" style="background: #e74c3c;">🔐 Admin Dashboard</a>
            <?php endif; ?>
        </div>

        <div class="transaction-history">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2>Recent Transactions</h2>
                <a href="reports.php" style="color: #3498db; font-size: 14px;">View All →</a>
            </div>

            <?php if (!empty($transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $transaction['type'])); ?></td>
                                <td class="<?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $transaction['amount'] > 0 ? '+' : ''; ?>UGX <?php echo number_format(abs($transaction['amount']), 0); ?>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><small><?php echo htmlspecialchars($transaction['reference_number']); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No transactions yet. Start by making a deposit!</p>
            <?php endif; ?>
        </div>

        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <h3>💡 Quick Tips</h3>
            <ul style="margin-left: 20px; line-height: 2;">
                <li>Keep your account balance above UGX <?php echo number_format($lowBalanceThreshold, 0); ?> to avoid low balance alerts</li>
                <li>Review your transaction history regularly</li>
                <li>Use strong passwords and never share them</li>
                <li>Contact support if you notice any suspicious activity</li>
            </ul>
        </div>
    </main>
</body>

</html>