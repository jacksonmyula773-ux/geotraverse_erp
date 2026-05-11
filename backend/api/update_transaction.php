<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data || !isset($data['id'])) {
    sendResponse(false, null, "Transaction ID required");
}

$database = new Database();
$db = $database->getConnection();

$id = intval($data['id']);
$fields = [];
$params = [':id' => $id];

$allowed = ['type', 'source', 'amount', 'transaction_date', 'status', 'description', 'department_id'];
foreach ($allowed as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

if (isset($data['status'])) {
    if ($data['status'] === 'paid') {
        $fields[] = "paid_amount = amount";
    } elseif ($data['status'] === 'partial' && isset($data['paid_amount'])) {
        $fields[] = "paid_amount = :paid_amount";
        $params[':paid_amount'] = floatval($data['paid_amount']);
    } elseif ($data['status'] === 'pending') {
        $fields[] = "paid_amount = 0";
    }
}

if (empty($fields)) {
    sendResponse(false, null, "No fields to update");
}

$query = "UPDATE transactions SET " . implode(", ", $fields) . " WHERE id = :id";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    sendResponse(true, null, "Transaction updated");
} else {
    sendResponse(false, null, "Update failed");
}
?>