<?php
// backend/api/delete_pending_payment.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');

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

$query = "DELETE FROM pending_payments WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$id])) {
    echo json_encode(['success' => true, 'message' => 'Payment record deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete payment record']);
}
?>