<?php
// backend/api/delete_transaction.php
require_once '../config/database.php';

error_log("=== delete_transaction.php called ===");

// Get ID from different sources
$data = array();
$input = file_get_contents("php://input");
if ($input) {
    $data = json_decode($input, true);
}
if (empty($data) && isset($_POST['id'])) {
    $data['id'] = $_POST['id'];
}
if (empty($data) && isset($_GET['id'])) {
    $data['id'] = $_GET['id'];
}

error_log("Delete transaction data: " . print_r($data, true));

if (!isset($data['id']) || empty($data['id'])) {
    sendResponse(false, null, "Transaction ID required");
}

$database = new Database();
$db = $database->getConnection();

$id = intval($data['id']);

$query = "DELETE FROM transactions WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);

if ($stmt->execute()) {
    sendResponse(true, null, "Transaction deleted successfully");
} else {
    $error = $stmt->errorInfo();
    error_log("Delete transaction error: " . print_r($error, true));
    sendResponse(false, null, "Failed to delete transaction: " . $error[2]);
}
?>