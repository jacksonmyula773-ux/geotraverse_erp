<?php
// backend/api/add_pending_payment.php
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

if (empty($data->client_name)) {
    echo json_encode(['success' => false, 'message' => 'Client name required']);
    exit();
}

if (empty($data->amount) || $data->amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid amount required']);
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

if ($user_dept == 1 || $user_role == 'Super Administrator') {
    $dept_id = isset($data->department_id) ? $data->department_id : null;
} else {
    $dept_id = $user_dept;
}

$client_name = $data->client_name;
$project_name = $data->project_name ?? '';
$amount = $data->amount;
$paid_amount = $data->paid_amount ?? 0;
$due_date = $data->due_date ?? date('Y-m-d', strtotime('+30 days'));
$status = $data->status ?? 'pending';
$notes = $data->notes ?? '';

$query = "INSERT INTO pending_payments (client_name, project_name, amount, paid_amount, due_date, status, notes, department_id, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $db->prepare($query);

if ($stmt->execute([$client_name, $project_name, $amount, $paid_amount, $due_date, $status, $notes, $dept_id])) {
    echo json_encode([
        'success' => true,
        'message' => 'Payment record added successfully',
        'data' => ['id' => $db->lastInsertId()]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add payment record']);
}
?>