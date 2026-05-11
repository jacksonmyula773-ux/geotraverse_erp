<?php
// backend/api/delete_transaction.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

error_log("=== DELETE TRANSACTION API CALLED ===");

// Get ID from different sources
$data = array();

// Check JSON input
$inputJSON = file_get_contents("php://input");
if ($inputJSON) {
    $data = json_decode($inputJSON, true);
}

// Check POST
if (empty($data) && isset($_POST['id'])) {
    $data['id'] = $_POST['id'];
}

// Check GET
if (empty($data) && isset($_GET['id'])) {
    $data['id'] = $_GET['id'];
}

error_log("Delete data: " . print_r($data, true));

if (!isset($data['id']) || empty($data['id'])) {
    sendResponse(false, null, "Transaction ID required");
}

$id = intval($data['id']);
error_log("Deleting transaction ID: " . $id);

$database = new Database();
$db = $database->getConnection();

// Check if exists
$checkQuery = "SELECT id FROM transactions WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    sendResponse(false, null, "Transaction not found with ID: " . $id);
}

$query = "DELETE FROM transactions WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);

if ($stmt->execute()) {
    error_log("Transaction deleted successfully: " . $id);
    sendResponse(true, null, "Transaction deleted successfully");
} else {
    $error = $stmt->errorInfo();
    error_log("Delete error: " . print_r($error, true));
    sendResponse(false, null, "Failed to delete transaction: " . $error[2]);
}
?>