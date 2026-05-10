<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? 'income';
$source = $data['source'] ?? '';
$amount = $data['amount'] ?? 0;
$transaction_date = $data['transaction_date'] ?? date('Y-m-d');
$paid_amount = $data['paid_amount'] ?? 0;
$status = $data['status'] ?? 'pending';
$description = $data['description'] ?? '';
$department_id = $data['department_id'] ?? ($_SESSION['department_id'] ?? 1);

if (!$source || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Source and amount required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO transactions (type, source, amount, transaction_date, paid_amount, status, description, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssddsdsi", $type, $source, $amount, $transaction_date, $paid_amount, $status, $description, $department_id);
$stmt->execute();

echo json_encode(['success' => true, 'id' => $conn->insert_id]);
?>