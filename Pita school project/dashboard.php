<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$account = getAccount($_SESSION['user_id']);
$transactions = $account ? getTransactions($account['id']) : [];
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
            <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . 
            htmlspecialchars($_SESSION['last_name'])); ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="account-summary">
            <h2>Account Summary</h2>
            <?php if ($account): ?>
                <div class="account-details">
                    <p>Account Number: <?php echo htmlspecialchars($account['account_number']); ?></p>
                    <p>Balance: $<?php echo number_format($account['balance'], 2); ?></p>
                </div>
            <?php else: ?>
                <p>No account found</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="transactions/deposit.php" class="btn">Make Deposit</a>
            <a href="transactions/withdraw.php" class="btn">Make Withdrawal</a>
            <a href="transactions/transfer.php" class="btn">Transfer Money</a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="btn">Admin Dashboard</a>
            <?php endif; ?>
        </div>

        <div class="transaction-history">
            <h2>Recent Transactions</h2>
            <?php if (!empty($transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                <td class="<?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                    $<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No transactions yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>