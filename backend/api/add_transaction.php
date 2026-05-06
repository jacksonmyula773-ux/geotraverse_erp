<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->source) || !isset($data->amount)) {
    echo json_encode(['success' => false, 'error' => 'Source and amount required']);
    exit();
}

$query = "INSERT INTO transactions (type, department_id, source, amount, paid_amount, status, description, transaction_date) 
          VALUES (:type, :dept_id, :source, :amount, :paid, :status, :desc, :date)";

$stmt = $db->prepare($query);
$stmt->bindParam(':type', $data->type);
$stmt->bindParam(':dept_id', $data->department_id);
$stmt->bindParam(':source', $data->source);
$stmt->bindParam(':amount', $data->amount);
$stmt->bindParam(':paid', $data->paid_amount);
$stmt->bindParam(':status', $data->status);
$stmt->bindParam(':desc', $data->description);
$stmt->bindParam(':date', $data->transaction_date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'data' => ['id' => $db->lastInsertId()]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add transaction']);
}
?>