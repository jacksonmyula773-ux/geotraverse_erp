<?php
// backend/api/update_transaction.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== update_transaction.php called ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

// Get data from different sources
$inputJSON = file_get_contents("php://input");
error_log("Raw input: " . $inputJSON);

$data = array();

// Try JSON first
if ($inputJSON) {
    $data = json_decode($inputJSON, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        $data = array();
    }
}

// If no data from JSON, try POST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// If still no data, try GET
if (empty($data) && !empty($_GET)) {
    $data = $_GET;
}

error_log("Final data: " . print_r($data, true));

// Check for ID
$id = null;
if (isset($data['id'])) {
    $id = intval($data['id']);
} elseif (isset($data['transaction_id'])) {
    $id = intval($data['transaction_id']);
}

if (!$id) {
    sendResponse(false, null, "Transaction ID is required");
}

$database = new Database();
$db = $database->getConnection();

// First check if transaction exists
$checkQuery = "SELECT id FROM transactions WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    sendResponse(false, null, "Transaction not found with ID: " . $id);
}

// Prepare update fields
$fields = array();
$params = array(':id' => $id);

// Map of allowed fields
$fieldMapping = array(
    'type' => 'type',
    'source' => 'source',
    'amount' => 'amount',
    'transaction_date' => 'transaction_date',
    'status' => 'status',
    'description' => 'description',
    'department_id' => 'department_id'
);

foreach ($fieldMapping as $inputField => $dbField) {
    if (isset($data[$inputField]) && $data[$inputField] !== '') {
        $fields[] = "$dbField = :$dbField";
        if ($dbField === 'amount') {
            $params[":$dbField"] = floatval($data[$inputField]);
        } else {
            $params[":$dbField"] = $data[$inputField];
        }
    }
}

// Handle paid_amount based on status
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
error_log("Update query: " . $query);
error_log("Params: " . print_r($params, true));

$stmt = $db->prepare($query);

try {
    if ($stmt->execute($params)) {
        sendResponse(true, array('id' => $id), "Transaction updated successfully");
    } else {
        $error = $stmt->errorInfo();
        error_log("Execute error: " . print_r($error, true));
        sendResponse(false, null, "Failed to update: " . $error[2]);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    sendResponse(false, null, "Error: " . $e->getMessage());
}
?>