<?php
// backend/api/add_transaction.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Validate required fields
if (empty($data->type)) {
    echo json_encode(['success' => false, 'message' => 'Transaction type is required']);
    exit();
}

if (empty($data->source)) {
    echo json_encode(['success' => false, 'message' => 'Source is required']);
    exit();
}

if (empty($data->amount) || $data->amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid amount is required']);
    exit();
}

if (empty($data->transaction_date)) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_dept = $_SESSION['department_id'];
$user_role = $_SESSION['role'];

// Determine department_id
if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $dept_id = isset($data->department_id) ? $data->department_id : null;
} else {
    $dept_id = $user_dept;
}

$type = $data->type;
$source = $data->source;
$amount = $data->amount;
$transaction_date = $data->transaction_date;
$paid_amount = $data->paid_amount ?? ($type == 'income' ? $amount : 0);
$status = $data->status ?? ($type == 'income' ? 'paid' : 'paid');
$description = $data->description ?? '';

// For income, if status is paid, set paid_amount = amount
if ($type == 'income' && $status == 'paid') {
    $paid_amount = $amount;
}

$query = "INSERT INTO transactions (type, source, amount, transaction_date, paid_amount, status, description, department_id, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $db->prepare($query);

if ($stmt->execute([$type, $source, $amount, $transaction_date, $paid_amount, $status, $description, $dept_id])) {
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction added successfully',
        'data' => ['id' => $newId]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add transaction']);
}
?>