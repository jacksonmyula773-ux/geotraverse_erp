<?php
// backend/api/get_transactions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== GET TRANSACTIONS API CALLED ===");

$database = new Database();
$db = $database->getConnection();

// Check if table has data
$checkQuery = "SELECT COUNT(*) as cnt FROM transactions";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute();
$count = $checkStmt->fetch(PDO::FETCH_ASSOC);
error_log("Total transactions in DB: " . $count['cnt']);

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

error_log("Returning " . count($transactions) . " transactions");
sendResponse(true, $transactions);
?>