<?php
// backend/api/update_transaction.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    sendResponse(false, null, "Transaction ID required");
}

$database = new Database();
$db = $database->getConnection();

$fields = [];
$params = [':id' => $data['id']];

$allowed_fields = ['type', 'source', 'amount', 'paid_amount', 'transaction_date', 'status', 'description', 'department_id'];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

$query = "UPDATE transactions SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    sendResponse(true, null, "Transaction updated successfully");
} else {
    sendResponse(false, null, "Failed to update transaction");
}
?>