<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? 0;
$type = $data['type'] ?? '';
$source = $data['source'] ?? '';
$amount = $data['amount'] ?? 0;
$transaction_date = $data['transaction_date'] ?? date('Y-m-d');
$paid_amount = $data['paid_amount'] ?? 0;
$status = $data['status'] ?? 'pending';
$description = $data['description'] ?? '';
$department_id = $data['department_id'] ?? ($_SESSION['department_id'] ?? 2);

if (!$id || !$source || !$amount) {
    echo json_encode(['success' => false, 'message' => 'ID, source and amount required']);
    exit;
}

$stmt = $conn->prepare("UPDATE transactions SET type = ?, source = ?, amount = ?, transaction_date = ?, paid_amount = ?, status = ?, description = ?, department_id = ? WHERE id = ?");
$stmt->bind_param("ssdssdsii", $type, $source, $amount, $transaction_date, $paid_amount, $status, $description, $department_id, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>