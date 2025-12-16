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

// Build query
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

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Date', 'Reference', 'Type', 'Description', 'Amount (UGX)', 'Balance After (UGX)']);

// Write data rows
foreach ($transactions as $transaction) {
    fputcsv($output, [
        date('Y-m-d H:i:s', strtotime($transaction['created_at'])),
        $transaction['reference_number'],
        ucwords(str_replace('_', ' ', $transaction['type'])),
        $transaction['description'],
        number_format($transaction['amount'], 0),
        number_format($transaction['balance_after'], 0)
    ]);
}

fclose($output);
exit();
