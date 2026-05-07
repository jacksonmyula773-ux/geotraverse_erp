<?php
// backend/api/update_transaction.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data || empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;
$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Check if transaction exists and get its department
$checkQuery = "SELECT department_id, type FROM transactions WHERE id = ?";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([$id]);
$transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

// Check permission
if ($user_dept != 1 && $user_role != 'Super Administrator' && $transaction['department_id'] != $user_dept) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Build update query
$updates = [];
$params = [];

if (isset($data->type)) {
    $updates[] = "type = ?";
    $params[] = $data->type;
}
if (isset($data->source)) {
    $updates[] = "source = ?";
    $params[] = $data->source;
}
if (isset($data->amount)) {
    $updates[] = "amount = ?";
    $params[] = $data->amount;
}
if (isset($data->transaction_date)) {
    $updates[] = "transaction_date = ?";
    $params[] = $data->transaction_date;
}
if (isset($data->paid_amount)) {
    $updates[] = "paid_amount = ?";
    $params[] = $data->paid_amount;
}
if (isset($data->status)) {
    $updates[] = "status = ?";
    $params[] = $data->status;
}
if (isset($data->description)) {
    $updates[] = "description = ?";
    $params[] = $data->description;
}
if (isset($data->department_id) && ($user_dept == 1 || $user_role == 'Super Administrator')) {
    $updates[] = "department_id = ?";
    $params[] = $data->department_id;
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE transactions SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
}
?>