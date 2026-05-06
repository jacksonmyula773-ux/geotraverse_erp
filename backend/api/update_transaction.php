<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$data = json_decode(file_get_contents('php://input'), true);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Transaction ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$updates = [];
$params = [':id' => $id];

if (isset($data['type'])) {
    $updates[] = "type = :type";
    $params[':type'] = $data['type'];
}
if (isset($data['source'])) {
    $updates[] = "source = :source";
    $params[':source'] = $data['source'];
}
if (isset($data['amount'])) {
    $updates[] = "amount = :amount";
    $params[':amount'] = $data['amount'];
}
if (isset($data['paid_amount'])) {
    $updates[] = "paid_amount = :paid_amount";
    $params[':paid_amount'] = $data['paid_amount'];
}
if (isset($data['status'])) {
    $updates[] = "status = :status";
    $params[':status'] = $data['status'];
}
if (isset($data['description'])) {
    $updates[] = "description = :description";
    $params[':description'] = $data['description'];
}
if (isset($data['transaction_date'])) {
    $updates[] = "transaction_date = :transaction_date";
    $params[':transaction_date'] = $data['transaction_date'];
}
if (isset($data['department_id'])) {
    $updates[] = "department_id = :dept_id";
    $params[':dept_id'] = $data['department_id'];
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit();
}

$query = "UPDATE transactions SET " . implode(', ', $updates) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update transaction']);
}
?>