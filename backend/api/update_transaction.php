<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$id = isset($data['id']) ? intval($data['id']) : null;
$type = isset($data['type']) ? $data['type'] : null;
$source = isset($data['source']) ? trim($data['source']) : null;
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$transactionDate = isset($data['transaction_date']) ? $data['transaction_date'] : null;
$status = isset($data['status']) ? $data['status'] : 'pending';
$description = isset($data['description']) ? trim($data['description']) : '';
$departmentId = isset($data['department_id']) ? intval($data['department_id']) : 1;

if (!$id || !$source || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction data']);
    exit;
}

// Get the original transaction to check previous status
$originalQuery = "SELECT type, amount, status FROM transactions WHERE id = ?";
$stmt = $conn->prepare($originalQuery);
$stmt->bind_param("i", $id);
$stmt->execute();
$original = $stmt->get_result()->fetch_assoc();

if (!$original) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

// Update transaction
$updateQuery = "UPDATE transactions SET type = ?, source = ?, amount = ?, transaction_date = ?, status = ?, description = ?, department_id = ? WHERE id = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("ssdsssii", $type, $source, $amount, $transactionDate, $status, $description, $departmentId, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
}

$stmt->close();
$conn->close();
?>