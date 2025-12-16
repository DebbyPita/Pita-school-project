<?php
function getAccount($user_id) {
    global $db;
    return $db->fetchOne("SELECT * FROM accounts WHERE user_id = ?", [$user_id]);
}

function getTransactions($account_id, $limit = 10) {
    global $db;
    return $db->fetchAll(
        "SELECT * FROM transactions WHERE account_id = ? ORDER BY created_at DESC LIMIT ?",
        [$account_id, $limit]
    );
}

function getAllUsers() {
    global $db;
    return $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
}

function getAllAccounts() {
    global $db;
    return $db->fetchAll("
        SELECT a.*, u.first_name, u.last_name 
        FROM accounts a
        JOIN users u ON a.user_id = u.id
    ");
}

function getAllTransactions() {
    global $db;
    return $db->fetchAll("
        SELECT t.*, a.account_number, u.first_name, u.last_name 
        FROM transactions t
        JOIN accounts a ON t.account_id = a.id
        JOIN users u ON a.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 20
    ");
}

function getAuditLogs() {
    global $db;
    return $db->fetchAll("
        SELECT a.*, u.username 
        FROM audit_log a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
}