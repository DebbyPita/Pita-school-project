<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$account = getAccount($_SESSION['user_id']);

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

global $db;

// Build query based on filters
$query = "SELECT * FROM transactions WHERE account_id = ?";
$params = [$account['id']];

if (!empty($startDate)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $endDate;
}

if ($type !== 'all') {
    $query .= " AND type = ?";
    $params[] = $type;
}

$query .= " ORDER BY created_at DESC";

$transactions = $db->fetchAll($query, $params);

// Calculate statistics
$totalDeposits = 0;
$totalWithdrawals = 0;
$totalTransfersIn = 0;
$totalTransfersOut = 0;

foreach ($transactions as $transaction) {
    switch ($transaction['type']) {
        case 'deposit':
            $totalDeposits += $transaction['amount'];
            break;
        case 'withdrawal':
            $totalWithdrawals += abs($transaction['amount']);
            break;
        case 'transfer_in':
            $totalTransfersIn += $transaction['amount'];
            break;
        case 'transfer_out':
            $totalTransfersOut += abs($transaction['amount']);
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Reports - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .report-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .report-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-value.positive {
            color: #27ae60;
        }

        .stat-value.negative {
            color: #e74c3c;
        }

        @media print {

            header,
            .report-filters,
            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main>
        <h2>Transaction Reports & Statements</h2>

        <div class="report-filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group" style="margin: 0;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="type">Transaction Type</label>
                        <select id="type" name="type" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="deposit" <?php echo $type === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                            <option value="withdrawal" <?php echo $type === 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option>
                            <option value="transfer_in" <?php echo $type === 'transfer_in' ? 'selected' : ''; ?>>Transfers In</option>
                            <option value="transfer_out" <?php echo $type === 'transfer_out' ? 'selected' : ''; ?>>Transfers Out</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Filter</button>
                    <a href="reports.php" class="btn secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="report-stats">
            <div class="stat-box">
                <div class="stat-label">Current Balance</div>
                <div class="stat-value">UGX <?php echo number_format($account['balance'], 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Deposits</div>
                <div class="stat-value positive">UGX <?php echo number_format($totalDeposits, 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Withdrawals</div>
                <div class="stat-value negative">UGX <?php echo number_format($totalWithdrawals, 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Transfers In</div>
                <div class="stat-value positive">UGX <?php echo number_format($totalTransfersIn, 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Transfers Out</div>
                <div class="stat-value negative">UGX <?php echo number_format($totalTransfersOut, 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value"><?php echo count($transactions); ?></div>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn">Print Report</button>
            <a href="export_csv.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&type=<?php echo $type; ?>" class="btn secondary">Export CSV</a>
            <a href="dashboard.php" class="btn secondary">Back to Dashboard</a>
        </div>

        <div class="transaction-history">
            <h3>Transaction Details</h3>
            <?php if (!empty($transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['reference_number']); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $transaction['type'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td class="<?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                    UGX <?php echo number_format(abs($transaction['amount']), 0); ?>
                                </td>
                                <td>UGX <?php echo number_format($transaction['balance_after'], 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No transactions found for the selected period.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>