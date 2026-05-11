<?php
// backend/api/get_transactions.php
require_once '../config/database.php';

error_log("=== get_transactions.php called ===");

$database = new Database();
$db = $database->getConnection();

// Check if transactions table has data
$checkQuery = "SELECT COUNT(*) as cnt FROM transactions";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute();
$checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
error_log("Transactions count: " . $checkResult['cnt']);

$query = "SELECT 
    t.id, 
    t.type, 
    t.source, 
    t.amount, 
    t.paid_amount, 
    t.transaction_date, 
    t.status, 
    t.description, 
    t.department_id, 
    t.created_at,
    d.name as department_name 
FROM transactions t
LEFT JOIN departments d ON t.department_id = d.id
ORDER BY t.transaction_date DESC, t.id DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$transactions = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $transactions[] = array(
        'id' => (int)$row['id'],
        'type' => $row['type'],
        'source' => $row['source'],
        'amount' => floatval($row['amount']),
        'paid_amount' => floatval($row['paid_amount']),
        'transaction_date' => $row['transaction_date'],
        'status' => $row['status'],
        'description' => $row['description'],
        'department_id' => (int)$row['department_id'],
        'department_name' => $row['department_name'],
        'created_at' => $row['created_at']
    );
}

error_log("Transactions returned: " . count($transactions));
sendResponse(true, $transactions);
?>