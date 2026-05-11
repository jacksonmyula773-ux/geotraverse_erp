<?php
// backend/api/add_transaction.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['source']) || empty($data['source']) || !isset($data['amount']) || $data['amount'] <= 0) {
    sendResponse(false, null, "Source and valid amount required");
}

$database = new Database();
$db = $database->getConnection();

$type = $data['type'] ?? 'income';
$source = $data['source'];
$amount = $data['amount'];
$paid_amount = $data['paid_amount'] ?? ($data['status'] === 'paid' ? $amount : 0);
$transaction_date = $data['transaction_date'] ?? date('Y-m-d');
$status = $data['status'] ?? 'pending';
$description = $data['description'] ?? null;
$department_id = $data['department_id'] ?? 1;

$query = "INSERT INTO transactions (type, source, amount, paid_amount, transaction_date, status, description, department_id) 
          VALUES (:type, :source, :amount, :paid_amount, :transaction_date, :status, :description, :department_id)";
$stmt = $db->prepare($query);
$stmt->bindParam(':type', $type);
$stmt->bindParam(':source', $source);
$stmt->bindParam(':amount', $amount);
$stmt->bindParam(':paid_amount', $paid_amount);
$stmt->bindParam(':transaction_date', $transaction_date);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':department_id', $department_id);

if ($stmt->execute()) {
    sendResponse(true, ['id' => $db->lastInsertId()], "Transaction added successfully");
} else {
    sendResponse(false, null, "Failed to add transaction");
}
?>