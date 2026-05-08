<?php
// backend/api/update_pending_payment.php
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
    echo json_encode(['success' => false, 'message' => 'Payment ID required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$id = (int)$data->id;

$updates = [];
$params = [];

if (isset($data->client_name)) { $updates[] = "client_name = ?"; $params[] = $data->client_name; }
if (isset($data->project_name)) { $updates[] = "project_name = ?"; $params[] = $data->project_name; }
if (isset($data->amount)) { $updates[] = "amount = ?"; $params[] = $data->amount; }
if (isset($data->paid_amount)) { $updates[] = "paid_amount = ?"; $params[] = $data->paid_amount; }
if (isset($data->due_date)) { $updates[] = "due_date = ?"; $params[] = $data->due_date; }
if (isset($data->status)) { $updates[] = "status = ?"; $params[] = $data->status; }
if (isset($data->notes)) { $updates[] = "notes = ?"; $params[] = $data->notes; }

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$params[] = $id;
$query = "UPDATE pending_payments SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute($params)) {
    echo json_encode(['success' => true, 'message' => 'Payment record updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update']);
}
?>