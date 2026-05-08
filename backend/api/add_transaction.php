<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$type = isset($data['type']) ? $data['type'] : null;
$source = isset($data['source']) ? trim($data['source']) : null;
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$transactionDate = isset($data['transaction_date']) ? $data['transaction_date'] : null;
$status = isset($data['status']) ? $data['status'] : 'pending';
$description = isset($data['description']) ? trim($data['description']) : '';
$departmentId = isset($data['department_id']) ? intval($data['department_id']) : 1;

if (!$source || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Source and amount required']);
    exit;
}

$query = "INSERT INTO transactions (type, source, amount, transaction_date, status, description, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssdsssi", $type, $source, $amount, $transactionDate, $status, $description, $departmentId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Transaction added successfully', 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add transaction']);
}

$stmt->close();
$conn->close();
?>