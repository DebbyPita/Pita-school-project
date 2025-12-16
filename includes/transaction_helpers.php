<?php

/**
 * Transaction Helper Functions
 * Place in: includes/transaction_helpers.php
 */

/**
 * Generate unique reference number for transactions
 * Format: PREFIX + YYYYMMDDHHMMSS + Microseconds + Random
 * Example: DEP20241215205138520012345
 */
function generateTransactionReference($type = 'TXN')
{
    // Validate prefix
    $validTypes = ['DEP', 'WTH', 'TRF', 'TXN'];
    if (!in_array($type, $validTypes)) {
        $type = 'TXN';
    }

    // Get current timestamp with microseconds
    $microtime = microtime(true);
    $datetime = date('YmdHis', $microtime);
    $microseconds = sprintf("%06d", ($microtime - floor($microtime)) * 1000000);

    // Add random number for extra uniqueness
    $random = rand(100, 999);

    // Combine all parts
    $reference = $type . $datetime . $microseconds . $random;

    return $reference;
}

/**
 * Validate transaction amount
 */
function validateTransactionAmount($amount, $type, $balance = null)
{
    $errors = [];

    // Check if amount is positive
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0";
    }

    // Check maximums based on type
    switch ($type) {
        case 'deposit':
            if ($amount > 10000000) {
                $errors[] = "Maximum deposit is UGX 10,000,000";
            }
            break;

        case 'withdrawal':
        case 'transfer':
            if ($amount > 5000000) {
                $max = $type === 'withdrawal' ? 'withdrawal' : 'transfer';
                $errors[] = "Maximum $max is UGX 5,000,000";
            }
            if ($balance !== null && $amount > $balance) {
                $errors[] = "Insufficient funds. Balance: UGX " . number_format($balance, 0);
            }
            break;
    }

    return $errors;
}

/**
 * Format currency for display
 */
function formatCurrency($amount, $showSign = false)
{
    $formatted = 'UGX ' . number_format(abs($amount), 0);

    if ($showSign && $amount != 0) {
        $formatted = ($amount > 0 ? '+' : '-') . $formatted;
    }

    return $formatted;
}

/**
 * Log transaction to audit trail
 */
function logTransaction($userId, $action, $amount, $reference = null)
{
    global $db;

    $details = ucfirst($action) . " " . formatCurrency($amount);
    if ($reference) {
        $details .= " (Ref: $reference)";
    }

    $db->query(
        "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$userId, $action, $details, $_SERVER['REMOTE_ADDR']]
    );
}

/**
 * Check if account is valid for transactions
 */
function validateAccount($account)
{
    $errors = [];

    if (!$account) {
        $errors[] = "Account not found";
        return $errors;
    }

    if ($account['status'] !== 'active') {
        $errors[] = "Account is not active. Status: " . $account['status'];
    }

    return $errors;
}

/**
 * Process deposit transaction
 */
function processDeposit($accountId, $amount, $description = '')
{
    global $db;

    try {
        $db->getConnection()->begin_transaction();

        // Get current balance
        $account = $db->fetchOne("SELECT balance FROM accounts WHERE id = ?", [$accountId]);

        // Calculate new balance
        $newBalance = $account['balance'] + $amount;

        // Update balance
        $db->query(
            "UPDATE accounts SET balance = ? WHERE id = ?",
            [$newBalance, $accountId]
        );

        // Generate reference
        $reference = generateTransactionReference('DEP');

        // Insert transaction
        $db->query(
            "INSERT INTO transactions (account_id, type, amount, balance_after, description, reference_number) 
             VALUES (?, 'deposit', ?, ?, ?, ?)",
            [$accountId, $amount, $newBalance, $description, $reference]
        );

        $db->getConnection()->commit();

        return ['success' => true, 'reference' => $reference, 'balance' => $newBalance];
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process withdrawal transaction
 */
function processWithdrawal($accountId, $amount, $description = '')
{
    global $db;

    try {
        $db->getConnection()->begin_transaction();

        // Get current balance
        $account = $db->fetchOne("SELECT balance FROM accounts WHERE id = ?", [$accountId]);

        // Check sufficient funds
        if ($account['balance'] < $amount) {
            throw new Exception("Insufficient funds");
        }

        // Calculate new balance
        $newBalance = $account['balance'] - $amount;

        // Update balance
        $db->query(
            "UPDATE accounts SET balance = ? WHERE id = ?",
            [$newBalance, $accountId]
        );

        // Generate reference
        $reference = generateTransactionReference('WTH');

        // Insert transaction (negative amount)
        $db->query(
            "INSERT INTO transactions (account_id, type, amount, balance_after, description, reference_number) 
             VALUES (?, 'withdrawal', ?, ?, ?, ?)",
            [$accountId, -$amount, $newBalance, $description, $reference]
        );

        $db->getConnection()->commit();

        return ['success' => true, 'reference' => $reference, 'balance' => $newBalance];
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process transfer transaction
 */
function processTransfer($fromAccountId, $toAccountId, $amount, $description = '')
{
    global $db;

    try {
        $db->getConnection()->begin_transaction();

        // Get both accounts
        $fromAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$fromAccountId]);
        $toAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$toAccountId]);

        // Validate
        if (!$fromAccount || !$toAccount) {
            throw new Exception("Account not found");
        }

        if ($fromAccount['balance'] < $amount) {
            throw new Exception("Insufficient funds");
        }

        if ($toAccount['status'] !== 'active') {
            throw new Exception("Recipient account is not active");
        }

        // Calculate new balances
        $newFromBalance = $fromAccount['balance'] - $amount;
        $newToBalance = $toAccount['balance'] + $amount;

        // Update both balances
        $db->query("UPDATE accounts SET balance = ? WHERE id = ?", [$newFromBalance, $fromAccountId]);
        $db->query("UPDATE accounts SET balance = ? WHERE id = ?", [$newToBalance, $toAccountId]);

        // Generate unique reference
        $reference = generateTransactionReference('TRF');

        // Insert sender transaction
        $db->query(
            "INSERT INTO transactions (account_id, type, amount, balance_after, description, reference_number) 
             VALUES (?, 'transfer_out', ?, ?, ?, ?)",
            [$fromAccountId, -$amount, $newFromBalance, "Transfer to " . $toAccount['account_number'] . " - " . $description, $reference]
        );

        // Insert recipient transaction
        $db->query(
            "INSERT INTO transactions (account_id, type, amount, balance_after, description, reference_number) 
             VALUES (?, 'transfer_in', ?, ?, ?, ?)",
            [$toAccountId, $amount, $newToBalance, "Transfer from " . $fromAccount['account_number'] . " - " . $description, $reference]
        );

        $db->getConnection()->commit();

        return [
            'success' => true,
            'reference' => $reference,
            'from_balance' => $newFromBalance,
            'to_balance' => $newToBalance
        ];
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
