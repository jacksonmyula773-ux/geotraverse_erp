<?php
// backend/api/add_transaction.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== ADD TRANSACTION API CALLED ===");

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data) {
    $data = $_POST;
}

error_log("Add transaction data: " . print_r($data, true));

$source = isset($data['source']) ? trim($data['source']) : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;

if (empty($source)) {
    sendResponse(false, null, "Source is required");
}

if ($amount <= 0) {
    sendResponse(false, null, "Valid amount is required");
}

$database = new Database();
$db = $database->getConnection();

$type = isset($data['type']) ? $data['type'] : 'income';
$transaction_date = isset($data['transaction_date']) ? $data['transaction_date'] : date('Y-m-d');
$status = isset($data['status']) ? $data['status'] : 'pending';
$description = isset($data['description']) ? $data['description'] : null;
$department_id = isset($data['department_id']) ? intval($data['department_id']) : 1;

// Calculate paid_amount based on status
$paid_amount = 0;
if ($status === 'paid') {
    $paid_amount = $amount;
} elseif ($status === 'partial' && isset($data['paid_amount'])) {
    $paid_amount = floatval($data['paid_amount']);
}

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
    $newId = $db->lastInsertId();
    error_log("Transaction added with ID: " . $newId);
    sendResponse(true, array('id' => $newId), "Transaction added successfully");
} else {
    $error = $stmt->errorInfo();
    error_log("Add transaction error: " . print_r($error, true));
    sendResponse(false, null, "Failed to add transaction: " . $error[2]);
}
?>