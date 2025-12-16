<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireAdmin();

$users = getAllUsers();
$accounts = getAllAccounts();
$transactions = getAllTransactions();
$auditLogs = getAuditLogs();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <header>
        <h1><?php echo SITE_NAME; ?> - Admin</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <a href="../../logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main>
        <div class="admin-tabs">
            <a href="dashboard.php" class="tab-btn active">Dashboard</a>
            <a href="../../dashboard.php" class="tab-btn">Back to User Dashboard</a>
        </div>

        <div class="admin-section">
            <h2>System Overview</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?php echo count($users); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Accounts</h3>
                    <p><?php echo count($accounts); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Transactions</h3>
                    <p><?php echo count($transactions); ?></p>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h2>Recent Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['account_number'] . ' (' . $transaction['first_name'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                            <td class="<?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                UGX <?php echo number_format(abs($transaction['amount']), 0); ?>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-section">
            <h2>Audit Log</h2>
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['username']); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>