<?php

/**
 * Transfer System Diagnostic Tool
 * Place in root: /pita-school/test_transfer_system.php
 * Access: http://localhost/pita-school/test_transfer_system.php
 * DELETE after fixing the issue!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';

global $db;

echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .pass { border-left: 5px solid #27ae60; }
    .fail { border-left: 5px solid #e74c3c; }
    h2 { color: #2c3e50; }
    pre { background: #ecf0f1; padding: 10px; overflow: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #3498db; color: white; }
</style>";

echo "<h1>🔍 Transfer System Diagnostic</h1>";

// Test 1: Database Connection
echo "<div class='test pass'>";
echo "<h2>✅ Test 1: Database Connection</h2>";
try {
    $conn = $db->getConnection();
    echo "Database connected successfully<br>";
    echo "Server: " . $conn->server_info . "<br>";
} catch (Exception $e) {
    echo "</div><div class='test fail'>";
    echo "❌ Connection failed: " . $e->getMessage();
    echo "</div>";
    exit;
}
echo "</div>";

// Test 2: Check Accounts
echo "<div class='test'>";
echo "<h2>Test 2: Accounts Check</h2>";
$accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY account_number");
if (count($accounts) > 0) {
    echo "<table>";
    echo "<tr><th>Account Number</th><th>User ID</th><th>Balance (UGX)</th><th>Status</th></tr>";
    foreach ($accounts as $acc) {
        $statusClass = $acc['status'] === 'active' ? 'pass' : 'fail';
        echo "<tr class='$statusClass'>";
        echo "<td>{$acc['account_number']}</td>";
        echo "<td>{$acc['user_id']}</td>";
        echo "<td>" . number_format($acc['balance'], 0) . "</td>";
        echo "<td>{$acc['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No accounts found!";
}
echo "</div>";

// Test 3: Table Engine Check
echo "<div class='test'>";
echo "<h2>Test 3: Table Engine (Must be InnoDB for transactions)</h2>";
$tables = $db->fetchAll("SHOW TABLE STATUS WHERE Name IN ('accounts', 'transactions', 'audit_log')");
echo "<table>";
echo "<tr><th>Table</th><th>Engine</th><th>Status</th></tr>";
foreach ($tables as $table) {
    $isInnoDB = $table['Engine'] === 'InnoDB';
    $statusClass = $isInnoDB ? 'pass' : 'fail';
    $status = $isInnoDB ? '✅ Good' : '❌ Wrong (Convert to InnoDB)';
    echo "<tr class='$statusClass'>";
    echo "<td>{$table['Name']}</td>";
    echo "<td>{$table['Engine']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test 4: Transaction Support
echo "<div class='test'>";
echo "<h2>Test 4: Database Transaction Support</h2>";
try {
    $conn->begin_transaction();
    echo "✅ BEGIN TRANSACTION works<br>";
    $conn->rollback();
    echo "✅ ROLLBACK works<br>";
    $conn->begin_transaction();
    $conn->commit();
    echo "✅ COMMIT works<br>";
    echo "<strong>Transaction support: ENABLED</strong>";
} catch (Exception $e) {
    echo "❌ Transaction support: FAILED<br>";
    echo "Error: " . $e->getMessage();
}
echo "</div>";

// Test 5: Simulate Transfer
echo "<div class='test'>";
echo "<h2>Test 5: Simulate Transfer (Dry Run - No Changes)</h2>";

// Get first two accounts
$account1 = $db->fetchOne("SELECT * FROM accounts ORDER BY id LIMIT 1");
$account2 = $db->fetchOne("SELECT * FROM accounts ORDER BY id LIMIT 1, 1");

if ($account1 && $account2) {
    echo "<strong>Test Scenario:</strong><br>";
    echo "Transfer UGX 1,000 from {$account1['account_number']} to {$account2['account_number']}<br><br>";

    echo "<strong>Current State:</strong><br>";
    echo "Sender ({$account1['account_number']}): UGX " . number_format($account1['balance'], 0) . "<br>";
    echo "Recipient ({$account2['account_number']}): UGX " . number_format($account2['balance'], 0) . "<br><br>";

    $transferAmount = 1000;

    // Check if transfer is possible
    if ($account1['balance'] < $transferAmount) {
        echo "❌ WOULD FAIL: Insufficient funds<br>";
    } elseif ($account1['status'] !== 'active') {
        echo "❌ WOULD FAIL: Sender account not active<br>";
    } elseif ($account2['status'] !== 'active') {
        echo "❌ WOULD FAIL: Recipient account not active<br>";
    } else {
        echo "✅ WOULD SUCCEED: All conditions met<br><br>";

        $newBalance1 = $account1['balance'] - $transferAmount;
        $newBalance2 = $account2['balance'] + $transferAmount;

        echo "<strong>Expected Result:</strong><br>";
        echo "Sender new balance: UGX " . number_format($newBalance1, 0) . "<br>";
        echo "Recipient new balance: UGX " . number_format($newBalance2, 0) . "<br>";
    }
} else {
    echo "❌ Not enough accounts to test";
}
echo "</div>";

// Test 6: Check Foreign Key Constraints
echo "<div class='test'>";
echo "<h2>Test 6: Foreign Key Constraints</h2>";
$result = $db->fetchAll("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'school_banking'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (count($result) > 0) {
    echo "<table>";
    echo "<tr><th>Table</th><th>Column</th><th>References</th></tr>";
    foreach ($result as $fk) {
        echo "<tr>";
        echo "<td>{$fk['TABLE_NAME']}</td>";
        echo "<td>{$fk['COLUMN_NAME']}</td>";
        echo "<td>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "⚠️ No foreign keys found (this is OK but not recommended)";
}
echo "</div>";

// Test 7: Recent Transactions
echo "<div class='test'>";
echo "<h2>Test 7: Recent Transactions</h2>";
$recentTx = $db->fetchAll("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 5");
if (count($recentTx) > 0) {
    echo "<table>";
    echo "<tr><th>Date</th><th>Account ID</th><th>Type</th><th>Amount</th><th>Reference</th></tr>";
    foreach ($recentTx as $tx) {
        echo "<tr>";
        echo "<td>" . date('Y-m-d H:i', strtotime($tx['created_at'])) . "</td>";
        echo "<td>{$tx['account_id']}</td>";
        echo "<td>{$tx['type']}</td>";
        echo "<td>UGX " . number_format(abs($tx['amount']), 0) . "</td>";
        echo "<td>{$tx['reference_number']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No transactions found yet.";
}
echo "</div>";

// Recommendations
echo "<div class='test'>";
echo "<h2>📋 Recommendations</h2>";

$issues = [];
$fixes = [];

// Check for MyISAM tables
foreach ($tables as $table) {
    if ($table['Engine'] !== 'InnoDB') {
        $issues[] = "Table '{$table['Name']}' is using {$table['Engine']} engine";
        $fixes[] = "ALTER TABLE {$table['Name']} ENGINE=InnoDB;";
    }
}

// Check for inactive accounts
foreach ($accounts as $acc) {
    if ($acc['status'] !== 'active') {
        $issues[] = "Account {$acc['account_number']} is not active";
        $fixes[] = "UPDATE accounts SET status = 'active' WHERE account_number = '{$acc['account_number']}';";
    }
}

if (count($issues) > 0) {
    echo "<strong>⚠️ Issues Found:</strong><br>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ol>";

    echo "<br><strong>🔧 Run these SQL commands to fix:</strong>";
    echo "<pre>";
    foreach ($fixes as $fix) {
        echo "$fix\n";
    }
    echo "</pre>";
} else {
    echo "✅ <strong>No issues found! Your system should be working correctly.</strong><br><br>";
    echo "If transfers still fail, check the detailed error message in transfer.php";
}

echo "</div>";

echo "<div class='test'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li>If any tests failed, run the recommended SQL fixes above</li>";
echo "<li>Try a transfer via the web interface</li>";
echo "<li>If it fails, check the detailed error message</li>";
echo "<li>DELETE this test file after fixing issues</li>";
echo "</ol>";
echo "<br>";
echo "<strong style='color: red;'>⚠️ IMPORTANT: Delete this file after testing!</strong>";
echo "</div>";
