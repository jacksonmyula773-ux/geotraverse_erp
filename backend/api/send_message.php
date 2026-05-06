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

if (!isset($data->to_department_id) || !isset($data->message)) {
    echo json_encode(['success' => false, 'error' => 'Recipient and message required']);
    exit();
}

$from_dept_id = 1; // Super Admin

$query = "INSERT INTO messages (from_department_id, to_department_id, message, is_read, created_at) 
          VALUES (:from, :to, :msg, 0, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':from', $from_dept_id);
$stmt->bindParam(':to', $data->to_department_id);
$stmt->bindParam(':msg', $data->message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'data' => ['id' => $db->lastInsertId()]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
?>